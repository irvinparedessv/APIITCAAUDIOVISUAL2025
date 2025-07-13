<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword as BaseReset;
use Illuminate\Support\Carbon;

class ResetPasswordNotification extends BaseReset
{
    public function toMail($notifiable)
    {
        $expirationMinutes = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');
        $expiresAt = Carbon::now()->addMinutes($expirationMinutes)->timestamp;
        $baseUrl = env('APP_MAIN', 'http://localhost:5173');
        $resetUrl = "{$baseUrl}/reset-password?token={$this->token}&email=" . urlencode($notifiable->getEmailForPasswordReset()) . "&expires={$expiresAt}";
        return (new MailMessage)
            ->subject('Restablecer contraseña')
            ->markdown('emails.reset-password', [
                'url' => $resetUrl,
                'user' => $notifiable,
            ]);
    }
}
