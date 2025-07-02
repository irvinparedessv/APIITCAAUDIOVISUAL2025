<?php

namespace App\Notifications;

use App\Models\ReservaEquipo;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Log;

class NuevaReservaNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    public $reserva;
    public $notifiableId;
    public $pagina;
    public $creadorId; // 👈 NUEVO

    public function __construct(ReservaEquipo $reserva, $notifiableId, $pagina = 1, $creadorId = null)
    {
        $this->reserva = $reserva->load(['user', 'equipos.tipoEquipo', 'aula', 'tipoReserva']);
        $this->notifiableId = $notifiableId;
        $this->pagina = $pagina;
        $this->creadorId = $creadorId;
    }

    public function via($notifiable)
    {
        Log::info('Método via() ejecutado para notificaciones');
        return ['database', 'broadcast'];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->broadcastWith());
    }

    public function toDatabase($notifiable)
    {
        $usuarioNombre = $this->reserva->user
            ? $this->reserva->user->first_name . ' ' . $this->reserva->user->last_name
            : 'Usuario desconocido';

        $esCreadorAdmin = $this->creadorId && $this->creadorId !== $this->reserva->user->id;

        return [
            'type' => 'nueva_reserva',
            'title' => $esCreadorAdmin
                ? 'Reserva de equipo realizada por administración'
                : 'Nueva reserva de equipo recibida',
            'message' => $esCreadorAdmin
                ? "Nueva reserva realizada por administración."
                : "Nueva reserva de equipo recibida del usuario {$usuarioNombre}.",
            'reserva' => [
                'id' => $this->reserva->id,
                'pagina' => $this->pagina,
                'user' => $usuarioNombre,
                'aula' => $this->reserva->aula?->nombre ?? $this->reserva->aula,
                'fecha_reserva' => $this->reserva->fecha_reserva,
                'fecha_entrega' => $this->reserva->fecha_entrega,
                'estado' => $this->reserva->estado,
                'tipo_reserva' => $this->reserva->tipoReserva?->nombre,
                'equipos' => $this->reserva->equipos->map(function ($equipo) {
                    return [
                        'nombre' => $equipo->nombre,
                        'tipo_equipo' => $equipo->tipoEquipo?->nombre,
                    ];
                }),
            ]
        ];
    }


    public function broadcastOn()
    {
        return [new PrivateChannel('notifications.user.' . $this->notifiableId)];
    }

    public function broadcastAs()
    {
        return 'nueva.reserva';
    }

    public function broadcastWith()
    {
        $usuarioNombre = $this->reserva->user
            ? $this->reserva->user->first_name . ' ' . $this->reserva->user->last_name
            : 'Usuario desconocido';

        $esCreadorAdmin = $this->creadorId && $this->creadorId !== $this->reserva->user->id;

        return [
            'title' => $esCreadorAdmin
                ? 'Reserva de equipo realizada por administración'
                : 'Nueva reserva de equipo recibida',
            'message' => $esCreadorAdmin
                ? "Nueva reserva realizada por administración."
                : "Nueva reserva de equipo recibida del usuario {$usuarioNombre}.",
            'reserva' => [
                'id' => $this->reserva->id,
                'pagina' => $this->pagina,
                'user' => $usuarioNombre,
                'aula' => $this->reserva->aula?->nombre ?? $this->reserva->aula,
                'fecha_reserva' => $this->reserva->fecha_reserva,
                'fecha_entrega' => $this->reserva->fecha_entrega,
                'estado' => $this->reserva->estado,
                'tipo_reserva' => $this->reserva->tipoReserva?->nombre,
                'equipos' => $this->reserva->equipos->map(function ($equipo) {
                    return [
                        'nombre' => $equipo->nombre,
                        'tipo_equipo' => $equipo->tipoEquipo?->nombre,
                    ];
                }),
            ]
        ];
    }
}
