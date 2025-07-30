<?php

namespace App\Notifications;

use App\Models\ReservaEquipo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NotificarResponsableReserva extends Notification implements ShouldQueue
{
    use Queueable;

    public $reserva;

    public function __construct(ReservaEquipo $reserva)
    {
        $reserva->load(['user', 'aula', 'equipos.modelo', 'equipos.tipoEquipo']);

        $this->reserva = [
            'id' => $reserva->id,
            'fecha_reserva' => $reserva->fecha_reserva->toDateTimeString(),
            'fecha_entrega' => $reserva->fecha_entrega->toDateTimeString(),
            'aula' => $reserva->aula ? [
                'id' => $reserva->aula->id,
                'name' => $reserva->aula->name,
            ] : null,
            'user' => $reserva->user
                ? $reserva->user->first_name . ' ' . $reserva->user->last_name
                : 'Usuario desconocido',
            'equipos' => $reserva->equipos->map(function ($equipo) {
                return [
                    'id' => $equipo->id,
                    'numero_serie' => $equipo->numero_serie,
                    'modelo' => $equipo->modelo?->nombre,
                    'tipo_equipo' => $equipo->tipoEquipo?->nombre,
                ];
            })->toArray(),
        ];
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Nueva Reserva Recibida')
            ->markdown('emails.reserva_encargado', [
                'reserva' => $this->reserva,
            ]);
    }
}
