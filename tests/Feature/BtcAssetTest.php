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
            ->assertJsonPath('data.quantidade_satoshis', '123456789.1234567890')
            ->assertJsonPath('data.moeda', 'BRL');

        $this->assertDatabaseHas('ativos_btc', [
            'usuario_id' => $user->id,
            'rotulo' => 'Carteira principal',
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
}
