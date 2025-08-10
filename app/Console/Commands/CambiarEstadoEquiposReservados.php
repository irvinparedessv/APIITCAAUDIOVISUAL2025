<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CambiarEstadoEquiposReservados extends Command
{
    protected $signature = 'equipos:cambiar-estado';
    protected $description = 'Cambia el estado de los equipos a Reservado cuando inicia su hora de reserva';

    public function handle()
    {
        $ahora = Carbon::now()->format('Y-m-d H:i');

        // Buscar reservas que empiezan ahora y están pendientes
        $reservas = DB::table('reserva_equipos')
            ->where('estado', 'Pendiente')
            ->whereRaw("DATE_FORMAT(fecha_reserva, '%Y-%m-%d %H:%i') = ?", [$ahora])
            ->pluck('id');

        if ($reservas->isEmpty()) {
            $this->info("No hay reservas pendientes en este minuto.");
            return;
        }

        // Obtener IDs de equipos
        $equipos = DB::table('equipo_reserva')
            ->whereIn('reserva_equipo_id', $reservas)
            ->pluck('equipo_id');

        if ($equipos->isNotEmpty()) {
            // Cambiar estado de equipos
            DB::table('equipos')
                ->whereIn('id', $equipos)
                ->update(['estado' => 'Reservado']);



            $this->info("Equipos y reservas actualizados correctamente.");
        } else {
            $this->info("No hay equipos para estas reservas.");
        }
    }
}
