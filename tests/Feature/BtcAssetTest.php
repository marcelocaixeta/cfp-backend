<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BtcAssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_btc_asset_with_manual_satoshis(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/btc/assets', [
            'rotulo' => 'Carteira principal',
            'quantidade_satoshis' => '123456789.1234567890',
            'preco_medio_compra' => 350000.55,
            'moeda' => 'BRL',
        ])
            ->assertCreated()
            ->assertJsonPath('data.rotulo', 'Carteira principal')
            ->assertJsonPath('data.tipo_ativo', 'BTC')
            ->assertJsonPath('data.quantidade_satoshis', '123456789.1234567890')
            ->assertJsonPath('data.moeda', 'BRL');

        $this->assertDatabaseHas('ativos_btc', [
            'usuario_id' => $user->id,
            'rotulo' => 'Carteira principal',
            'tipo_ativo' => 'BTC',
            'quantidade_satoshis' => '123456789.1234567890',
            'moeda' => 'BRL',
        ]);
    }

    public function test_btc_asset_requires_manual_satoshis_amount(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/btc/assets', [
            'rotulo' => 'Carteira principal',
            'quantidade_btc' => '1.23456789',
            'moeda' => 'BRL',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantidade_satoshis');
    }

    public function test_btc_dashboard_summarizes_satoshis_as_btc(): void
    {
        $user = User::factory()->create();
        $user->btcAssets()->create([
            'rotulo' => 'Carteira principal',
            'quantidade_satoshis' => '150000000.0000000000',
            'moeda' => 'BRL',
        ]);
        $user->btcAssets()->create([
            'rotulo' => 'Carteira reserva',
            'quantidade_satoshis' => '25000000.0000000000',
            'moeda' => 'BRL',
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/btc/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_satoshis', '175000000.0000000000')
            ->assertJsonPath('data.total_btc', '1.75000000');
    }

    public function test_btc_asset_accepts_decimal_precision_up_to_ten_places(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/btc/assets', [
            'rotulo' => 'Ledger',
            'quantidade_satoshis' => '1667367.1234567890',
            'moeda' => 'BRL',
        ])
            ->assertCreated()
            ->assertJsonPath('data.quantidade_satoshis', '1667367.1234567890');
    }

    public function test_authenticated_user_can_create_fixed_income_asset(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/btc/assets', [
            'rotulo' => 'Tesouro Selic',
            'tipo_ativo' => 'Renda Fixa',
            'valor_investido' => '1000.00',
            'valor_atual' => '1035.45',
            'moeda' => 'BRL',
        ])
            ->assertCreated()
            ->assertJsonPath('data.rotulo', 'Tesouro Selic')
            ->assertJsonPath('data.tipo_ativo', 'RENDA_FIXA')
            ->assertJsonPath('data.valor_investido', '1000.00')
            ->assertJsonPath('data.valor_atual', '1035.45')
            ->assertJsonPath('data.quantidade_satoshis', null);

        $this->assertDatabaseHas('ativos_btc', [
            'usuario_id' => $user->id,
            'rotulo' => 'Tesouro Selic',
            'tipo_ativo' => 'RENDA_FIXA',
            'valor_atual' => '1035.45',
        ]);
    }

    public function test_authenticated_user_can_create_variable_income_asset(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/btc/assets', [
            'rotulo' => 'PETR4',
            'tipo_ativo' => 'renda_variavel',
            'valor_investido' => '2500.00',
            'valor_atual' => '2710.20',
            'moeda' => 'BRL',
        ])
            ->assertCreated()
            ->assertJsonPath('data.tipo_ativo', 'RENDA_VARIAVEL')
            ->assertJsonPath('data.valor_atual', '2710.20');
    }

    public function test_non_btc_asset_requires_current_value(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/btc/assets', [
            'rotulo' => 'CDB',
            'tipo_ativo' => 'RENDA_FIXA',
            'moeda' => 'BRL',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('valor_atual');
    }

    public function test_btc_dashboard_ignores_non_btc_assets(): void
    {
        $user = User::factory()->create();
        $user->btcAssets()->create([
            'rotulo' => 'Carteira principal',
            'tipo_ativo' => 'BTC',
            'quantidade_satoshis' => '100000000.0000000000',
            'moeda' => 'BRL',
        ]);
        $user->btcAssets()->create([
            'rotulo' => 'Tesouro Selic',
            'tipo_ativo' => 'RENDA_FIXA',
            'valor_atual' => '5000.00',
            'moeda' => 'BRL',
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/btc/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_satoshis', '100000000.0000000000')
            ->assertJsonPath('data.total_btc', '1.00000000')
            ->assertJsonCount(1, 'data.ativos');
    }
}
