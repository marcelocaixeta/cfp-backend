<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ativos_btc')) {
            return;
        }

        if (! Schema::hasColumn('ativos_btc', 'quantidade_satoshis')) {
            Schema::table('ativos_btc', function (Blueprint $table): void {
                $table->decimal('quantidade_satoshis', 30, 10)->default(0)->after('rotulo');
            });
        }

        if (Schema::hasColumn('ativos_btc', 'quantidade_btc')) {
            DB::table('ativos_btc')
                ->select(['id', 'quantidade_btc'])
                ->orderBy('id')
                ->chunkById(100, function ($assets): void {
                    foreach ($assets as $asset) {
                        DB::table('ativos_btc')
                            ->where('id', $asset->id)
                            ->update([
                                'quantidade_satoshis' => number_format((float) $asset->quantidade_btc * 100_000_000, 10, '.', ''),
                            ]);
                    }
                });

            Schema::table('ativos_btc', function (Blueprint $table): void {
                $table->dropColumn('quantidade_btc');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ativos_btc')) {
            return;
        }

        if (! Schema::hasColumn('ativos_btc', 'quantidade_btc')) {
            Schema::table('ativos_btc', function (Blueprint $table): void {
                $table->decimal('quantidade_btc', 20, 8)->default(0)->after('rotulo');
            });
        }

        if (Schema::hasColumn('ativos_btc', 'quantidade_satoshis')) {
            DB::table('ativos_btc')
                ->select(['id', 'quantidade_satoshis'])
                ->orderBy('id')
                ->chunkById(100, function ($assets): void {
                    foreach ($assets as $asset) {
                        DB::table('ativos_btc')
                            ->where('id', $asset->id)
                            ->update([
                                'quantidade_btc' => number_format((float) $asset->quantidade_satoshis / 100_000_000, 8, '.', ''),
                            ]);
                    }
                });

            Schema::table('ativos_btc', function (Blueprint $table): void {
                $table->dropColumn('quantidade_satoshis');
            });
        }
    }
};
