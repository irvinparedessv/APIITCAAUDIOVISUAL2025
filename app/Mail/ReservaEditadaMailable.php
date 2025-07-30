<?php

namespace App\Mail;

use App\Models\ReservaEquipo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservaEditadaMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $reserva;
    public $esResponsable; // true si es admin/encargado, false si es prestamista

    public function __construct(ReservaEquipo $reserva, bool $esResponsable)
    {
        $this->reserva = $reserva->load('user', 'equipos.tipoEquipo');
        $this->esResponsable = $esResponsable;
    }

    public function build()
    {
        $subject = $this->esResponsable
            ? 'Se ha actualizado una reserva de equipo'
            : 'Tu reserva de equipo ha sido modificada';

        $equipos = $this->reserva->equipos->map(function ($equipo) {
            $modelo = $equipo->modelo?->nombre ?? '';
            $tipo = $equipo->tipoEquipo?->nombre ?? '';
            $cantidad = $equipo->pivot->cantidad ?? 1;

            return [
                'nombre_completo' => "{$tipo} {$modelo}",
                'cantidad' => $cantidad,
            ];
        });

        return $this->markdown('emails.ediccion_reserva_equipo')
            ->subject($subject)
            ->with([
                'reserva' => $this->reserva,
                'esResponsable' => $this->esResponsable,
                'equipos' => $equipos, // ✅ aquí se pasa
            ]);
    }
}
