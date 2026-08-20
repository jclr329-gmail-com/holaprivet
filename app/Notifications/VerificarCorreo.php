<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/** El correo de verificacion, en ruso y con la voz del curso. */
class VerificarCorreo extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('holaprivet · подтвердите почту')
            ->greeting('¡Hola!')
            ->line('Остался один шаг: подтвердите свою почту, и ваш прогресс в курсе будет сохраняться в аккаунте — на любом устройстве.')
            ->action('Подтвердить почту', $url)
            ->line('Если вы не создавали аккаунт на holaprivet.com, просто удалите это письмо.')
            ->salutation('Hasta pronto · holaprivet');
    }
}
