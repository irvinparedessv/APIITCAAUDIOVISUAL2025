<?php

namespace App\Http\Controllers;

use App\Models\Mantenimiento;
use Illuminate\Http\Request;

class MantenimientoController extends Controller
{
    /**
     * Listar todos los mantenimientos con paginación y relaciones.
     */
    public function index(Request $request)
{
    $perPage = $request->input('perPage', 10);

    $query = Mantenimiento::with([
        'equipo.modelo.marca', // para que modelo y marca también se incluyan
        'tipoMantenimiento',
        'usuario',
        'futuroMantenimiento'
    ]);

    if ($request->filled('equipo_id')) {
        $query->where('equipo_id', $request->equipo_id);
    }

    if ($request->filled('tipo_id')) {
        $query->where('tipo_mantenimiento_id', $request->tipo_id);
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
            'fecha_mantenimiento' => ['required', 'date'],
            'hora_mantenimiento_inicio' => ['required', 'date_format:H:i:s'],
            'hora_mantenimiento_final' => ['required', 'date_format:H:i:s', 'after_or_equal:hora_mantenimiento_inicio'],
            'detalles' => ['nullable', 'string'],
            'tipo_id' => ['required', 'exists:tipo_mantenimientos,id'],
            'user_id' => ['required', 'exists:users,id'],
            'futuro_mantenimiento_id' => ['nullable', 'exists:futuro_mantenimientos,id'],
            'vida_util' => ['nullable', 'integer', 'min:0'],
        ]);

        $mantenimiento = Mantenimiento::create($validated);

        return response()->json([
            'message' => 'Mantenimiento creado correctamente',
            'data' => $mantenimiento->load([
                'equipo.modelo.marca', 
                'tipoMantenimiento', 
                'usuario', 
                'futuroMantenimiento'
            ]),
        ], 201);
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
            'hora_mantenimiento_inicio' => ['sometimes', 'required', 'date_format:H:i:s'],
            'hora_mantenimiento_final' => ['sometimes', 'required', 'date_format:H:i:s', 'after_or_equal:hora_mantenimiento_inicio'],
            'detalles' => ['nullable', 'string'],
            'tipo_id' => ['sometimes', 'required', 'exists:tipo_mantenimientos,id'],
            'user_id' => ['sometimes', 'required', 'exists:users,id'],
            'futuro_mantenimiento_id' => ['nullable', 'exists:futuro_mantenimientos,id'],
            'vida_util' => ['nullable', 'integer', 'min:0'],
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
        $mantenimiento = Mantenimiento::findOrFail($id);
        $mantenimiento->delete();

        return response()->json(['message' => 'Mantenimiento eliminado correctamente']);
    }
}
