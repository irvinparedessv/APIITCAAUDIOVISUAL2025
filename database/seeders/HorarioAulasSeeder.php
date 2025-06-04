<?php

namespace Database\Seeders;

use App\Models\Aula;
use App\Models\HorarioAulas;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HorarioAulasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $aulas = Aula::all();

        // Rango de fechas
        $startDate = '2025-06-01';
        $endDate = '2025-11-30';

        // Horario: 8:00 AM a 5:00 PM
        $startTime = '08:00:00';
        $endTime = '17:00:00';

        // Días de lunes a viernes
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        foreach ($aulas as $aula) {
            HorarioAulas::create([
                'aula_id' => $aula->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'days' => json_encode($days),
            ]);
        }
    }
}
