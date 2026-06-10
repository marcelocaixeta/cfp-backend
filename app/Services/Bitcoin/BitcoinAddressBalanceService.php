<?php

namespace App\Services\Bitcoin;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use RuntimeException;

class BitcoinAddressBalanceService
{
    public function __construct(private readonly HttpFactory $http) {}

    /**
     * @return array<string, mixed>
     */
    public function getBalance(string $address): array
    {
        $baseUrl = rtrim((string) config('services.bitcoin.explorer_url'), '/');
        $timeout = (int) config('services.bitcoin.timeout', 10);

        try {
            $payload = $this->http
                ->baseUrl($baseUrl)
                ->acceptJson()
                ->timeout($timeout)
                ->get("/address/{$address}")
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            throw new RuntimeException('Unable to fetch the Bitcoin address balance.', previous: $exception);
        }

        if (! is_array($payload)) {
            throw new RuntimeException('The Bitcoin explorer returned an invalid response.');
        }

        $confirmedSats = $this->balanceFromStats((array) Arr::get($payload, 'chain_stats', []));
        $mempoolSats = $this->balanceFromStats((array) Arr::get($payload, 'mempool_stats', []));
        $totalSats = $confirmedSats + $mempoolSats;

        return [
            'address' => (string) Arr::get($payload, 'address', $address),
            'confirmed_balance_sats' => $confirmedSats,
            'confirmed_balance_btc' => $this->satoshisToBtc($confirmedSats),
            'mempool_balance_sats' => $mempoolSats,
            'mempool_balance_btc' => $this->satoshisToBtc($mempoolSats),
            'total_balance_sats' => $totalSats,
            'total_balance_btc' => $this->satoshisToBtc($totalSats),
            'transaction_count' => [
                'confirmed' => (int) Arr::get($payload, 'chain_stats.tx_count', 0),
                'mempool' => (int) Arr::get($payload, 'mempool_stats.tx_count', 0),
            ],
            'source' => $baseUrl,
        ];
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    private function balanceFromStats(array $stats): int
    {
        return (int) ($stats['funded_txo_sum'] ?? 0) - (int) ($stats['spent_txo_sum'] ?? 0);
    }

    private function satoshisToBtc(int $satoshis): string
    {
        $sign = $satoshis < 0 ? '-' : '';
        $absoluteSatoshis = abs($satoshis);
        $whole = intdiv($absoluteSatoshis, 100_000_000);
        $fraction = $absoluteSatoshis % 100_000_000;

        return sprintf('%s%d.%08d', $sign, $whole, $fraction);
    }
}
