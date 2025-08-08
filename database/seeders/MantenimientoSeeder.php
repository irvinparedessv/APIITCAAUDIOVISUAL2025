<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mantenimiento;
use App\Models\Equipo;
use App\Models\TipoMantenimiento;
use App\Models\User;
use App\Models\FuturoMantenimiento;
use Carbon\Carbon;

class MantenimientoSeeder extends Seeder
{
    public function run()
    {
        $equipos = Equipo::all();
        $tipos = TipoMantenimiento::all();
        $usuarios = User::whereIn('role_id', [1, 2])->get(); // Solo admins o encargados

        foreach ($equipos as $equipo) {
            $tipo = $tipos->random();
            $usuario = $usuarios->random();

            // Fecha aleatoria en el pasado
            $fechaInicio = Carbon::now()->subDays(rand(1, 100));
            $fechaFinal = (clone $fechaInicio)->addDays(rand(0, 3)); // puede ser el mismo día o unos días después

            // Futuro mantenimiento relacionado (50% probabilidad)
            $futuro = FuturoMantenimiento::where('equipo_id', $equipo->id)->inRandomOrder()->first();

            Mantenimiento::create([
                'equipo_id' => $equipo->id,
                'fecha_mantenimiento' => $fechaInicio->toDateString(),
                'fecha_mantenimiento_final' => $fechaFinal->toDateString(),
                'hora_mantenimiento_inicio' => '08:00:00',
                'hora_mantenimiento_final' => '12:00:00',
                'detalles' => 'Mantenimiento realizado correctamente.',
                'comentario' => 'El equipo fue revisado y se reemplazaron componentes menores.',
                'tipo_id' => $tipo->id,
                'user_id' => $usuario->id,
                'futuro_mantenimiento_id' => $futuro ? $futuro->id : null,
                'vida_util' => rand(1, 12),
                'estado_equipo_inicial' => 1,
                'estado_equipo_final' => 1,
            ]);
        }
    }
}
