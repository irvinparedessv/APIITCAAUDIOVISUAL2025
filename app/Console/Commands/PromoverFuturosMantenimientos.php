<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FuturoMantenimiento;
use App\Models\Mantenimiento;
use App\Models\Equipo;
use App\Models\Bitacora;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PromoverFuturosMantenimientos extends Command
{
    protected $signature = 'mantenimientos:promover';

    protected $description = 'Convierte futuros mantenimientos en mantenimientos reales';

    public function handle()
    {
        $hoy = Carbon::now()->toDateString();

        $futuros = FuturoMantenimiento::where('fecha_mantenimiento', '<=', $hoy)->get();

        $this->info("Procesando " . $futuros->count() . " futuros mantenimientos...");

        foreach ($futuros as $futuro) {
            // Verificar si ya existe un mantenimiento creado
            $existe = Mantenimiento::where('futuro_mantenimiento_id', $futuro->id)->exists();
            if ($existe) {
                $this->line("Ya existe mantenimiento para el futuro ID {$futuro->id}");
                continue;
            }

            // Obtener el equipo asociado
            $equipo = Equipo::find($futuro->equipo_id);
            $estadoAnterior = $equipo->estado->nombre ?? 'Desconocido';

            // Crear mantenimiento real
            $nuevo = Mantenimiento::create([
                'equipo_id' => $futuro->equipo_id,
                'fecha_mantenimiento' => $futuro->fecha_mantenimiento,
                'hora_mantenimiento_inicio' => $futuro->hora_mantenimiento_inicio,
                'hora_mantenimiento_final' => $futuro->hora_mantenimiento_final,
                'tipo_id' => $futuro->tipo_mantenimiento_id,
                'user_id' => $futuro->user_id,
                'futuro_mantenimiento_id' => $futuro->id,
                'detalles' => 'Mantenimiento generado automáticamente desde programación',
            ]);

            // Cambiar estado del equipo a "En Mantenimiento" (ID 2)
            $equipo->estado_id = 2; // Asumiendo que 2 es "En Mantenimiento"
            $equipo->save();
            $estadoNuevo = $equipo->estado->nombre;

            // Registrar en bitácora
            $descripcion = "Sistema promovió un futuro mantenimiento a real:\n" .
                "Equipo: {$equipo->modelo->marca->nombre} {$equipo->modelo->nombre} (S/N: {$equipo->numero_serie})\n" .
                "Tipo: {$futuro->tipoMantenimiento->nombre}\n" .
                "Fecha: {$futuro->fecha_mantenimiento}\n" .
                "Estado: {$estadoAnterior} → {$estadoNuevo}";

            Bitacora::create([
                'user_id' => null, // O usar un usuario sistema si tienes
                'nombre_usuario' => 'Sistema Automático',
                'accion' => 'Promoción de mantenimiento',
                'modulo' => 'Mantenimiento',
                'descripcion' => $descripcion,
            ]);

            $this->info("Mantenimiento creado con ID {$nuevo->id} desde Futuro ID {$futuro->id}. Equipo ID {$equipo->id} marcado como En Mantenimiento.");
        }

        $this->info('Proceso completado.');
    }
}