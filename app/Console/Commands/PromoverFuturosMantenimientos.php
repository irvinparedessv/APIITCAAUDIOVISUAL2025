<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FuturoMantenimiento;
use App\Models\Mantenimiento;
use App\Models\Equipo;
use App\Models\Bitacora;
use App\Models\Estado;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PromoverFuturosMantenimientos extends Command
{
    protected $signature = 'mantenimientos:promover';

    protected $description = 'Convierte futuros mantenimientos en mantenimientos activos';

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
                'fecha_mantenimiento_final' => $futuro->fecha_mantenimiento_final,
                'hora_mantenimiento_final' => $futuro->hora_mantenimiento_final,
                'tipo_id' => $futuro->tipo_mantenimiento_id,
                'user_id' => $futuro->user_id,
                'futuro_mantenimiento_id' => $futuro->id,
                'detalles' => $futuro->detalles,
            ]);

            // Cambiar estado del equipo a "En Mantenimiento" (ID 2)
            $equipo->estado_id = 2; 
            $equipo->save();
            $estadoNuevo = Estado::find(2)->nombre;

            // Registrar en bitácora
            $descripcion = "El sistema convirtió un mantenimiento programado en un mantenimiento activo.\n" .
                "Equipo: {$equipo->modelo->marca->nombre} {$equipo->modelo->nombre} (S/N: {$equipo->numero_serie})\n" .
                "Tipo: {$futuro->tipoMantenimiento->nombre}\n" .
                "Fecha: {$futuro->fecha_mantenimiento}\n" .
                "Estado: {$estadoAnterior} → {$estadoNuevo}";

            Bitacora::create([
                'user_id' => null, 
                'nombre_usuario' => 'Sistema Automático',
                'accion' => 'Automatización de mantenimientos',
                'modulo' => 'Mantenimiento',
                'descripcion' => $descripcion,
            ]);

            $this->info("Mantenimiento creado con ID {$nuevo->id} desde Futuro ID {$futuro->id}. Equipo ID {$equipo->id} marcado como En Mantenimiento.");
        }

        $this->info('Proceso completado.');
    }
}