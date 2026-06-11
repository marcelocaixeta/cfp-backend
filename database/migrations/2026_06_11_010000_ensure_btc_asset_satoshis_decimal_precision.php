<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ativos_btc', 'quantidade_satoshis')) {
            return;
        }

        DB::statement('ALTER TABLE ativos_btc ALTER COLUMN quantidade_satoshis TYPE DECIMAL(30, 10) USING quantidade_satoshis::decimal(30, 10)');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ativos_btc', 'quantidade_satoshis')) {
            return;
        }

        DB::statement('ALTER TABLE ativos_btc ALTER COLUMN quantidade_satoshis TYPE BIGINT USING ROUND(quantidade_satoshis)::bigint');
    }
};
