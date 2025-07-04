<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReservaAula;
use App\Models\ReservaEquipo;
use App\Notifications\EstadoReservaAulaNotification;
use App\Notifications\EstadoReservaEquipoNotification;
use App\Helpers\BitacoraHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class CancelarReservasVencidas extends Command
{
    protected $signature = 'reservas:cancelar-vencidas';
    protected $description = 'Cancela reservas de aulas y equipos vencidas';

    public function handle()
    {
        $ahora = Carbon::today();
        $hoy = $ahora->toDateString();

        $this->cancelarAulas($hoy);
        $this->cancelarEquipos($ahora);

        $this->info('Proceso de cancelación completado.');
    }

    private function cancelarAulas($hoy)
    {
        $reservas = ReservaAula::where('estado', 'pendiente')
            ->whereDate('fecha', '<', $hoy)
            ->get();

        foreach ($reservas as $reserva) {
            $estadoAnterior = $reserva->estado;

            $reserva->estado = 'Cancelado';
            $reserva->comentario = 'Cancelada automáticamente por cron.';
            $reserva->save();

            if ($reserva->user) {
                $pagina = null;
                $reserva->user->notify(new EstadoReservaAulaNotification($reserva, $pagina));
            }

            BitacoraHelper::registrarCambioEstadoReservaAula(
                $reserva->id,
                $estadoAnterior,
                'Cancelado',
                'Sistema (Cron)'
            );

            Log::info("Reserva aula ID {$reserva->id} cancelada automáticamente.");
        }
    }

    private function cancelarEquipos($ahora)
    {
        $reservas = ReservaEquipo::where('estado', 'Pendiente')
            ->where('fecha_reserva', '<', $ahora)
            ->get();

        foreach ($reservas as $reserva) {
            $estadoAnterior = $reserva->estado;

            $reserva->estado = 'Cancelado';
            $reserva->comentario = 'Cancelada automáticamente por sistema.';
            $reserva->save();

            if ($reserva->user) {
                $pagina = null;
                $reserva->user->notify(new EstadoReservaEquipoNotification($reserva, $reserva->user->id, $pagina));
            }

            BitacoraHelper::registrarCambioEstadoReserva(
                $reserva->id,
                $estadoAnterior,
                'Cancelado',
                'Sistema'
            );

            Log::info("Reserva equipo ID {$reserva->id} cancelada automáticamente.");
        }
    }
}
