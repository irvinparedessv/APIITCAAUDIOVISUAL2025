<?php

namespace App\Notifications;

use App\Models\ReservaAula;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class EmailEstadoAulaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $reserva;

    public function __construct(ReservaAula $reserva)
    {
        $this->reserva = $reserva;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Estado de reserva de aula')
            ->markdown('emails.reserva_estado_aula', [
                'reserva' => $this->reserva,
                'usuario' => $notifiable
            ]);
    }
}
