<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas_casa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('tipo');
            $table->string('descricao');
            $table->string('fornecedor_nome')->nullable();
            $table->decimal('valor', 14, 2);
            $table->date('data_vencimento');
            $table->date('mes_referencia')->nullable();
            $table->string('situacao')->default('pending');
            $table->text('observacoes')->nullable();
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->softDeletes('excluido_em');

            $table->index(['usuario_id', 'tipo']);
            $table->index(['usuario_id', 'situacao']);
            $table->index(['usuario_id', 'data_vencimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_casa');
    }
};
