<?php
namespace App\Notifications;

use App\Models\ReservaEquipo;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ReservaDevolucionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $reserva;

    public function __construct(ReservaEquipo $reserva)
    {
        $this->reserva = $reserva->load('user');
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Reserva Devolucion')
            ->greeting('Hola ' . $this->reserva->user->first_name)
            ->line('La reserva ha sido marcada como devuelta.')
            ->line('Detalles:')
            ->line('Aula: ' . $this->reserva->aula)
            ->line('Fecha de reserva: ' . $this->reserva->fecha_reserva)
            ->line('Fecha de entrega: ' . $this->reserva->fecha_entrega)
            ->action('Ver reserva', url('/reservas/' . $this->reserva->id));
    }

    public function toDatabase($notifiable)
    {
        return [
            'mensaje' => 'La reserva ha sido marcada como devuelta',
            'reserva_id' => $this->reserva->id,
        ];
    }
}
