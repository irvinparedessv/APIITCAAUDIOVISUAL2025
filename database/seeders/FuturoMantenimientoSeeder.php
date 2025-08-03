<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FuturoMantenimiento;
use App\Models\Equipo;
use App\Models\TipoMantenimiento;

class FuturoMantenimientoSeeder extends Seeder
{
    public function run()
    {
        $equipos = Equipo::all();
        $tipos = TipoMantenimiento::all();

        foreach ($equipos as $equipo) {
            $cantidadFuturos = rand(1, 2);

            for ($i = 0; $i < $cantidadFuturos; $i++) {
                FuturoMantenimiento::create([
                    'equipo_id' => $equipo->id,
                    'tipo_mantenimiento_id' => $tipos->random()->id,
                    'fecha_mantenimiento' => now()->addDays(rand(1, 30))->toDateString(),
                    'hora_mantenimiento_inicio' => '09:00:00',
                ]);
            }
        }
    }
}
