<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mantenimiento;
use App\Models\Equipo;
use App\Models\TipoMantenimiento;
use App\Models\User;
use App\Models\FuturoMantenimiento;

class MantenimientoSeeder extends Seeder
{
    public function run()
    {
        $equipos = Equipo::all();
        $tipos = TipoMantenimiento::all();
        $usuarios = User::all();

        foreach ($equipos as $equipo) {
            $tipo = $tipos->random();
            $usuario = $usuarios->random();

            // 50% probabilidad de tener un futuro mantenimiento relacionado
            $futuro = FuturoMantenimiento::where('equipo_id', $equipo->id)->inRandomOrder()->first();

            Mantenimiento::create([
                'equipo_id' => $equipo->id,
                'fecha_mantenimiento' => now()->subDays(rand(1, 100))->toDateString(),
                'hora_mantenimiento_inicio' => '08:00:00',
                'hora_mantenimiento_final' => '12:00:00',
                'detalles' => 'Mantenimiento realizado correctamente.',
                'tipo_id' => $tipo->id,
                'user_id' => $usuario->id,
                'futuro_mantenimiento_id' => $futuro ? $futuro->id : null,
                'vida_util' => rand(1, 12),
            ]);
        }
    }
}
