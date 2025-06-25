<?php

namespace App\Notifications;

use App\Models\ReservaAula;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CancelarReservaAulaPrestamista extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $reserva;
    protected $notifiableId;
    public $id;
    protected $pagina;

    public function __construct(ReservaAula $reserva, $notifiableId = null, $pagina = 1)
    {
        if (!$reserva->user) {
            Log::error('No se puede crear notificación: Reserva de aula sin usuario', ['reserva_id' => $reserva->id]);
            throw new \Exception("La reserva de aula no tiene usuario asociado");
        }

        $this->reserva = $reserva->fresh(['user', 'aula']);
        $this->notifiableId = $notifiableId ?: $reserva->user->id;
        $this->id = (string) Str::uuid();
        $this->pagina = $pagina;

        Log::info('Creando notificación de cancelación por prestamista', [
            'reserva_id' => $reserva->id,
            'cancelada_por' => $reserva->user->first_name . ' ' . $reserva->user->last_name,
        ]);
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'cancelacion_reserva_prestamista',
            'title' => 'Se ha procedido la cancelación de una reserva de aula',
            'message' => "La reserva para el aula {$this->reserva->aula->name} fue cancelada por {$this->reserva->user->first_name} {$this->reserva->user->last_name}.",
            'reserva' => [
                'id' => $this->reserva->id,
                'aula' => $this->reserva->aula->name,
                'fecha' => $this->reserva->fecha->format('Y-m-d'),
                'horario' => $this->reserva->horario,
                'estado' => $this->reserva->estado,
                'comentario' => $this->reserva->comentario,
                'pagina' => $this->pagina,
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
        return 'reserva.aula.cancelada.prestamista';
    }
}
