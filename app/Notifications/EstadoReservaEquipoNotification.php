<?php

namespace App\Notifications;

use App\Models\ReservaEquipo;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EstadoReservaEquipoNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected $reserva;
    protected $notifiableId;  // Cambiado a protected para consistencia
    public $id;

    public function __construct(ReservaEquipo $reserva, $notifiableId)
    {
        // Validación importante
        if (!$reserva->user) {
            Log::error('No se puede crear notificación: Reserva sin usuario', ['reserva_id' => $reserva->id]);
            throw new \Exception("La reserva no tiene usuario asociado");
        }

        $this->reserva = $reserva->load(['user', 'equipos.tipoEquipo', 'tipoReserva']);
        $this->notifiableId = $notifiableId ?: $reserva->user->id; // Usar parámetro o user->id
        $this->id = (string) Str::uuid();
        
        Log::info('Creando notificación de estado', [
            'reserva_id' => $reserva->id,
            'user_id' => $this->notifiableId,
            'estado' => $reserva->estado
        ]);
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        // Convertir fechas a string solo si son objetos Carbon/DateTime
        $fechaReserva = $this->reserva->fecha_reserva;
        $fechaEntrega = $this->reserva->fecha_entrega;
        
        return [
            'type' => 'estado_reserva',
            'title' => 'Estado de tu reserva de equipo actualizada',
            'message' => "Tu reserva para el aula {$this->reserva->aula} ha sido marcada como '{$this->reserva->estado}'.",
            'reserva' => [
                'id' => $this->reserva->id,
                'aula' => $this->reserva->aula,
                'tipo_reserva' => $this->reserva->tipoReserva ? $this->reserva->tipoReserva->nombre : null,
                'equipos' => $this->reserva->equipos->map(function($equipo) {
                    return [
                        'nombre' => $equipo->nombre,
                        'tipo_equipo' => $equipo->tipoEquipo ? $equipo->tipoEquipo->nombre : null,
                    ];
                })->toArray(),
                'fecha_reserva' => is_object($fechaReserva) ? $fechaReserva->toDateTimeString() : $fechaReserva,
                'fecha_entrega' => is_object($fechaEntrega) ? $fechaEntrega->toDateTimeString() : $fechaEntrega,
                'estado' => $this->reserva->estado,
                'comentario' => $this->reserva->comentario,
            ]
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function broadcastOn()
    {
        return new PrivateChannel("notifications.user.{$this->notifiableId}");
    }

    public function broadcastAs()
    {
        return 'reserva.estado.actualizado';
    }
}