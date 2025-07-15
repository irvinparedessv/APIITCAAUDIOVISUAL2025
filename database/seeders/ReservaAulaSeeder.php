<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Aula;
use App\Models\ReservaAula;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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

        $materias = [
            'POO',
            'CA',
            'DB',
            'RD',
            'IA',
            'SO',

        ];

        $familias = [
            'DS-29',
            'DS-30',
            'DS-31',
            'DS-32',
            'DS-33',
            'DS-34',
            'DS-35',
            'DS-36',
            'DS-37',
            'DS-38'
        ];

        $grupos = [
            'Teorico',
            'Practica'

        ];

        $mesesHistorial = 24;
        $estados = ['Aprobado', 'Pendiente', 'Rechazado'];
        $bloquesHorario = [
            '07:00 - 07:50',
            '07:55 - 08:45',
            '08:50 - 09:40',
            '09:45 - 10:35',
            '10:40 - 11:30',
            '13:10 - 14:00',
            '14:05 - 14:55',
            '15:00 - 15:50',
            '15:55 - 16:45',
            '16:50 - 17:40',
            '17:45 - 18:35',
            '18:40 - 19:30',
            '19:35 - 20:25',
        ];

        for ($mes = 0; $mes < $mesesHistorial; $mes++) {
            $cantidadReservas = rand(30, 70);

            for ($i = 0; $i < $cantidadReservas; $i++) {
                $fecha = now()
                    ->subMonths($mes)
                    ->startOfMonth()
                    ->addDays(rand(0, 27));

                $horario = $bloquesHorario[array_rand($bloquesHorario)];

                $materia = $materias[array_rand($materias)];
                $familia = $familias[array_rand($familias)];
                $grupo = $grupos[array_rand($grupos)];

                $titulo = "{$materia} {$familia} {$grupo}";

                // Si quieres un comentario aleatorio extra:
                // $comentarioExtra = $faker->sentence();

                $reserva = ReservaAula::create([
                    'aula_id' => $aulas->random()->id,
                    'user_id' => $usuarios->random()->id,
                    'fecha' => $fecha->toDateString(),
                    'horario' => $horario,
                    'estado' => $estados[array_rand($estados)],
                    'titulo' => $titulo,
                ]);

                DB::table('codigo_qr_aulas')->insert([
                    'id' => Str::uuid(),
                    'reserva_id' => $reserva->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('Reservas de aula con comentario y códigos QR generados exitosamente.');
    }
}
