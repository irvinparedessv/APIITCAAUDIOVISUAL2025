<?php

namespace App\Notifications;

use App\Models\ReservaEquipo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;

class EstadoReservaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $reserva;

    public function __construct(ReservaEquipo $reserva)
    {
        $this->reserva = $reserva->load('user');
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
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

    public function toMail($notifiable)
    {
        $estadoTraducido = match($this->reserva->estado) {
            'approved' => 'aprobada',
            'rejected' => 'rechazada',
            'returned' => 'devuelta',
            default => $this->reserva->estado
        };

        return (new MailMessage)
            ->subject('Estado de tu reserva')
            ->greeting('Hola ' . $this->reserva->user->first_name)
            ->line("Tu reserva ha sido {$estadoTraducido}.")
            ->line('Detalles:')
            ->line('Aula: ' . $this->reserva->aula)
            ->line('Fecha de reserva: ' . $this->reserva->fecha_reserva)
            ->line('Fecha de entrega: ' . $this->reserva->fecha_entrega)
            ->action('Ver reserva', url('/reservas/' . $this->reserva->id));
    }
}
