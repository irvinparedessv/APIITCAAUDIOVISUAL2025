<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoMantenimiento;

class TipoMantenimientoSeeder extends Seeder
{
    public function run()
    {
        $tipos = [
            ['nombre' => 'Preventivo', 'estado' => 1],
            ['nombre' => 'Correctivo', 'estado' => 1],
            ['nombre' => 'Predictivo', 'estado' => 1],
        ];

        foreach ($tipos as $tipo) {
            TipoMantenimiento::updateOrCreate(['nombre' => $tipo['nombre']], $tipo);
        }
    }
}
