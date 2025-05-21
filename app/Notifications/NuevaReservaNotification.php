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
        $this->reserva->load(['user', 'equipos.tipoEquipo', 'aula']); // cargamos relaciones necesarias
        $this->notifiableId = $notifiableId;
    }


    public function via($notifiable)
    {
        Log::info('Método via() ejecutado para notificaciones');
        return ['database', 'broadcast']; // Aquí activas los dos canales
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
                'user' => $this->reserva->user ? $this->reserva->user->first_name . ' ' . $this->reserva->user->last_name : null, // Concatenamos el nombre
                'aula' => $this->reserva->aula,
                'fecha_reserva' => $this->reserva->fecha_reserva,
                'fecha_entrega' => $this->reserva->fecha_entrega,
                'estado' => $this->reserva->estado,
            ]
            
        ]);
    }

    public function toDatabase($notifiable)
    {
        $usuarioNombre = $this->reserva->user
            ? $this->reserva->user->first_name . ' ' . $this->reserva->user->last_name
            : 'Usuario desconocido';

        return [
            'title' => 'Nueva reserva recibida',
            'message' => "Nueva reserva recibida del usuario {$usuarioNombre}.",
            'reserva_id' => $this->reserva->id,
            'user' => $usuarioNombre,
            'aula' => $this->reserva->aula?->nombre ?? $this->reserva->aula, // si aula es relación
            'fecha_reserva' => $this->reserva->fecha_reserva,
            'fecha_entrega' => $this->reserva->fecha_entrega,
            'estado' => $this->reserva->estado,
            'equipos' => $this->reserva->equipos->map(function($equipo) {
                return [
                    'nombre' => $equipo->nombre,
                    'tipo' => $equipo->tipoEquipo ? $equipo->tipoEquipo->nombre : null,
                ];
            }),

        ];
    }



    public function broadcastOn()
    {
         // Canal privado para el usuario específico responsable
        return [new PrivateChannel('notifications.user.' . $this->notifiableId)];
    }



    public function broadcastAs()
    {
        // Define un nombre de evento personalizado
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
                'equipos' => $this->reserva->equipos->map(function($equipo) {
                    return [
                        'nombre' => $equipo->nombre,
                        'tipo' => $equipo->tipoEquipo ? $equipo->tipoEquipo->nombre : null,
                    ];
                }),

            ]
        ];
    }


}
