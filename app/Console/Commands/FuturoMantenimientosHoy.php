<?php

namespace App\Console\Commands;

use App\Models\FuturoMantenimiento;
use App\Mail\FuturoMantenimientoHoyMailable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class FuturoMantenimientosHoy extends Command
{
    protected $signature = 'mantenimientos:enviar-hoy';
    protected $description = 'Envía correo a usuarios cuando tienen un mantenimiento programado para hoy';

    public function handle()
    {
        $hoy = Carbon::today()->toDateString();

        // Busca mantenimientos para HOY
        $mantenimientos = FuturoMantenimiento::with('equipo', 'user', 'tipoMantenimiento')
            ->where('fecha_mantenimiento', $hoy)
            ->get();

        if ($mantenimientos->isEmpty()) {
            $this->info('No hay mantenimientos programados para hoy.');
            return;
        }

        foreach ($mantenimientos as $mantenimiento) {
            if ($mantenimiento->user && $mantenimiento->user->email) {
                Mail::to($mantenimiento->user->email)
                    ->send(new FuturoMantenimientoHoyMailable($mantenimiento));
                $this->info("Correo enviado a {$mantenimiento->user->email} para el equipo {$mantenimiento->equipo->id}.");
            }
        }

        $this->info('Proceso finalizado.');
    }
}
