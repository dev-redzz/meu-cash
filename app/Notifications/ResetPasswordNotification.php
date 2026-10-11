<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    public function __construct(public readonly string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
        $minutes = config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Redefinir sua senha · Meu Cash')
            ->greeting('Olá, '.str($notifiable->name)->explode(' ')->first().'!')
            ->line('Recebemos um pedido para criar ou redefinir a senha da sua conta no Meu Cash.')
            ->action('Definir nova senha', $url)
            ->line("Este link vale por {$minutes} minutos.")
            ->line('Se você não pediu isso, ignore este e-mail. Sua senha continua a mesma.')
            ->salutation('Equipe Meu Cash');
    }
}
