<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PruebaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contenido;

    public function __construct($contenido)
    {
        $this->contenido = $contenido;
    }

    public function build()
    {
        return $this->subject('Correo de Prueba')
                    ->view('emails.prueba');
    }
}
