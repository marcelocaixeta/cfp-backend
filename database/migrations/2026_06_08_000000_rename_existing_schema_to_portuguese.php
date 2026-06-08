<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameTables([
            'users' => 'usuarios',
            'password_reset_tokens' => 'tokens_redefinicao_senha',
            'sessions' => 'sessoes',
            'cache_locks' => 'bloqueios_cache',
            'jobs' => 'trabalhos',
            'job_batches' => 'lotes_trabalhos',
            'failed_jobs' => 'trabalhos_falhos',
            'personal_access_tokens' => 'tokens_acesso_pessoal',
            'email_signups' => 'inscricoes_email',
            'finance_categories' => 'categorias_financeiras',
            'credit_cards' => 'cartoes_credito',
            'credit_card_debts' => 'dividas_cartao_credito',
            'loans' => 'emprestimos',
            'loan_installments' => 'parcelas_emprestimos',
            'btc_assets' => 'ativos_btc',
            'btc_price_snapshots' => 'capturas_precos_btc',
            'user_settings' => 'configuracoes_usuario',
            'support_tickets' => 'chamados_suporte',
            'support_ticket_messages' => 'mensagens_chamados_suporte',
        ]);

        $this->renameColumns('usuarios', [
            'name' => 'nome',
            'email_verified_at' => 'email_verificado_em',
            'password' => 'senha',
            'remember_token' => 'lembrar_token',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
        ]);

        $this->renameColumns('inscricoes_email', [
            'source' => 'origem',
            'ip_address' => 'endereco_ip',
            'user_agent' => 'agente_usuario',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
        ]);

        $this->renameColumns('categorias_financeiras', [
            'user_id' => 'usuario_id',
            'name' => 'nome',
            'type' => 'tipo',
            'color' => 'cor',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
        ]);

        $this->renameColumns('cartoes_credito', [
            'user_id' => 'usuario_id',
            'name' => 'nome',
            'last_four_digits' => 'ultimos_quatro_digitos',
            'brand' => 'bandeira',
            'limit_amount' => 'limite_valor',
            'closing_day' => 'dia_fechamento',
            'due_day' => 'dia_vencimento',
            'is_active' => 'ativo',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
            'deleted_at' => 'excluido_em',
        ]);

        $this->renameColumns('dividas_cartao_credito', [
            'user_id' => 'usuario_id',
            'credit_card_id' => 'cartao_credito_id',
            'category_id' => 'categoria_id',
            'description' => 'descricao',
            'total_amount' => 'valor_total',
            'installment_count' => 'quantidade_parcelas',
            'current_installment' => 'parcela_atual',
            'installment_amount' => 'valor_parcela',
            'first_due_date' => 'primeira_data_vencimento',
            'status' => 'situacao',
            'notes' => 'observacoes',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
            'deleted_at' => 'excluido_em',
        ]);

        $this->renameColumns('emprestimos', [
            'user_id' => 'usuario_id',
            'category_id' => 'categoria_id',
            'lender_name' => 'credor_nome',
            'description' => 'descricao',
            'principal_amount' => 'valor_principal',
            'interest_rate' => 'taxa_juros',
            'interest_rate_period' => 'periodo_taxa_juros',
            'installment_count' => 'quantidade_parcelas',
            'installment_amount' => 'valor_parcela',
            'first_due_date' => 'primeira_data_vencimento',
            'status' => 'situacao',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
            'deleted_at' => 'excluido_em',
        ]);

        $this->renameColumns('parcelas_emprestimos', [
            'loan_id' => 'emprestimo_id',
            'user_id' => 'usuario_id',
            'installment_number' => 'numero_parcela',
            'due_date' => 'data_vencimento',
            'amount' => 'valor',
            'paid_at' => 'pago_em',
            'status' => 'situacao',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
        ]);

        $this->renameColumns('ativos_btc', [
            'user_id' => 'usuario_id',
            'label' => 'rotulo',
            'amount_btc' => 'quantidade_btc',
            'average_buy_price' => 'preco_medio_compra',
            'currency' => 'moeda',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
            'deleted_at' => 'excluido_em',
        ]);

        $this->renameColumns('capturas_precos_btc', [
            'provider' => 'provedor',
            'currency' => 'moeda',
            'price' => 'preco',
            'captured_at' => 'capturado_em',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
        ]);

        $this->renameColumns('configuracoes_usuario', [
            'user_id' => 'usuario_id',
            'default_currency' => 'moeda_padrao',
            'timezone' => 'fuso_horario',
            'dashboard_preferences' => 'preferencias_dashboard',
            'notification_preferences' => 'preferencias_notificacao',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
        ]);

        $this->renameColumns('chamados_suporte', [
            'user_id' => 'usuario_id',
            'subject' => 'assunto',
            'category' => 'categoria',
            'priority' => 'prioridade',
            'status' => 'situacao',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
        ]);

        $this->renameColumns('mensagens_chamados_suporte', [
            'ticket_id' => 'chamado_id',
            'user_id' => 'usuario_id',
            'message' => 'mensagem',
            'is_internal' => 'interno',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
        ]);
    }

    public function down(): void
    {
        $this->renameColumns('mensagens_chamados_suporte', array_flip([
            'ticket_id' => 'chamado_id',
            'user_id' => 'usuario_id',
            'message' => 'mensagem',
            'is_internal' => 'interno',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
        ]));

        $this->renameColumns('chamados_suporte', array_flip([
            'user_id' => 'usuario_id',
            'subject' => 'assunto',
            'category' => 'categoria',
            'priority' => 'prioridade',
            'status' => 'situacao',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
        ]));

        $this->renameColumns('configuracoes_usuario', array_flip([
            'user_id' => 'usuario_id',
            'default_currency' => 'moeda_padrao',
            'timezone' => 'fuso_horario',
            'dashboard_preferences' => 'preferencias_dashboard',
            'notification_preferences' => 'preferencias_notificacao',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
        ]));

        $this->renameColumns('capturas_precos_btc', array_flip([
            'provider' => 'provedor',
            'currency' => 'moeda',
            'price' => 'preco',
            'captured_at' => 'capturado_em',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
        ]));

        $this->renameColumns('ativos_btc', array_flip([
            'user_id' => 'usuario_id',
            'label' => 'rotulo',
            'amount_btc' => 'quantidade_btc',
            'average_buy_price' => 'preco_medio_compra',
            'currency' => 'moeda',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
            'deleted_at' => 'excluido_em',
        ]));

        $this->renameColumns('parcelas_emprestimos', array_flip([
            'loan_id' => 'emprestimo_id',
            'user_id' => 'usuario_id',
            'installment_number' => 'numero_parcela',
            'due_date' => 'data_vencimento',
            'amount' => 'valor',
            'paid_at' => 'pago_em',
            'status' => 'situacao',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
        ]));

        $this->renameColumns('emprestimos', array_flip([
            'user_id' => 'usuario_id',
            'category_id' => 'categoria_id',
            'lender_name' => 'credor_nome',
            'description' => 'descricao',
            'principal_amount' => 'valor_principal',
            'interest_rate' => 'taxa_juros',
            'interest_rate_period' => 'periodo_taxa_juros',
            'installment_count' => 'quantidade_parcelas',
            'installment_amount' => 'valor_parcela',
            'first_due_date' => 'primeira_data_vencimento',
            'status' => 'situacao',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
            'deleted_at' => 'excluido_em',
        ]));

        $this->renameColumns('dividas_cartao_credito', array_flip([
            'user_id' => 'usuario_id',
            'credit_card_id' => 'cartao_credito_id',
            'category_id' => 'categoria_id',
            'description' => 'descricao',
            'total_amount' => 'valor_total',
            'installment_count' => 'quantidade_parcelas',
            'current_installment' => 'parcela_atual',
            'installment_amount' => 'valor_parcela',
            'first_due_date' => 'primeira_data_vencimento',
            'status' => 'situacao',
            'notes' => 'observacoes',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
            'deleted_at' => 'excluido_em',
        ]));

        $this->renameColumns('cartoes_credito', array_flip([
            'user_id' => 'usuario_id',
            'name' => 'nome',
            'last_four_digits' => 'ultimos_quatro_digitos',
            'brand' => 'bandeira',
            'limit_amount' => 'limite_valor',
            'closing_day' => 'dia_fechamento',
            'due_day' => 'dia_vencimento',
            'is_active' => 'ativo',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
            'deleted_at' => 'excluido_em',
        ]));

        $this->renameColumns('categorias_financeiras', array_flip([
            'user_id' => 'usuario_id',
            'name' => 'nome',
            'type' => 'tipo',
            'color' => 'cor',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
        ]));

        $this->renameColumns('inscricoes_email', array_flip([
            'source' => 'origem',
            'ip_address' => 'endereco_ip',
            'user_agent' => 'agente_usuario',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
        ]));

        $this->renameColumns('usuarios', array_flip([
            'name' => 'nome',
            'email_verified_at' => 'email_verificado_em',
            'password' => 'senha',
            'remember_token' => 'lembrar_token',
            'created_at' => 'criado_em',
            'updated_at' => 'atualizado_em',
        ]));

        $this->renameTables(array_flip([
            'users' => 'usuarios',
            'password_reset_tokens' => 'tokens_redefinicao_senha',
            'sessions' => 'sessoes',
            'cache_locks' => 'bloqueios_cache',
            'jobs' => 'trabalhos',
            'job_batches' => 'lotes_trabalhos',
            'failed_jobs' => 'trabalhos_falhos',
            'personal_access_tokens' => 'tokens_acesso_pessoal',
            'email_signups' => 'inscricoes_email',
            'finance_categories' => 'categorias_financeiras',
            'credit_cards' => 'cartoes_credito',
            'credit_card_debts' => 'dividas_cartao_credito',
            'loans' => 'emprestimos',
            'loan_installments' => 'parcelas_emprestimos',
            'btc_assets' => 'ativos_btc',
            'btc_price_snapshots' => 'capturas_precos_btc',
            'user_settings' => 'configuracoes_usuario',
            'support_tickets' => 'chamados_suporte',
            'support_ticket_messages' => 'mensagens_chamados_suporte',
        ]));
    }

    /**
     * @param  array<string, string>  $tables
     */
    private function renameTables(array $tables): void
    {
        foreach ($tables as $from => $to) {
            if (Schema::hasTable($from) && ! Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }
        }
    }

    /**
     * @param  array<string, string>  $columns
     */
    private function renameColumns(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $from => $to) {
            if (Schema::hasColumn($table, $from) && ! Schema::hasColumn($table, $to)) {
                Schema::table($table, function (Blueprint $schema) use ($from, $to): void {
                    $schema->renameColumn($from, $to);
                });
            }
        }
    }
};
