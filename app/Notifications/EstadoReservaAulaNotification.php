<?php

namespace App\Notifications;

use App\Models\ReservaAula;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;

class EstadoReservaAulaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $reserva;

    public function __construct(ReservaAula $reserva)
    {
        $this->reserva = $reserva->load(['user', 'aula']);
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable)
    {
        return new DatabaseMessage([
            'title' => 'Estado de tu reserva de aula actualizado',
            'message' => "Tu reserva para el aula {$this->reserva->aula->nombre} ha sido marcada como '{$this->reserva->estado}'.",
            'reserva_id' => $this->reserva->id,
            'estado' => $this->reserva->estado,
            'comentario' => $this->reserva->comentario,
        ]);
    }

    public function toMail($notifiable)
    {
        $estadoTraducido = match($this->reserva->estado) {
            'approved' => 'aprobada',
            'rejected' => 'rechazada',
            'returned' => 'devuelta',
            default => $this->reserva->estado
        };

        return (new MailMessage)
            ->subject('Estado de tu reserva de aula')
            ->greeting('Hola ' . $this->reserva->user->first_name)
            ->line("Tu reserva ha sido {$estadoTraducido}.")
            ->line('Detalles de la reserva:')
            ->line('Aula: ' . $this->reserva->aula->nombre)
            ->line('Fecha: ' . $this->reserva->fecha)
            ->line('Horario: ' . $this->reserva->horario)
            ->action('Ver reserva', url('/reservas-aulas/' . $this->reserva->id));
    }
}
