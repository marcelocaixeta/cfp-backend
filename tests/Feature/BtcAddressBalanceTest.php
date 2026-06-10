<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BtcAddressBalanceTest extends TestCase
{
    use RefreshDatabase;

    private string $address = 'bc1qr9pj5udtwu538ty8gq5k8dh7fm3s7fq46tgmz3';

    public function test_authenticated_user_can_fetch_bitcoin_address_balance(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Http::fake([
            'https://blockstream.info/api/address/*' => Http::response([
                'address' => $this->address,
                'chain_stats' => [
                    'tx_count' => 3,
                    'funded_txo_sum' => 123456789,
                    'spent_txo_sum' => 23456789,
                ],
                'mempool_stats' => [
                    'tx_count' => 1,
                    'funded_txo_sum' => 5000,
                    'spent_txo_sum' => 1000,
                ],
            ]),
        ]);

        $this->getJson('/api/v1/btc/address-balance?address='.$this->address)
            ->assertOk()
            ->assertJsonPath('data.address', $this->address)
            ->assertJsonPath('data.confirmed_balance_sats', 100000000)
            ->assertJsonPath('data.confirmed_balance_btc', '1.00000000')
            ->assertJsonPath('data.mempool_balance_sats', 4000)
            ->assertJsonPath('data.mempool_balance_btc', '0.00004000')
            ->assertJsonPath('data.total_balance_sats', 100004000)
            ->assertJsonPath('data.total_balance_btc', '1.00004000')
            ->assertJsonPath('data.transaction_count.confirmed', 3)
            ->assertJsonPath('data.transaction_count.mempool', 1);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://blockstream.info/api/address/'.$this->address);
    }

    public function test_authenticated_user_can_fetch_configured_wallet_address_balance(): void
    {
        Sanctum::actingAs(User::factory()->create());
        config(['services.bitcoin.wallet_address' => $this->address]);

        Http::fake([
            'https://blockstream.info/api/address/*' => Http::response([
                'address' => $this->address,
                'chain_stats' => [
                    'tx_count' => 1,
                    'funded_txo_sum' => 250000000,
                    'spent_txo_sum' => 0,
                ],
                'mempool_stats' => [
                    'tx_count' => 0,
                    'funded_txo_sum' => 0,
                    'spent_txo_sum' => 0,
                ],
            ]),
        ]);

        $this->getJson('/api/v1/btc/address-balance')
            ->assertOk()
            ->assertJsonPath('data.address', $this->address)
            ->assertJsonPath('data.total_balance_btc', '2.50000000');
    }

    public function test_guest_cannot_fetch_bitcoin_address_balance(): void
    {
        $this->getJson('/api/v1/btc/address-balance?address='.$this->address)
            ->assertUnauthorized();
    }

    public function test_it_rejects_invalid_bitcoin_address(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Http::fake();

        $this->getJson('/api/v1/btc/address-balance?address=endereco-invalido')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('address');

        Http::assertNothingSent();
    }
}
