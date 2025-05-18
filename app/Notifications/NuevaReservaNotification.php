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
        $this->reserva = $reserva->load('user'); // <-- aseguramos que el user esté cargado
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
        return [
            'reserva' => [
                'id' => $this->reserva->id,
                'user' => $this->reserva->user ? $this->reserva->user->first_name . ' ' . $this->reserva->user->last_name : null,
                'aula' => $this->reserva->aula,
                'fecha_reserva' => $this->reserva->fecha_reserva,
                'fecha_entrega' => $this->reserva->fecha_entrega,
            ]
        ];
    }

}
