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
        $this->reserva = $reserva->load('equipos');
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
                'reserva' => $this->reserva
            ]);
    } catch (\Exception $e) {
        Log::error('Error al generar el correo de ConfirmarReservaUsuario', [
            'error' => $e->getMessage(),
            'reserva_id' => $this->reserva->id ?? null,
            'stack' => $e->getTraceAsString(),
        ]);
        throw $e; // re-lanzamos para que el job falle y Laravel lo registre también
    }
}

}
