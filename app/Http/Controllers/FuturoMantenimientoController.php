<?php

namespace App\Http\Controllers;

use App\Models\FuturoMantenimiento;
use Illuminate\Http\Request;

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
                $q->whereHas('equipo', function ($q2) use ($search) {
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
            'fecha_mantenimiento' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($request) {
                    $existing = FuturoMantenimiento::where('equipo_id', $request->equipo_id)
                        ->whereDate('fecha_mantenimiento', $value)
                        ->exists();

                    if ($existing) {
                        $fail('Ya existe un mantenimiento programado para este equipo en la fecha seleccionada.');
                    }
                }
            ],
            'user_id' => ['required'],
            'hora_mantenimiento_inicio' => ['required', 'date_format:H:i'],
            'fecha_mantenimiento_final' => 'nullable|date',
            'hora_mantenimiento_final' => ['required', 'date_format:H:i'],
            'detalles' => ['nullable', 'string'],
        ]);

        $futuro = FuturoMantenimiento::create($validated);

        return response()->json([
            'message' => 'Futuro mantenimiento creado correctamente',
            'data' => $futuro->load(['equipo', 'tipoMantenimiento']),
        ], 201);
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
            'fecha_mantenimiento' => ['sometimes', 'required', 'date'],
            'user_id' => ['required'],
            'hora_mantenimiento_inicio' => ['sometimes', 'required', 'date_format:H:i'],
            'fecha_mantenimiento_final' => 'nullable|date',
            'hora_mantenimiento_final' => ['required', 'date_format:H:i'],
            'detalles' => ['nullable', 'string'],
        ]);

        $futuro->update($validated);

        return response()->json([
            'message' => 'Futuro mantenimiento actualizado correctamente',
            'data' => $futuro->load(['equipo', 'tipoMantenimiento']),
        ]);
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
