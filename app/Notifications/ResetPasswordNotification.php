<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword as BaseReset;

class ResetPasswordNotification extends BaseReset
{
    public function toMail($notifiable)
    {
        $resetUrl = url("http://localhost:5173/reset-password?token={$this->token}&email={$notifiable->getEmailForPasswordReset()}");

        return (new MailMessage)
            ->subject('Restablecer contraseña')
            ->markdown('emails.reset-password', [
                'url' => $resetUrl,
                'user' => $notifiable,
            ]);
    }
}
