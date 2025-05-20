<?php

namespace App\Notifications;

use App\Models\ReservaEquipo;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

class EstadoReservaNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected $reserva;

     // ✅ sin tipo
    public $id;

    public function __construct(ReservaEquipo $reserva)
    {
        $this->reserva = $reserva->load('user');
        $this->id = Str::uuid()->toString(); // <- Laravel lo requiere para broadcasting
    }

    public function via($notifiable)
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Estado de tu reserva actualizado',
            'message' => "Tu reserva para el aula {$this->reserva->aula} ha sido marcada como '{$this->reserva->estado}'.",
            'reserva' => [
                'id' => $this->reserva->id,
                'aula' => $this->reserva->aula,
                'fecha_reserva' => $this->reserva->fecha_reserva,
                'fecha_entrega' => $this->reserva->fecha_entrega,
            ],
            'estado' => $this->reserva->estado,
            'comentario' => $this->reserva->comentario,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function broadcastOn()
    {
        return new PrivateChannel("notifications.user.{$this->reserva->user->id}");
    }

    public function broadcastAs()
    {
        return 'reserva.estado.actualizado';
    }

    public function toMail($notifiable)
    {

    }
}
