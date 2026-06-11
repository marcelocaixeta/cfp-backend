<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Redefinicao de senha')
            ->greeting('Ola!')
            ->line('Recebemos uma solicitacao para redefinir a senha da sua conta.')
            ->action('Redefinir senha', $this->resetUrl($notifiable->email))
            ->line('Este link expira em 60 minutos.')
            ->line('Se voce nao solicitou a redefinicao, ignore este e-mail.');
    }

    private function resetUrl(string $email): string
    {
        $baseUrl = config('app.frontend_password_reset_url');
        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl.$separator.http_build_query([
            'token' => $this->token,
            'email' => $email,
        ]);
    }
}
