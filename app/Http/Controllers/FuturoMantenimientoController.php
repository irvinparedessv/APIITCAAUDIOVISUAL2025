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
        $perPage = $request->input('per_page', 10); // usa "per_page" para que coincida con el frontend

        $query = FuturoMantenimiento::with(['equipo.modelo.marca', 'tipoMantenimiento']);

        // Filtro por ID de equipo (opcional, ya lo tenías)
        if ($request->filled('equipo_id')) {
            $query->where('equipo_id', $request->equipo_id);
        }

        // 🔍 Filtro por texto en equipo o tipo
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('equipo', function ($q2) use ($search) {
                    $q2->where('numero_serie', 'like', "%{$search}%");
                })->orWhereHas('tipoMantenimiento', function ($q3) use ($search) {
                    $q3->where('nombre', 'like', "%{$search}%");
                });
            });
        }

        $futuros = $query->orderBy('id', 'Desc')->paginate($perPage);

        return response()->json($futuros);
    }

    /**
     * Mostrar un futuro mantenimiento específico.
     */
    public function show($id)
    {
        $futuro = FuturoMantenimiento::with(['equipo.modelo.marca', 'tipoMantenimiento', 'mantenimientos.tipoMantenimiento'])->findOrFail($id);

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
            'vida_util' => ['nullable', 'integer', 'min:0'],
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
            'vida_util' => ['nullable', 'integer', 'min:0'],
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
