<?php

namespace App\Notifications;

use App\Models\ReservaAula;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EstadoReservaAulaNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected $reserva;
    protected $notifiableId;
    public $id;
    protected $pagina;
    protected $tipo; // 'estado' o 'edicion'

    public function __construct(ReservaAula $reserva, $notifiableId = null, $pagina = 1, $tipo = 'estado')
    {
        if (!$reserva->user) {
            Log::error('No se puede crear notificación: Reserva de aula sin usuario', ['reserva_id' => $reserva->id]);
            throw new \Exception("La reserva de aula no tiene usuario asociado");
        }

        $this->reserva = $reserva->fresh(['user', 'aula']);
        $this->notifiableId = $notifiableId ?: $reserva->user->id;
        $this->id = (string) Str::uuid();
        $this->pagina = $pagina;
        $this->tipo = $tipo;

        Log::info('Creando notificación de aula', [
            'reserva_id' => $reserva->id,
            'user_id' => $this->notifiableId,
            'estado' => $reserva->estado,
            'tipo' => $tipo,
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

        $title = $esEdicion
            ? ($esResponsable ? 'Se ha actualizado una reserva de aula' : 'Tu reserva de aula ha sido actualizada')
            : 'Estado de tu reserva de aula actualizado';

        $message = $esEdicion
            ? "La reserva de aula #{$this->reserva->id} fue modificada."
            : "Tu reserva para el aula {$this->reserva->aula->name} ha sido marcada como '{$this->reserva->estado}'.";

        return [
            'type' => 'estado_reserva_aula',
            'title' => $title,
            'message' => $message,
            'pagina' => $this->pagina,
            'reserva' => [
                'id' => $this->reserva->id,
                'aula' => $this->reserva->aula->name,
                'fecha' => $this->reserva->fecha->format('Y-m-d'),
                'horario' => $this->reserva->horario,
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
        return 'reserva.aula.estado.actualizado';
    }
}
