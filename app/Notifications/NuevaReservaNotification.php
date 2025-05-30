<?php
// app/Notifications/NuevaReservaNotification.php

namespace App\Notifications;

use App\Models\ReservaEquipo;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Log;

class NuevaReservaNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    public $reserva;
    public $notifiableId;

    public function __construct(ReservaEquipo $reserva, $notifiableId)
    {
        $this->reserva = $reserva;
        $this->reserva->load(['user', 'equipos.tipoEquipo', 'aula', 'tipoReserva']); 
        $this->notifiableId = $notifiableId;
    }


    public function via($notifiable)
    {
        Log::info('Método via() ejecutado para notificaciones');
        return ['database', 'broadcast']; 
    }

    public function toBroadcast($notifiable)
    {
        Log::info('User Details: ', [
            'user' => $this->reserva->user,
            'first_name' => $this->reserva->user ? $this->reserva->user->first_name : null,
            'last_name' => $this->reserva->user ? $this->reserva->user->last_name : null,
        ]);

        return new BroadcastMessage([
            'reserva' => [
                'id' => $this->reserva->id,
                'user' => $this->reserva->user ? $this->reserva->user->first_name . ' ' . $this->reserva->user->last_name : null,
                'aula' => $this->reserva->aula,
                'fecha_reserva' => $this->reserva->fecha_reserva,
                'fecha_entrega' => $this->reserva->fecha_entrega,
                'estado' => $this->reserva->estado,
                'tipo_reserva' => $this->reserva->tipoReserva ? $this->reserva->tipoReserva->nombre : null, // Añadido tipo de reserva
            ]
        ]);
    }

    public function toDatabase($notifiable)
    {
        $usuarioNombre = $this->reserva->user
            ? $this->reserva->user->first_name . ' ' . $this->reserva->user->last_name
            : 'Usuario desconocido';

        return [
            'type' => 'nueva_reserva',
            'title' => 'Nueva reserva de equipo recibida',
            'message' => "Nueva reserva recibida del usuario {$usuarioNombre}.",
            'reserva' => [  // Cambiado a objeto 'reserva' para consistencia
                'id' => $this->reserva->id,
                'user' => $usuarioNombre,
                'aula' => $this->reserva->aula?->nombre ?? $this->reserva->aula, 
                'fecha_reserva' => $this->reserva->fecha_reserva,
                'fecha_entrega' => $this->reserva->fecha_entrega,
                'estado' => $this->reserva->estado,
                'tipo_reserva' => $this->reserva->tipoReserva ? $this->reserva->tipoReserva->nombre : null,
                'equipos' => $this->reserva->equipos->map(function($equipo) {
                    return [
                        'nombre' => $equipo->nombre,
                        'tipo_equipo' => $equipo->tipoEquipo ? $equipo->tipoEquipo->nombre : null, 
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

        return [
            'reserva' => [
                'id' => $this->reserva->id,
                'user' => $usuarioNombre,
                'aula' => $this->reserva->aula?->nombre ?? $this->reserva->aula,
                'fecha_reserva' => $this->reserva->fecha_reserva,
                'fecha_entrega' => $this->reserva->fecha_entrega,
                'estado' => $this->reserva->estado,
                'tipo_reserva' => $this->reserva->tipoReserva ? $this->reserva->tipoReserva->nombre : null,
                'equipos' => $this->reserva->equipos->map(function($equipo) {
                    return [
                        'nombre' => $equipo->nombre,
                        'tipo_equipo' => $equipo->tipoEquipo ? $equipo->tipoEquipo->nombre : null, 
                    ];
                }),

            ]
        ];
    }


}
