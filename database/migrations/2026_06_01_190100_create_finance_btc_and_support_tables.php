<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_financeiras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->cascadeOnDelete();
            $table->string('nome');
            $table->string('tipo');
            $table->string('cor', 16)->nullable();
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();

            $table->index(['usuario_id', 'tipo']);
        });

        Schema::create('cartoes_credito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('nome');
            $table->char('ultimos_quatro_digitos', 4)->nullable();
            $table->string('bandeira')->nullable();
            $table->decimal('limite_valor', 14, 2)->nullable();
            $table->unsignedTinyInteger('dia_fechamento')->nullable();
            $table->unsignedTinyInteger('dia_vencimento')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->softDeletes('excluido_em');

            $table->index('usuario_id');
        });

        Schema::create('dividas_cartao_credito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('cartao_credito_id')->nullable()->constrained('cartoes_credito')->nullOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_financeiras')->nullOnDelete();
            $table->string('descricao');
            $table->decimal('valor_total', 14, 2);
            $table->unsignedSmallInteger('quantidade_parcelas')->default(1);
            $table->unsignedSmallInteger('parcela_atual')->default(1);
            $table->decimal('valor_parcela', 14, 2);
            $table->date('primeira_data_vencimento');
            $table->string('situacao')->default('pending');
            $table->text('observacoes')->nullable();
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->softDeletes('excluido_em');

            $table->index(['usuario_id', 'situacao']);
            $table->index(['usuario_id', 'primeira_data_vencimento']);
        });

        Schema::create('emprestimos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_financeiras')->nullOnDelete();
            $table->string('credor_nome');
            $table->string('descricao')->nullable();
            $table->decimal('valor_principal', 14, 2);
            $table->decimal('taxa_juros', 8, 4)->nullable();
            $table->string('periodo_taxa_juros')->nullable();
            $table->unsignedSmallInteger('quantidade_parcelas');
            $table->decimal('valor_parcela', 14, 2);
            $table->date('primeira_data_vencimento');
            $table->string('situacao')->default('active');
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->softDeletes('excluido_em');

            $table->index(['usuario_id', 'situacao']);
            $table->index(['usuario_id', 'primeira_data_vencimento']);
        });

        Schema::create('parcelas_emprestimos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emprestimo_id')->constrained('emprestimos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero_parcela');
            $table->date('data_vencimento');
            $table->decimal('valor', 14, 2);
            $table->timestamp('pago_em')->nullable();
            $table->string('situacao')->default('pending');
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();

            $table->unique(['emprestimo_id', 'numero_parcela']);
            $table->index(['usuario_id', 'situacao']);
            $table->index(['usuario_id', 'data_vencimento']);
        });

        Schema::create('ativos_btc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('rotulo');
            $table->decimal('quantidade_satoshis', 30, 10);
            $table->decimal('preco_medio_compra', 18, 2)->nullable();
            $table->char('moeda', 3)->default('BRL');
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->softDeletes('excluido_em');

            $table->index('usuario_id');
        });

        Schema::create('capturas_precos_btc', function (Blueprint $table) {
            $table->id();
            $table->string('provedor');
            $table->char('moeda', 3)->default('BRL');
            $table->decimal('preco', 18, 2);
            $table->timestamp('capturado_em');
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();

            $table->index(['moeda', 'capturado_em']);
        });

        Schema::create('configuracoes_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->unique()->constrained('usuarios')->cascadeOnDelete();
            $table->char('moeda_padrao', 3)->default('BRL');
            $table->string('fuso_horario')->default('America/Sao_Paulo');
            $table->jsonb('preferencias_dashboard')->nullable();
            $table->jsonb('preferencias_notificacao')->nullable();
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
        });

        Schema::create('chamados_suporte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('assunto');
            $table->string('categoria')->nullable();
            $table->string('prioridade')->default('normal');
            $table->string('situacao')->default('open');
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();

            $table->index(['usuario_id', 'situacao']);
        });

        Schema::create('mensagens_chamados_suporte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamado_id')->constrained('chamados_suporte')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->text('mensagem');
            $table->boolean('interno')->default(false);
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();

            $table->index(['chamado_id', 'criado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensagens_chamados_suporte');
        Schema::dropIfExists('chamados_suporte');
        Schema::dropIfExists('configuracoes_usuario');
        Schema::dropIfExists('capturas_precos_btc');
        Schema::dropIfExists('ativos_btc');
        Schema::dropIfExists('parcelas_emprestimos');
        Schema::dropIfExists('emprestimos');
        Schema::dropIfExists('dividas_cartao_credito');
        Schema::dropIfExists('cartoes_credito');
        Schema::dropIfExists('categorias_financeiras');
    }
};
