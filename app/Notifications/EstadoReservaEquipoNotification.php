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
    protected $notifiableId;
    public $id;
    public $pagina; 
    protected $tipo;

    public function __construct(ReservaEquipo $reserva, $notifiableId, $pagina = 1, $tipo = 'estado')
    {
        if (!$reserva->user) {
            Log::error('No se puede crear notificación: Reserva sin usuario', ['reserva_id' => $reserva->id]);
            throw new \Exception("La reserva no tiene usuario asociado");
        }

        $this->reserva = $reserva->load(['user', 'equipos.tipoEquipo', 'tipoReserva']);
        $this->notifiableId = $notifiableId ?: $reserva->user->id;
        $this->pagina = $pagina;
        $this->id = (string) Str::uuid();
        $this->tipo = $tipo;

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
        $esEdicion = $this->tipo === 'edicion';
        $esResponsable = $notifiable->id !== $this->reserva->user->id;
        $fechaReserva = $this->reserva->fecha_reserva;
        $fechaEntrega = $this->reserva->fecha_entrega;

        $title = $esEdicion
            ? ($esResponsable ? 'Se ha actualizado una reserva de equipo' : 'Tu reserva de equipo ha sido actualizada')
            : 'Estado de tu reserva de equipo actualizado';

        $message = $esEdicion
            ? "La reserva de equipo #{$this->reserva->id} fue modificada."
            : "Tu reserva de equipo ha sido marcada como '{$this->reserva->estado}'.";

        return [
            'type' => 'estado_reserva',
            'title' => $title,
            'message' => $message,
            'reserva' => [
                'id' => $this->reserva->id,
                'pagina' => $this->pagina,
                'aula' => $this->reserva->aula,
                'tipo_reserva' => $this->reserva->tipoReserva?->nombre,
                'equipos' => $this->reserva->equipos->map(function($equipo) {
                    return [
                        'nombre' => $equipo->nombre,
                        'tipo_equipo' => $equipo->tipoEquipo?->nombre,
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
