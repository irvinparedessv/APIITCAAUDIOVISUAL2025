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
                'aula' => $this->reserva->aula ? [
                    'id' => $this->reserva->aula->id,
                    'name' => $this->reserva->aula->name,
                    'path_modelo' => $this->reserva->aula->path_modelo,
                    'capacidad_maxima' => $this->reserva->aula->capacidad_maxima,
                    'descripcion' => $this->reserva->aula->descripcion,
                    'escala' => $this->reserva->aula->escala,
                ] : null,
                'fecha_reserva' => $this->reserva->fecha_reserva->toDateTimeString(),
                'fecha_entrega' => $this->reserva->fecha_entrega->toDateTimeString(),
                'estado' => $this->reserva->estado,
                'tipo_reserva' => $this->reserva->tipoReserva?->nombre,
                'equipos' => $this->reserva->equipos->map(function ($equipo) {
                    return [
                        'id' => $equipo->id,
                        'numero_serie' => $equipo->numero_serie,
                        'tipo_equipo' => $equipo->tipoEquipo?->nombre,
                        'modelo' => $equipo->modelo?->nombre,
                    ];
                })->toArray(),
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
                'aula' => $this->reserva->aula ? [
                    'id' => $this->reserva->aula->id,
                    'name' => $this->reserva->aula->name,
                    'path_modelo' => $this->reserva->aula->path_modelo,
                    'capacidad_maxima' => $this->reserva->aula->capacidad_maxima,
                    'descripcion' => $this->reserva->aula->descripcion,
                    'escala' => $this->reserva->aula->escala,
                ] : null,
                'fecha_reserva' => $this->reserva->fecha_reserva->toDateTimeString(),
                'fecha_entrega' => $this->reserva->fecha_entrega->toDateTimeString(),
                'estado' => $this->reserva->estado,
                'tipo_reserva' => $this->reserva->tipoReserva?->nombre,
                'equipos' => $this->reserva->equipos->map(function ($equipo) {
                    return [
                        'id' => $equipo->id,
                        'numero_serie' => $equipo->numero_serie,
                        'tipo_equipo' => $equipo->tipoEquipo?->nombre,
                        'modelo' => $equipo->modelo?->nombre,
                    ];
                })->toArray(),
            ]
        ];
    }
}
