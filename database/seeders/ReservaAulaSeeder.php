<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Aula;
use App\Models\ReservaAula;
use Illuminate\Database\Seeder;

class ReservaAulaSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = User::all();
        $aulas = Aula::all();

        if ($usuarios->isEmpty() || $aulas->isEmpty()) {
            $this->command->error('No hay usuarios o aulas disponibles para generar reservas.');
            return;
        }

        $mesesHistorial = 24;
        $estados = ['Aprobado', 'Pendiente', 'Rechazado'];
        $bloquesHorario = [
            '07:00 - 08:40',
            '08:40 - 10:20',
            '10:20 - 12:00',
            '12:00 - 13:40',
            '13:40 - 15:20',
            '15:20 - 17:00',
            '17:00 - 18:40',
            '18:40 - 20:20',
        ];

        for ($mes = 0; $mes < $mesesHistorial; $mes++) {
            $cantidadReservas = rand(30, 70);

            for ($i = 0; $i < $cantidadReservas; $i++) {
                $fecha = now()
                    ->subMonths($mes)
                    ->startOfMonth()
                    ->addDays(rand(0, 27));

                $horario = $bloquesHorario[array_rand($bloquesHorario)];

                ReservaAula::create([
                    'aula_id' => $aulas->random()->id,
                    'user_id' => $usuarios->random()->id,
                    'fecha' => $fecha->toDateString(),
                    'horario' => $horario,
                    'estado' => $estados[array_rand($estados)],
                    'comentario' => fake()->boolean(30) ? fake()->sentence() : null,
                ]);
            }
        }

        $this->command->info('Reservas de aula generadas exitosamente.');
    }
}
