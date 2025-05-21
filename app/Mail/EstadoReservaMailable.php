<?php

namespace App\Mail;

use App\Models\ReservaEquipo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EstadoReservaMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $reserva;

    public function __construct(ReservaEquipo $reserva)
    {
        $this->reserva = $reserva->load('user');
    }

    public function build()
    {
        return $this->subject('Actualización de estado de reserva')
                    ->markdown('emails.reservas.estado')
                    ->with([
                        'reserva' => $this->reserva,
                    ]);
    }
}

