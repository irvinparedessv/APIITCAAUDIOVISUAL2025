<?php

namespace App\Notifications;

use App\Models\ReservaAula;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Log;

class NuevaReservaAulaNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    public $reserva;
    public $notifiableId;
    public $pagina;
    public $creadorId;

    public function __construct(ReservaAula $reserva, $notifiableId, int $pagina = 1, $creadorId = null)
    {
        $this->reserva = $reserva->load(['user', 'aula']);
        $this->notifiableId = $notifiableId;
        $this->pagina = $pagina;
        $this->creadorId = $creadorId;
    }

    public function via($notifiable)
    {
        Log::info('Método via() ejecutado para notificaciones de aula');
        return ['database', 'broadcast'];
    }

    public function toBroadcast($notifiable)
    {
        Log::info('User Details (aula): ', [
            'user' => $this->reserva->user,
            'first_name' => $this->reserva->user ? $this->reserva->user->first_name : null,
            'last_name' => $this->reserva->user ? $this->reserva->user->last_name : null,
        ]);

        $usuarioNombre = $this->reserva->user
            ? $this->reserva->user->first_name . ' ' . $this->reserva->user->last_name
            : 'Usuario desconocido';

        $esCreadorAdmin = $this->creadorId && $this->creadorId !== $this->reserva->user->id;

        return [
            'title' => $esCreadorAdmin
                ? 'Reserva de aula realizada por administración'
                : 'Nueva reserva de aula recibida',
            'message' => $esCreadorAdmin
                ? "Nueva reserva realizada por administración."
                : "Nueva reserva de aula recibida del usuario {$usuarioNombre}.",
            'reserva' => [
                'id' => $this->reserva->id,
                'pagina' => $this->pagina,
                'user' => $usuarioNombre,
                'aula' => $this->reserva->aula->name,
                'fecha' => $this->reserva->fecha->format('Y-m-d'),
                'horario' => $this->reserva->horario,
                'estado' => $this->reserva->estado,
            ]
        ];
    }

    public function toDatabase($notifiable)
    {
        $usuarioNombre = $this->reserva->user
            ? $this->reserva->user->first_name . ' ' . $this->reserva->user->last_name
            : 'Usuario desconocido';

        $esCreadorAdmin = $this->creadorId && $this->creadorId !== $this->reserva->user->id;

        return [
            'type' => 'nueva_reserva_aula',
            'title' => $esCreadorAdmin
                ? 'Reserva de aula realizada por administración'
                : 'Nueva reserva de aula recibida',
            'message' => $esCreadorAdmin
                ? "Nueva reserva realizada por administración."
                : "Nueva reserva de aula recibida del usuario {$usuarioNombre}.",
            'reserva' => [
                'id' => $this->reserva->id,
                'pagina' => $this->pagina,
                'user' => $usuarioNombre,
                'aula' => $this->reserva->aula->name,
                'fecha' => $this->reserva->fecha->format('Y-m-d'),
                'horario' => $this->reserva->horario,
                'estado' => $this->reserva->estado,
            ]
        ];
    }

    public function broadcastOn()
    {
        return [new PrivateChannel('notifications.user.' . $this->notifiableId)];
    }

    public function broadcastAs()
    {
        return 'nueva.reserva.aula';
    }

    public function broadcastWith()
    {
        $usuarioNombre = $this->reserva->user
            ? $this->reserva->user->first_name . ' ' . $this->reserva->user->last_name
            : 'Usuario desconocido';

        return [
            'reserva' => [
                'id' => $this->reserva->id,
                'pagina' => $this->pagina,
                'user' => $usuarioNombre,
                'aula' => $this->reserva->aula->name,
                'fecha' => $this->reserva->fecha->format('Y-m-d'),
                'horario' => $this->reserva->horario,
                'estado' => $this->reserva->estado,
            ]
        ];
    }
}
