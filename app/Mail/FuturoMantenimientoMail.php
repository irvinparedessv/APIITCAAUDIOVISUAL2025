<?php

namespace App\Mail;

use App\Models\FuturoMantenimiento;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FuturoMantenimientoHoyMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $mantenimiento;

    public function __construct(FuturoMantenimiento $mantenimiento)
    {
        $this->mantenimiento = $mantenimiento->load('equipo', 'tipoMantenimiento');
    }

    public function build()
    {
        return $this->subject('Tienes un mantenimiento programado para hoy')
            ->markdown('emails.mantenimiento_hoy')
            ->with(['mantenimiento' => $this->mantenimiento]);
    }
}
