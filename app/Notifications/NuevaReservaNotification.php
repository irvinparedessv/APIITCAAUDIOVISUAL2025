<?php
// app/Notifications/NuevaReservaNotification.php

namespace App\Notifications;

use App\Models\ReservaEquipo;
use Illuminate\Broadcasting\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Log;

class NuevaReservaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $reserva;

    public function __construct(ReservaEquipo $reserva)
    {
        $this->reserva = $reserva->load('user'); // <-- aseguramos que el user esté cargado
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
                
            ]
            
        ]);
    }

    public function toDatabase($notifiable)
    {
        Log::info('User Details: ', [
            'user' => $this->reserva->user,
            'first_name' => $this->reserva->user ? $this->reserva->user->first_name : null,
            'last_name' => $this->reserva->user ? $this->reserva->user->last_name : null,
        ]);
        return [
            'reserva_id' => $this->reserva->id,
            'user' => $this->reserva->user ? $this->reserva->user->first_name . ' ' . $this->reserva->user->last_name : null, // Concatenamos el nombre
            'aula' => $this->reserva->aula,
            'fecha_reserva' => $this->reserva->fecha_reserva,
            'fecha_entrega' => $this->reserva->fecha_entrega,
        ];
    }

    public function broadcastOn()
    {
        // Canal compartido para admin y encargado
        return new Channel('notifications.rol.admin-encargado');
    }



    public function broadcastAs()
    {
        // Define un nombre de evento personalizado
        return 'nueva.reserva';
    }

}
