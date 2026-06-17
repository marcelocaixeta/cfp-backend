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

        Schema::table('ativos_btc', function (Blueprint $table): void {
            if (! Schema::hasColumn('ativos_btc', 'tipo_ativo')) {
                $table->string('tipo_ativo')->default('BTC')->after('rotulo');
            }

            if (! Schema::hasColumn('ativos_btc', 'valor_investido')) {
                $table->decimal('valor_investido', 18, 2)->nullable()->after('preco_medio_compra');
            }

            if (! Schema::hasColumn('ativos_btc', 'valor_atual')) {
                $table->decimal('valor_atual', 18, 2)->nullable()->after('valor_investido');
            }
        });

        DB::statement("UPDATE ativos_btc SET tipo_ativo = 'BTC' WHERE tipo_ativo IS NULL");
        DB::statement('ALTER TABLE ativos_btc ALTER COLUMN quantidade_satoshis DROP NOT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('ativos_btc')) {
            return;
        }

        DB::statement("UPDATE ativos_btc SET quantidade_satoshis = 0 WHERE quantidade_satoshis IS NULL");
        DB::statement('ALTER TABLE ativos_btc ALTER COLUMN quantidade_satoshis SET NOT NULL');

        Schema::table('ativos_btc', function (Blueprint $table): void {
            if (Schema::hasColumn('ativos_btc', 'valor_atual')) {
                $table->dropColumn('valor_atual');
            }

            if (Schema::hasColumn('ativos_btc', 'valor_investido')) {
                $table->dropColumn('valor_investido');
            }

            if (Schema::hasColumn('ativos_btc', 'tipo_ativo')) {
                $table->dropColumn('tipo_ativo');
            }
        });
    }
};
