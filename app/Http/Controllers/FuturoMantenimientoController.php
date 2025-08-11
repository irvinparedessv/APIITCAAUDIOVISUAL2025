<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Equipo;
use App\Models\FuturoMantenimiento;
use App\Models\TipoMantenimiento;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FuturoMantenimientoController extends Controller
{
    /**
     * Listar todos los futuros mantenimientos con paginación.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = FuturoMantenimiento::with(['equipo.modelo.marca', 'usuario', 'tipoMantenimiento']);

        // Filtro por ID específico
        if ($request->filled('futuro_id')) {
            $query->where('id', $request->futuro_id);
        }

        // Filtro por tipo de mantenimiento
        if ($request->filled('tipo_id')) {
            $query->where('tipo_mantenimiento_id', $request->tipo_id);
        }

        // Filtro por rango de fechas
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_mantenimiento', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_mantenimiento', '<=', $request->fecha_fin);
        }

        // Búsqueda general
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('detalles', 'like', "%{$search}%")
                    ->orWhereHas('equipo', function ($q2) use ($search) {
                        $q2->where('numero_serie', 'like', "%{$search}%")
                            ->orWhereHas('modelo', function ($q3) use ($search) {
                                $q3->where('nombre', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('tipoMantenimiento', function ($q4) use ($search) {
                        $q4->where('nombre', 'like', "%{$search}%");
                    })
                    ->orWhereHas('usuario', function ($q5) use ($search) {
                        $q5->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $futuros = $query->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json($futuros);
    }

    /**
     * Mostrar un futuro mantenimiento específico.
     */
    public function show($id)
    {
        $futuro = FuturoMantenimiento::with(['equipo.modelo.marca', 'usuario', 'tipoMantenimiento', 'mantenimientos.tipoMantenimiento'])->findOrFail($id);

        return response()->json($futuro);
    }

    /**
     * Crear un nuevo futuro mantenimiento.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'equipo_id' => ['required', 'exists:equipos,id'],
            'tipo_mantenimiento_id' => ['required', 'exists:tipo_mantenimientos,id'],
            'fecha_mantenimiento' => ['required', 'date', 'after_or_equal:today'],
            'hora_mantenimiento_inicio' => ['required', 'date_format:H:i'],
            'fecha_mantenimiento_final' => ['required', 'date', 'after_or_equal:fecha_mantenimiento'],
            'hora_mantenimiento_final' => ['required', 'date_format:H:i'],
            'user_id' => ['required', 'exists:users,id'],
            'detalles' => ['nullable', 'string'],
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

        // 1. Validar que no exista otro futuro mantenimiento en el mismo rango
        $mantenimientoExistente = FuturoMantenimiento::where('equipo_id', $validated['equipo_id'])
            ->where(function ($query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween(DB::raw("TIMESTAMP(fecha_mantenimiento, hora_mantenimiento_inicio)"), [$fechaInicio, $fechaFin])
                    ->orWhereBetween(DB::raw("TIMESTAMP(fecha_mantenimiento_final, hora_mantenimiento_final)"), [$fechaInicio, $fechaFin])
                    ->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                        $q->where(DB::raw("TIMESTAMP(fecha_mantenimiento, hora_mantenimiento_inicio)"), '<=', $fechaInicio)
                            ->where(DB::raw("TIMESTAMP(fecha_mantenimiento_final, hora_mantenimiento_final)"), '>=', $fechaFin);
                    });
            })
            ->exists();

        if ($mantenimientoExistente) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un mantenimiento programado para este equipo en el rango de fechas seleccionado'
            ], 409);
        }

        // 2. Validar contra reservas existentes
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

        // 3. Validar contra mantenimientos activos
        $mantenimientoActivo = DB::table('mantenimientos')
            ->where('equipo_id', $validated['equipo_id'])
            ->whereNull('fecha_mantenimiento_final')
            ->exists();

        if ($mantenimientoActivo) {
            return response()->json([
                'success' => false,
                'message' => 'El equipo se encuentra actualmente en mantenimiento'
            ], 409);
        }

        DB::beginTransaction();

        try {
            // Crear el futuro mantenimiento
            $futuro = FuturoMantenimiento::create($validated);

            // Registrar en bitácora
            $equipo = Equipo::with('modelo.marca')->find($validated['equipo_id']);
            $tipoMantenimiento = TipoMantenimiento::find($validated['tipo_mantenimiento_id']);
            $user = User::find($validated['user_id']);

            Bitacora::create([
                'user_id' => $user->id,
                'nombre_usuario' => $user->first_name . ' ' . $user->last_name,
                'accion' => 'Programación de futuro mantenimiento',
                'modulo' => 'Mantenimiento',
                'descripcion' => "Se programó un mantenimiento futuro para el equipo: " .
                    "{$equipo->modelo->marca->nombre} {$equipo->modelo->nombre} (S/N: {$equipo->numero_serie}) " .
                    "desde {$validated['fecha_mantenimiento']} {$validated['hora_mantenimiento_inicio']} " .
                    "hasta {$validated['fecha_mantenimiento_final']} {$validated['hora_mantenimiento_final']}. " .
                    "Tipo: {$tipoMantenimiento->nombre}"
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Futuro mantenimiento programado correctamente',
                'data' => $futuro->load(['equipo', 'tipoMantenimiento'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al programar futuro mantenimiento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al programar el mantenimiento futuro',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Actualizar un futuro mantenimiento existente.
     */
    public function update(Request $request, $id)
{
    $futuro = FuturoMantenimiento::findOrFail($id);

    $validated = $request->validate([
        'equipo_id' => ['sometimes', 'required', 'exists:equipos,id'],
        'tipo_mantenimiento_id' => ['sometimes', 'required', 'exists:tipo_mantenimientos,id'],
        'fecha_mantenimiento' => ['sometimes', 'required', 'date', 'after_or_equal:today'],
        'hora_mantenimiento_inicio' => ['sometimes', 'required', 'date_format:H:i'],
        'fecha_mantenimiento_final' => ['required', 'date', 'after_or_equal:fecha_mantenimiento'],
        'hora_mantenimiento_final' => ['required', 'date_format:H:i'],
        'user_id' => ['required', 'exists:users,id'],
        'detalles' => ['nullable', 'string'],
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

    // 1. Validar que no exista otro futuro mantenimiento en el mismo rango (excluyendo el actual)
    $mantenimientoExistente = FuturoMantenimiento::where('equipo_id', $validated['equipo_id'])
        ->where('id', '!=', $id)
        ->where(function ($query) use ($fechaInicio, $fechaFin) {
            $query->whereBetween(DB::raw("TIMESTAMP(fecha_mantenimiento, hora_mantenimiento_inicio)"), [$fechaInicio, $fechaFin])
                ->orWhereBetween(DB::raw("TIMESTAMP(fecha_mantenimiento_final, hora_mantenimiento_final)"), [$fechaInicio, $fechaFin])
                ->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                    $q->where(DB::raw("TIMESTAMP(fecha_mantenimiento, hora_mantenimiento_inicio)"), '<=', $fechaInicio)
                        ->where(DB::raw("TIMESTAMP(fecha_mantenimiento_final, hora_mantenimiento_final)"), '>=', $fechaFin);
                });
        })
        ->exists();

    if ($mantenimientoExistente) {
        return response()->json([
            'success' => false,
            'message' => 'Ya existe un mantenimiento programado para este equipo en el rango de fechas seleccionado'
        ], 409);
    }

    // 2. Validar contra reservas existentes
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

    // 3. Validar contra mantenimientos activos
    $mantenimientoActivo = DB::table('mantenimientos')
        ->where('equipo_id', $validated['equipo_id'])
        ->whereNull('fecha_mantenimiento_final')
        ->exists();

    if ($mantenimientoActivo) {
        return response()->json([
            'success' => false,
            'message' => 'El equipo se encuentra actualmente en mantenimiento'
        ], 409);
    }

    DB::beginTransaction();

    try {
        // Actualizar el futuro mantenimiento
        $futuro->update($validated);

        // Registrar en bitácora
        $equipo = Equipo::with('modelo.marca')->find($validated['equipo_id']);
        $tipoMantenimiento = TipoMantenimiento::find($validated['tipo_mantenimiento_id']);
        $user = User::find($validated['user_id']);

        Bitacora::create([
            'user_id' => $user->id,
            'nombre_usuario' => $user->first_name . ' ' . $user->last_name,
            'accion' => 'Actualización de futuro mantenimiento',
            'modulo' => 'Mantenimiento',
            'descripcion' => "Se actualizó el mantenimiento futuro para el equipo: " .
                "{$equipo->modelo->marca->nombre} {$equipo->modelo->nombre} (S/N: {$equipo->numero_serie}) " .
                "desde {$validated['fecha_mantenimiento']} {$validated['hora_mantenimiento_inicio']} " .
                "hasta {$validated['fecha_mantenimiento_final']} {$validated['hora_mantenimiento_final']}. " .
                "Tipo: {$tipoMantenimiento->nombre}"
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Futuro mantenimiento actualizado correctamente',
            'data' => $futuro->load(['equipo', 'tipoMantenimiento'])
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error al actualizar futuro mantenimiento: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar el mantenimiento futuro',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Eliminar un futuro mantenimiento.
     */
    public function destroy($id)
    {
        $futuro = FuturoMantenimiento::findOrFail($id);
        $futuro->delete();

        return response()->json(['message' => 'Futuro mantenimiento eliminado correctamente']);
    }
}
