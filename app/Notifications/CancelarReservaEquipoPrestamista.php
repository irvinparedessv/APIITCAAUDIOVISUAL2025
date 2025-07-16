<?php

namespace App\Notifications;

use App\Models\ReservaEquipo;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Str;

class CancelarReservaEquipoPrestamista extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected $reserva;
    protected $prestamistaId;
    protected $pagina; // ✅ NUEVO
    public $id;

    public function __construct(ReservaEquipo $reserva, $prestamistaId, $pagina = 1)
    {
        $this->reserva = $reserva->load(['user', 'equipos.tipoEquipo', 'tipoReserva']);
        $this->prestamistaId = $prestamistaId;
        $this->pagina = $pagina; // ✅ NUEVO
        $this->id = (string) Str::uuid();
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'cancelacion_reserva',
            'title' => 'Se ha cancelado una reserva de equipo',
            'message' => "Una reserva fue cancelada por {$this->reserva->user->first_name} {$this->reserva->user->last_name}.",
            'reserva' => [
                'id' => $this->reserva->id,
                'pagina' => $this->pagina, // ✅ NUEVO
                'aula' => $this->reserva->aula,
                'tipo_reserva' => $this->reserva->tipoReserva?->nombre,
                'usuario' => $this->reserva->user?->first_name . ' ' . $this->reserva->user?->last_name,
                'fecha_reserva' => $this->reserva->fecha_reserva,
                'fecha_entrega' => $this->reserva->fecha_entrega,
                'equipos' => $this->reserva->equipos->map(function ($equipo) {
                    return [
                        'nombre' => $equipo->nombre,
                        'tipo_equipo' => $equipo->tipoEquipo?->nombre,
                    ];
                })->toArray(),
                'comentario' => $this->reserva->comentario,
            ],
            'prestamista_id' => $this->prestamistaId,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function broadcastOn()
    {
        return new PrivateChannel("notifications.user.{$this->prestamistaId}");
    }

    public function broadcastAs()
    {
        return 'reserva.cancelada';
    }
}
