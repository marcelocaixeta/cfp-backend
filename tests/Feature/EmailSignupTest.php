<?php

namespace Tests\Feature;

use App\Models\EmailSignup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailSignupTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_an_email_signup_from_the_registration_popup(): void
    {
        $response = $this->postJson('/api/v1/email-signups', [
            'email' => 'Usuario@Email.com',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.email', 'usuario@email.com')
            ->assertJsonPath('data.origem', 'registration-popup');

        $this->assertDatabaseHas('inscricoes_email', [
            'email' => 'usuario@email.com',
            'origem' => 'registration-popup',
        ]);
    }

    public function test_it_updates_an_existing_email_signup_instead_of_creating_a_duplicate(): void
    {
        EmailSignup::create([
            'email' => 'usuario@email.com',
            'origem' => 'registration-popup',
        ]);

        $response = $this->postJson('/api/v1/email-signups', [
            'email' => 'usuario@email.com',
            'origem' => 'fear-greed-popup',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.origem', 'fear-greed-popup');

        $this->assertDatabaseCount('inscricoes_email', 1);
    }

    public function test_it_requires_a_valid_email(): void
    {
        $response = $this->postJson('/api/v1/email-signups', [
            'email' => 'email-invalido',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }
}
