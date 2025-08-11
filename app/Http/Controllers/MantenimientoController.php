<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Equipo;
use App\Models\Estado;
use App\Models\Mantenimiento;
use App\Models\TipoMantenimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MantenimientoController extends Controller
{
    /**
     * Listar todos los mantenimientos con paginación y relaciones.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);

        $query = Mantenimiento::with([
            'equipo.modelo.marca',
            'equipo.estado',
            'tipoMantenimiento',
            'usuario',
            'futuroMantenimiento'
        ]);

        // Filtro por equipo_id (opcional)
        if ($request->filled('equipo_id')) {
            $query->where('equipo_id', $request->equipo_id);
        }

        // Filtro por tipo_id
        if ($request->filled('tipo_id')) {
            $query->where('tipo_id', $request->tipo_id);
        }

        if ($request->filled('estado_id')) {
            if ($request->estado_id == 2) {
                // Mantenimientos en progreso (sin fecha final)
                $query->whereNull('fecha_mantenimiento_final');
            } else {
                // Mantenimientos finalizados con estado específico
                $query->where('estado_equipo_final', $request->estado_id)
                    ->whereNotNull('fecha_mantenimiento_final');
            }
        }
        // Filtro por rango de fechas
        if ($request->filled('fecha_inicio')) {
            $query->where('fecha_mantenimiento', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->where('fecha_mantenimiento', '<=', $request->fecha_fin);
        }

        // Filtro por vida útil
        if ($request->filled('vida_util_min')) {
            $query->where('vida_util', '>=', $request->vida_util_min);
        }
        if ($request->filled('vida_util_max')) {
            $query->where('vida_util', '<=', $request->vida_util_max);
        }

        // Filtro por búsqueda general
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->whereHas('equipo', function ($q2) use ($search) {
                    $q2->where('numero_serie', 'like', "%{$search}%");
                })
                    ->orWhereHas('tipoMantenimiento', function ($q3) use ($search) {
                        $q3->where('nombre', 'like', "%{$search}%");
                    })
                    ->orWhereHas('usuario', function ($q4) use ($search) {
                        $q4->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhere('comentario', 'like', "%{$search}%")
                    ->orWhere('vida_util', 'like', "%{$search}%");
            });
        }

        $mantenimientos = $query->orderBy('fecha_mantenimiento', 'desc')
            ->paginate($perPage);

        return response()->json($mantenimientos);
    }


    /**
     * Mostrar un mantenimiento específico.
     */
    public function show($id)
    {
        $mantenimiento = Mantenimiento::with([
            'equipo.modelo.marca',
            'tipoMantenimiento',
            'usuario',
            'futuroMantenimiento'
        ])->findOrFail($id);

        return response()->json($mantenimiento);
    }

    /**
     * Crear un nuevo mantenimiento.
     */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'equipo_id' => ['required', 'exists:equipos,id'],
            'fecha_mantenimiento' => ['required', 'date', 'after_or_equal:today'],
            'hora_mantenimiento_inicio' => ['required', 'date_format:H:i'],
            'fecha_mantenimiento_final' => ['required', 'date', 'after_or_equal:fecha_mantenimiento'],
            'hora_mantenimiento_final' => ['required', 'date_format:H:i'],
            'detalles' => ['nullable', 'string'],
            'tipo_id' => ['required', 'exists:tipo_mantenimientos,id'],
            'user_id' => ['required', 'exists:users,id'],
            'futuro_mantenimiento_id' => ['nullable', 'exists:futuro_mantenimientos,id'],
            'vida_util' => ['nullable', 'integer', 'min:0'],
        ], [
            'fecha_mantenimiento_final.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha de inicio',
            'hora_mantenimiento_final.required' => 'La hora final es requerida cuando se especifica fecha final'
        ]);

        // Validar que la fecha/hora final sea mayor que la inicial
        $fechaInicio = Carbon::parse($validated['fecha_mantenimiento'] . ' ' . $validated['hora_mantenimiento_inicio']);
        $fechaFin = Carbon::parse($validated['fecha_mantenimiento_final'] . ' ' . $validated['hora_mantenimiento_final']);

        if ($fechaFin <= $fechaInicio) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha/hora final debe ser posterior a la fecha/hora de inicio'
            ], 422);
        }

        // Verificar si el equipo está reservado en ese rango
        $reservasConflictivas = DB::table('reserva_equipos as re')
            ->join('equipo_reserva as er', 're.id', '=', 'er.reserva_equipo_id')
            ->where('er.equipo_id', $validated['equipo_id'])
            ->whereIn('re.estado', ['Aprobada', 'Pendiente'])
            ->where(function ($query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween('re.fecha_reserva', [$fechaInicio, $fechaFin])
                    ->orWhereBetween('re.fecha_entrega', [$fechaInicio, $fechaFin])
                    ->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                        $q->where('re.fecha_reserva', '<=', $fechaInicio)
                            ->where('re.fecha_entrega', '>=', $fechaFin);
                    });
            })
            ->exists();

        if ($reservasConflictivas) {
            $reservaInfo = DB::table('reserva_equipos as re')
                ->join('equipo_reserva as er', 're.id', '=', 'er.reserva_equipo_id')
                ->join('users', 're.user_id', '=', 'users.id')
                ->where('er.equipo_id', $validated['equipo_id'])
                ->whereIn('re.estado', ['Aprobada', 'Pendiente'])
                ->where(function ($query) use ($fechaInicio, $fechaFin) {
                    $query->whereBetween('re.fecha_reserva', [$fechaInicio, $fechaFin])
                        ->orWhereBetween('re.fecha_entrega', [$fechaInicio, $fechaFin])
                        ->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                            $q->where('re.fecha_reserva', '<=', $fechaInicio)
                                ->where('re.fecha_entrega', '>=', $fechaFin);
                        });
                })
                ->select('re.fecha_reserva', 're.fecha_entrega', 'users.first_name', 'users.last_name')
                ->first();

            return response()->json([
                'success' => false,
                'message' => 'El equipo está reservado durante el período solicitado',
                'conflict_info' => [
                    'fecha_inicio' => $reservaInfo->fecha_reserva,
                    'fecha_fin' => $reservaInfo->fecha_entrega,
                    'reservado_por' => $reservaInfo->first_name . ' ' . $reservaInfo->last_name
                ]
            ], 409);
        }

        // Verificar si el equipo ya está en mantenimiento
        $equipo = Equipo::find($validated['equipo_id']);
        if ($equipo->estado_id == 2) { // 2 = En Mantenimiento
            return response()->json([
                'success' => false,
                'message' => 'El equipo ya se encuentra en mantenimiento actualmente'
            ], 409);
        }

        // Verificar conflictos con otros mantenimientos programados
        $mantenimientosConflictivos = DB::table('mantenimientos')
            ->where('equipo_id', $validated['equipo_id'])
            ->where(function ($query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween('fecha_mantenimiento', [$fechaInicio, $fechaFin])
                    ->orWhereBetween('fecha_mantenimiento_final', [$fechaInicio, $fechaFin])
                    ->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                        $q->where('fecha_mantenimiento', '<=', $fechaInicio)
                            ->where('fecha_mantenimiento_final', '>=', $fechaFin);
                    });
            })
            ->exists();

        if ($mantenimientosConflictivos) {
            return response()->json([
                'success' => false,
                'message' => 'El equipo ya tiene un mantenimiento programado en ese horario'
            ], 409);
        }

        DB::beginTransaction();

        try {
            $equipo = Equipo::with('modelo.marca', 'estado')->find($validated['equipo_id']);
            $tipoMantenimiento = TipoMantenimiento::find($validated['tipo_id']);

            // Guardar el estado actual del equipo como estado inicial del nuevo mantenimiento
            $estadoAnterior = $equipo->estado->nombre ?? 'Desconocido';
            $estadoAnteriorId = $equipo->estado_id;

            // 1. Crear el mantenimiento con el estado inicial actual del equipo
            $mantenimiento = Mantenimiento::create([
                ...$validated,
                'estado_equipo_inicial' => $estadoAnteriorId,
                'estado_equipo_final' => null // Aún no tiene estado final
            ]);

            // 2. Actualizar estado del equipo a "En Mantenimiento" (2) solo si no está ya en ese estado
            if ($equipo->estado_id != 2) {
                $equipo->estado_id = 2;
                $equipo->save();
            }
            $estadoNuevo = Estado::find(2)->nombre;

            // Registrar en bitácora
            $user = Auth::user();
            $descripcion = ($user ? "{$user->first_name} {$user->last_name}" : 'Sistema') .
                " creó un mantenimiento:\n" .
                "Equipo: {$equipo->modelo->marca->nombre} {$equipo->modelo->nombre} (S/N: {$equipo->numero_serie})\n" .
                "Tipo: {$tipoMantenimiento->nombre}\n" .
                "Fecha inicio: {$validated['fecha_mantenimiento']} a las {$validated['hora_mantenimiento_inicio']}\n" .
                "Fecha fin: {$validated['fecha_mantenimiento_final']} a las {$validated['hora_mantenimiento_final']}\n" .
                "Estado: {$estadoAnterior} → {$estadoNuevo}\n" .
                "Detalles: " . ($validated['detalles'] ?? 'Ninguno');

            Bitacora::create([
                'user_id' => $user?->id,
                'nombre_usuario' => $user ? "{$user->first_name} {$user->last_name}" : 'Sistema',
                'accion' => 'Creación de mantenimiento',
                'modulo' => 'Mantenimiento',
                'descripcion' => $descripcion,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento creado correctamente',
                'data' => [
                    'mantenimiento' => $mantenimiento,
                    'equipo' => $equipo->fresh()
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear mantenimiento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un mantenimiento existente.
     */
    public function update(Request $request, $id)
    {
        $mantenimiento = Mantenimiento::findOrFail($id);

        $validated = $request->validate([
            'equipo_id' => ['sometimes', 'required', 'exists:equipos,id'],
            'fecha_mantenimiento' => ['sometimes', 'required', 'date'],
            'hora_mantenimiento_inicio' => ['sometimes', 'required', 'date_format:H:i'],
            //'hora_mantenimiento_final' => ['sometimes', 'required', 'date_format:H:i:s', 'after_or_equal:hora_mantenimiento_inicio'],
            'detalles' => ['nullable', 'string'],
            'tipo_id' => ['sometimes', 'required', 'exists:tipo_mantenimientos,id'],
            'user_id' => ['sometimes', 'required', 'exists:users,id'],
            'futuro_mantenimiento_id' => ['nullable', 'exists:futuro_mantenimientos,id'],
        ]);

        $mantenimiento->update($validated);

        return response()->json([
            'message' => 'Mantenimiento actualizado correctamente',
            'data' => $mantenimiento->load([
                'equipo.modelo.marca',
                'tipoMantenimiento',
                'usuario',
                'futuroMantenimiento'
            ]),
        ]);
    }

    /**
     * Eliminar un mantenimiento.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $mantenimiento = Mantenimiento::findOrFail($id);
            $equipo = Equipo::find($mantenimiento->equipo_id);

            // Guardar información para la bitácora antes de hacer cambios
            $user = Auth::user();
            $tipoMantenimiento = $mantenimiento->tipoMantenimiento;
            $estadoAnterior = $equipo->estado->nombre ?? 'Desconocido';

            // 1. Restaurar el estado anterior del equipo (el que tenía antes del mantenimiento)
            if ($mantenimiento->estado_equipo_inicial) {
                $equipo->estado_id = $mantenimiento->estado_equipo_inicial;
                $equipo->save();
            }
            $estadoNuevo = Estado::find($mantenimiento->estado_equipo_inicial)->nombre;

            // 2. Eliminar el mantenimiento
            $mantenimiento->delete();

            // Registrar en bitácora
            $descripcion = ($user ? "{$user->first_name} {$user->last_name}" : 'Sistema') .
                " eliminó un mantenimiento:\n" .
                "Equipo: {$equipo->modelo->marca->nombre} {$equipo->modelo->nombre} (S/N: {$equipo->numero_serie})\n" .
                "Tipo: {$tipoMantenimiento->nombre}\n" .
                "Estado: {$estadoAnterior} → {$estadoNuevo}";

            Bitacora::create([
                'user_id' => $user?->id,
                'nombre_usuario' => $user ? "{$user->first_name} {$user->last_name}" : 'Sistema',
                'accion' => 'Eliminación de mantenimiento',
                'modulo' => 'Mantenimiento',
                'descripcion' => $descripcion,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento eliminado correctamente y estado del equipo restaurado',
                'data' => [
                    'equipo' => $equipo->fresh()
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar mantenimiento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateVidaUtil($id, Request $request)
    {
        $request->validate([
            'vida_util' => 'required|integer|min:0',
            'comentario' => 'nullable|string|max:255'
        ]);

        DB::beginTransaction();
        try {
            $mantenimiento = Mantenimiento::with(['equipo.modelo.marca', 'tipoMantenimiento'])->findOrFail($id);
            $equipo = $mantenimiento->equipo;

            // Guardar valores anteriores para la bitácora
            $vidaUtilAnterior = $mantenimiento->vida_util ?? 0;
            $vidaUtilEquipoAnterior = $equipo->vida_util;
            $comentarioAnterior = $mantenimiento->comentario;

            // 1. Actualizar el mantenimiento
            $mantenimiento->update([
                'vida_util' => $request->vida_util,
                'comentario' => $request->comentario ?? $comentarioAnterior
            ]);

            if ($mantenimiento->fecha_mantenimiento_final) {
                // Mantenimiento ya existía → ajustar contribución
                $equipo->vida_util = ($equipo->vida_util - $vidaUtilAnterior) + $request->vida_util;
            } else {
                // Mantenimiento aún no registrado completamente → sumar directo
                $equipo->vida_util += $request->vida_util;
            }
            $equipo->save();


            // Registrar en bitácora
            $user = Auth::user();
            $descripcion = ($user ? "{$user->first_name} {$user->last_name}" : 'Sistema') .
                " actualizó la vida útil del mantenimiento:\n" .
                "Equipo: {$equipo->modelo->marca->nombre} {$equipo->modelo->nombre} (S/N: {$equipo->numero_serie})\n" .
                "Tipo de mantenimiento: {$mantenimiento->tipoMantenimiento->nombre}\n" .
                "Vida útil mantenimiento: {$vidaUtilAnterior} → {$request->vida_util} horas\n" .
                "Vida útil equipo: {$vidaUtilEquipoAnterior} → {$equipo->vida_util} horas\n" .
                "Comentario: " . ($request->comentario ?? ($comentarioAnterior ? "Sin cambios: $comentarioAnterior" : "Ninguno"));

            Bitacora::create([
                'user_id' => $user?->id,
                'nombre_usuario' => $user ? "{$user->first_name} {$user->last_name}" : 'Sistema',
                'accion' => 'Actualización de vida útil',
                'modulo' => 'Mantenimiento',
                'descripcion' => $descripcion,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vida útil actualizada correctamente',
                'data' => [
                    'mantenimiento' => $mantenimiento,
                    'equipo' => $equipo
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar vida útil: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar vida útil',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
