<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $tempPassword;
    public $confirmationUrl;

    public function __construct($user, $tempPassword, $confirmationUrl)
    {
        $this->user = $user;
        $this->tempPassword = $tempPassword;
        $this->confirmationUrl = $confirmationUrl;
    }

    // En ConfirmAccountMail.php
    // App/Mail/ConfirmAccountMail.php
public function build()
{
    return $this->markdown('emails.confirm_account')
        ->subject('Confirma tu cuenta')
        ->with([
            'user' => $this->user,
            'password' => $this->tempPassword,
            'confirmationUrl' => $this->confirmationUrl,
        ]);
}
}
