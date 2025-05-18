<?php

namespace App\Notifications;

use App\Models\ReservaEquipo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class EstadoReservaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $reserva;

    public function __construct(ReservaEquipo $reserva)
    {
        $this->reserva = $reserva;
    }

    public function via($notifiable)
    {
        return ['database']; // Puedes agregar 'mail' si también quieres correo
    }

    public function toDatabase($notifiable)
    {
        return new DatabaseMessage([
            'title' => 'Estado de tu reserva actualizado',
            'message' => "Tu reserva para el aula {$this->reserva->aula} ha sido marcada como '{$this->reserva->estado}'.",
            'reserva_id' => $this->reserva->id,
            'estado' => $this->reserva->estado,
            'comentario' => $this->reserva->comentario,
        ]);
    }
}
