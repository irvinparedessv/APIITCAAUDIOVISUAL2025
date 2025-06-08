<?php

namespace Database\Seeders;

use App\Models\Equipo;
use App\Models\ReservaEquipo;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReservaEquipoSeeder extends Seeder
{
    public function run(): void
    {
        $equipos = Equipo::all();
        $usuarios = User::all();

        if ($equipos->isEmpty() || $usuarios->isEmpty()) {
            $this->command->error('No hay equipos o usuarios para generar reservas.');
            return;
        }

        $mesesHistorial = 24; // 24 meses atrás (2 años)
        $maxReservasPorMes = 10; // max reservas por equipo y mes

        foreach ($equipos as $equipo) {
            for ($mes = 0; $mes < $mesesHistorial; $mes++) {
                $cantidadReservas = rand(3, $maxReservasPorMes);

                for ($i = 0; $i < $cantidadReservas; $i++) {
                    // Fecha de la reserva entre el día 1 y 28 del mes correspondiente, para evitar problemas de meses con 30/31 días
                    $fecha = now()->subMonths($mes)->startOfMonth()->addDays(rand(0, 27));

                    // Hora inicio entre 8 y 17 horas
                    $horaInicio = $fecha->copy()->setTime(rand(8, 17), 0);

                    // Duración entre 1 y 3 horas
                    $duracionHoras = rand(1, 3);
                    $horaFin = $horaInicio->copy()->addHours($duracionHoras);

                    // Aula aleatoria
                    $aula = 'A-' . rand(100, 200);

                    // Crear reserva
                    $reserva = ReservaEquipo::create([
                        'user_id' => $usuarios->random()->id,
                        'aula' => $aula,
                        'fecha_reserva' => $horaInicio,
                        'fecha_entrega' => $horaFin,
                        'estado' => 'Aprobado',
                        'tipo_reserva_id' => 1,
                    ]);

                    // Asociar equipo con cantidad aleatoria (1 o 2)
                    $reserva->equipos()->attach($equipo->id, ['cantidad' => rand(1, 2)]);
                }
            }
        }
    }
}
