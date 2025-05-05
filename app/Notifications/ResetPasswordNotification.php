<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword as BaseReset;
use Illuminate\Support\Carbon;

class ResetPasswordNotification extends BaseReset
{
    public function toMail($notifiable)
    {
        $expirationMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');
        $expiresAt = Carbon::now()->addMinutes($expirationMinutes)->timestamp;
        
        $resetUrl = url("http://localhost:5173/reset-password?token={$this->token}&email={$notifiable->getEmailForPasswordReset()}&expires={$expiresAt}");
        return (new MailMessage)
            ->subject('Restablecer contraseña')
            ->markdown('emails.reset-password', [
                'url' => $resetUrl,
                'user' => $notifiable,
            ]);
    }
}
