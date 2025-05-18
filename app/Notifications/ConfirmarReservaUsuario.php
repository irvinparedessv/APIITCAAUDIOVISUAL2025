<?php

namespace App\Notifications;

use App\Models\ReservaEquipo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ConfirmarReservaUsuario extends Notification implements ShouldQueue
{
    use Queueable;

    public $reserva;

    public function __construct(ReservaEquipo $reserva)
    {
        $this->reserva = $reserva->load('equipos');
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Confirmación de tu Reserva')
            ->markdown('emails.reserva_usuario', [
                'reserva' => $this->reserva
            ]);
    }

}
