<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReservaEquipo;
use App\Notifications\RecordatorioDevolucionEquipoNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RecordatorioDevolucionEquipos extends Command
{
    protected $signature = 'reservas:recordatorio-devolucion';
    protected $description = 'Envía recordatorio para devolver equipos a punto de finalizar la reserva';

    public function handle()
    {
        $ahora = Carbon::now();
        $enQuinceMinutos = $ahora->copy()->addMinutes(15);

        $reservas = ReservaEquipo::where('estado', 'Pendiente')
            ->whereBetween('fecha_hora_fin', [$ahora, $enQuinceMinutos])
            ->get();

        foreach ($reservas as $reserva) {
            if ($reserva->user) {
                $reserva->user->notify(new RecordatorioDevolucionEquipoNotification($reserva));
                Log::info("Recordatorio enviado para reserva equipo ID {$reserva->id}");
            }
        }

        $this->info('Recordatorios de devolución enviados correctamente.');
    }
}
