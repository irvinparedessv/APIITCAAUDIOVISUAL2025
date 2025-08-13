<?php

namespace Database\Seeders;

use App\Models\Aula;
use App\Models\Equipo;
use App\Models\ReservaEquipo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ReservaEquipoSeeder extends Seeder
{
    public function run(): void
    {
        $equipos = Equipo::where('es_componente', false)->get();
        $usuarios = User::all();
        $ubicaciones = Aula::all();

        if ($equipos->isEmpty() || $usuarios->isEmpty()) {
            $this->command->error('No hay equipos o usuarios para generar reservas.');
            return;
        }

        $mesesHistorial = 24;
        $maxReservasPorMes = 30; // MÁS reservas por mes

        // Agrupar equipos por tipo
        $equiposPorTipo = $equipos->groupBy('tipo_equipo_id');

        for ($mes = 0; $mes < $mesesHistorial; $mes++) {
            $cantidadReservas = rand(50, 100); // Aumentamos la cantidad por mes

            for ($i = 0; $i < $cantidadReservas; $i++) {
                $fecha = now()->subMonths($mes)->startOfMonth()->addDays(rand(0, 27));
                $horaInicio = $fecha->copy()->setTime(rand(7, 20), [0, 15, 30, 45][rand(0, 3)]);
                $horaFin = $horaInicio->copy()->addHours(rand(1, 3));
                $aula =  $ubicaciones->random()->id;
                $tipoReserva = rand(1, 2);

                $reserva = ReservaEquipo::create([
                    'user_id' => $usuarios->random()->id,
                    'aula_id' => $aula,
                    'fecha_reserva' => $horaInicio,
                    'fecha_entrega' => $horaFin,
                    'estado' => 'Aprobado',
                    'tipo_reserva_id' => $tipoReserva,
                ]);

                // 🚩 Crear QR relacionado
                DB::table('codigo_qr_reserva_equipos')->insert([
                    'id' => Str::uuid(),
                    'reserva_id' => $reserva->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Selección de tipos sin repetir
                $tiposSeleccionados = $equiposPorTipo->keys()
                    ->random(rand(1, min(4, $equiposPorTipo->count())));

                if ($tiposSeleccionados instanceof \Illuminate\Support\Collection) {
                    $tiposSeleccionados = $tiposSeleccionados->all();
                } else {
                    $tiposSeleccionados = [$tiposSeleccionados];
                }

                foreach ($tiposSeleccionados as $tipoId) {
                    $equiposDelTipo = $equiposPorTipo[$tipoId];
                    $equipo = $equiposDelTipo->random();

                    $reserva->equipos()->attach($equipo->id, [
                        'cantidad' => 1,
                    ]);
                }
            }
        }

        $this->command->info('Reservas con QR generadas exitosamente.');
    }
}
