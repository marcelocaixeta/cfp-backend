<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_a_password_reset_link(): void
    {
        Notification::fake();
        config(['app.frontend_password_reset_url' => 'http://localhost:5173/reset-password']);
        $user = User::factory()->create([
            'email' => 'marcelo@example.com',
        ]);

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ])
            ->assertOk()
            ->assertJsonPath('data.message', 'Se o e-mail existir, enviaremos um link para redefinir a senha.');

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use ($user): bool {
                $actionUrl = $notification->toMail($user)->actionUrl;

                return str_starts_with($actionUrl, 'http://localhost:5173/reset-password?')
                    && str_contains($actionUrl, 'token=')
                    && str_contains($actionUrl, 'email=marcelo%40example.com');
            }
        );
    }

    public function test_requesting_a_password_reset_link_does_not_expose_unknown_emails(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'unknown@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('data.message', 'Se o e-mail existir, enviaremos um link para redefinir a senha.');

        Notification::assertNothingSent();
    }

    public function test_user_can_reset_password_with_a_valid_token(): void
    {
        $user = User::factory()->create([
            'senha' => 'old-password',
        ]);
        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'senha' => 'new-password',
            'senha_confirmation' => 'new-password',
        ])
            ->assertOk()
            ->assertJsonPath('data.message', 'Senha redefinida com sucesso.');

        $this->assertTrue(Hash::check('new-password', $user->refresh()->senha));
        $this->assertDatabaseMissing('tokens_redefinicao_senha', [
            'email' => $user->email,
        ]);
    }

    public function test_password_reset_requires_a_valid_token(): void
    {
        $user = User::factory()->create([
            'senha' => 'old-password',
        ]);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'senha' => 'new-password',
            'senha_confirmation' => 'new-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertTrue(Hash::check('old-password', $user->refresh()->senha));
    }
}
