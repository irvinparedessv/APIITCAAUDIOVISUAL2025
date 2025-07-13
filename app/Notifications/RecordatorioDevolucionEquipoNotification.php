<?php

namespace App\Notifications;

use App\Models\ReservaEquipo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RecordatorioDevolucionEquipoNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $reserva;
    protected $notifiableId;
    public $id;

    public function __construct(ReservaEquipo $reserva, $notifiableId = null)
    {
        if (!$reserva->user) {
            Log::error('No se puede crear notificación: Reserva de equipo sin usuario', ['reserva_id' => $reserva->id]);
            throw new \Exception("La reserva de equipo no tiene usuario asociado");
        }

        $this->reserva = $reserva->fresh(['user', 'equipo']);
        $this->notifiableId = $notifiableId ?: $reserva->user->id;
        $this->id = (string) Str::uuid();

        Log::info('Creando notificación de recordatorio de devolución', [
            'reserva_id' => $reserva->id,
            'usuario' => $reserva->user->first_name . ' ' . $reserva->user->last_name,
        ]);
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'recordatorio_devolucion_equipo',
            'title' => 'Recordatorio: Devolución de equipo',
            'message' => "Tu reserva para el equipo {$this->reserva->equipo->name} está a punto de finalizar. Devuélvelo a tiempo.",
            'reserva' => [
                'id' => $this->reserva->id,
                'equipo' => $this->reserva->equipo->name,
                'fecha_fin' => $this->reserva->fecha_hora_fin->format('Y-m-d H:i'),
                'estado' => $this->reserva->estado,
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
        return 'reserva.equipo.recordatorio.devolucion';
    }

    public function toMail($notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Recordatorio: Devolución de equipo')
            ->line("Hola {$this->reserva->user->first_name},")
            ->line("Tu reserva para el equipo **{$this->reserva->equipo->name}** está a punto de finalizar.")
            ->line('Hora de finalización: ' . $this->reserva->fecha_hora_fin->format('Y-m-d H:i'))
            ->line('Por favor, devuelve el equipo a tiempo para evitar inconvenientes.')
            ->line('Gracias.');
    }
}
