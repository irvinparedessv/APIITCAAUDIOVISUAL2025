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
            // Elegir tipo aleatorio
            $tipo = $tipos->random();

            // Crear futuros mantenimientos para cada equipo, por ejemplo 1 o 2 registros
            $cantidadFuturos = rand(1, 2);

            for ($i = 0; $i < $cantidadFuturos; $i++) {
                FuturoMantenimiento::create([
                    'equipo_id' => $equipo->id,
                    'fecha_mantenimiento' => now()->addDays(rand(1, 30))->toDateString(),
                    'hora_mantenimiento_inicio' => '09:00:00',
                    'hora_mantenimiento_final' => '11:00:00',
                ]);
            }
        }
    }
}
