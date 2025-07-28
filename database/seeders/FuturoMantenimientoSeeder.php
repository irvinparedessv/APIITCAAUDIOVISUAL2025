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
            // Crear 1 o 2 registros futuros
            $cantidadFuturos = rand(1, 2);

            for ($i = 0; $i < $cantidadFuturos; $i++) {
                // Elegir tipo aleatorio para cada mantenimiento
                $tipo = $tipos->random();

                FuturoMantenimiento::create([
                    'equipo_id' => $equipo->id,
                    'tipo_mantenimiento_id' => $tipo->id, // <-- Agregar esta línea
                    'fecha_mantenimiento' => now()->addDays(rand(1, 30))->toDateString(),
                    'hora_mantenimiento_inicio' => '09:00:00',
                    'hora_mantenimiento_final' => '11:00:00',
                ]);
            }
        }
    }
}
