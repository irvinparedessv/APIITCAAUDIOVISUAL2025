<?php

namespace Database\Seeders;

use App\Models\ReservaAula;
use App\Models\ReservaAulaBloque;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ReservaAulaBloqueSeeder extends Seeder
{
    public function run(): void
    {
        $reservas = ReservaAula::all();

        foreach ($reservas as $reserva) {
            // Desglosa horario "08:00-09:00"
            [$horaInicio, $horaFin] = explode('-', $reserva->horario);

            // Si la reserva tiene fecha_fin y dias => es recurrente
            if ($reserva->fecha_fin && $reserva->dias) {
                $dias = json_decode($reserva->dias, true); // ejemplo: ["Monday", "Wednesday"]
                $fechaInicio = Carbon::parse($reserva->fecha);
                $fechaFin = Carbon::parse($reserva->fecha_fin);

                // Recorre rango de fechas
                for ($date = $fechaInicio->copy(); $date->lte($fechaFin); $date->addDay()) {
                    if (in_array($date->format('l'), $dias)) {
                        ReservaAulaBloque::create([
                            'reserva_id'   => $reserva->id,
                            'fecha_inicio' => $date->toDateString(),
                            'fecha_fin'    => $date->toDateString(),
                            'hora_inicio'  => $horaInicio,
                            'hora_fin'     => $horaFin,
                            'dia'          => $date->format('l'),
                            'estado'       => strtolower($reserva->estado),
                        ]);
                    }
                }
            } else {
                // No recurrente => solo un bloque
                ReservaAulaBloque::create([
                    'reserva_id'   => $reserva->id,
                    'fecha_inicio' => $reserva->fecha,
                    'fecha_fin'    => $reserva->fecha,
                    'hora_inicio'  => $horaInicio,
                    'hora_fin'     => $horaFin,
                    'dia'          => Carbon::parse($reserva->fecha)->format('l'),
                    'estado'       => strtolower($reserva->estado),
                ]);
            }
        }
    }
}
