<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/** El correo de restablecer contrasena, en ruso. */
class RestablecerContrasena extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('holaprivet · новый пароль')
            ->greeting('¡Hola!')
            ->line('Кто-то (надеемся, что вы) попросил сменить пароль на holaprivet.com.')
            ->action('Придумать новый пароль', $url)
            ->line('Ссылка действует 60 минут. Если это были не вы — ничего не делайте, пароль останется прежним.')
            ->salutation('Hasta pronto · holaprivet');
    }
}
