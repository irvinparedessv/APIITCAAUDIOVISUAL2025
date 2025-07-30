<?php

namespace App\Notifications;

use App\Models\ReservaEquipo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class ConfirmarReservaUsuario extends Notification implements ShouldQueue
{
    use Queueable;

    public $reserva;

    public function __construct(ReservaEquipo $reserva)
    {
        // Carga relaciones necesarias
        $reserva->load(['equipos.modelo', 'equipos.tipoEquipo', 'aula']);

        // Transformamos la reserva para la vista
        $this->reserva = [
            'id' => $reserva->id,
            'fecha_reserva' => $reserva->fecha_reserva->toDateTimeString(),
            'fecha_entrega' => $reserva->fecha_entrega->toDateTimeString(),
            'aula' => $reserva->aula ? [
                'id' => $reserva->aula->id,
                'name' => $reserva->aula->name,
                'descripcion' => $reserva->aula->descripcion,
                // agrega otros campos que necesites
            ] : null,
            'equipos' => $reserva->equipos->map(function ($equipo) {
                return [
                    'id' => $equipo->id,
                    'numero_serie' => $equipo->numero_serie,
                    'tipo_equipo' => $equipo->tipoEquipo?->nombre,
                    'modelo' => $equipo->modelo?->nombre,
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
        try {
            return (new MailMessage)
                ->subject('Confirmación de tu Reserva')
                ->markdown('emails.reserva_usuario', [
                    'reserva' => $this->reserva,
                ]);
        } catch (\Exception $e) {
            Log::error('Error al generar el correo de ConfirmarReservaUsuario', [
                'error' => $e->getMessage(),
                'reserva_id' => $this->reserva['id'] ?? null,
                'stack' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
