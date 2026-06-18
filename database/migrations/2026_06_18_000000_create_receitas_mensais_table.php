<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receitas_mensais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_financeiras')->nullOnDelete();
            $table->string('descricao');
            $table->decimal('valor', 14, 2);
            $table->date('data_recebimento');
            $table->boolean('recorrente')->default(true);
            $table->string('tipo_receita');
            $table->text('observacoes')->nullable();
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->softDeletes('excluido_em');

            $table->index(['usuario_id', 'data_recebimento']);
            $table->index(['usuario_id', 'tipo_receita']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receitas_mensais');
    }
};
