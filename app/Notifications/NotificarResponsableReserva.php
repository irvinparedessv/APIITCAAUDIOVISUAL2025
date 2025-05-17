<?php

namespace App\Notifications;

use App\Models\ReservaEquipo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NotificarResponsableReserva extends Notification implements ShouldQueue
{
    use Queueable;

    public $reserva;

    public function __construct(ReservaEquipo $reserva)
    {
        $this->reserva = $reserva->load('user', 'equipos');
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Nueva Reserva Recibida')
            ->view('emails.reserva_encargado', [
                'reserva' => $this->reserva
            ]);
    }
}
