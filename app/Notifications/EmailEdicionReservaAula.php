<?php

namespace App\Notifications;

use App\Models\ReservaAula;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class EmailEdicionReservaAula extends Notification implements ShouldQueue
{
    use Queueable;

    public ReservaAula $reserva;

    public function __construct(ReservaAula $reserva)
    {
        $this->reserva = $reserva->load('aula', 'user');
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $esResponsable = in_array(strtolower($notifiable->role->nombre), ['administrador', 'encargado']);
        $subject = $esResponsable
                ? 'Reserva de aula modificada (notificación administrativa)'
                : 'Tu reserva de aula ha sido modificada';

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.edicion_reserva_aula', [
                'reserva' => $this->reserva,
                'usuario' => $notifiable,
                'esResponsable' => $esResponsable,
            ]);
    }
}
