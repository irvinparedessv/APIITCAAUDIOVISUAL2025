<?php

namespace App\Http\Controllers;

use App\Models\TipoMantenimiento;
use Illuminate\Http\Request;

class TipoMantenimientoController extends Controller
{
    /**
     * Mostrar todos los tipos de mantenimiento.
     */
    public function index()
{
    $tipos = TipoMantenimiento::orderBy('nombre')->get();
    return response()->json([
        'message' => 'Lista de tipos de mantenimiento',
        'data' => $tipos,
    ]);
}


    /**
     * Crear un nuevo tipo de mantenimiento.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:tipo_mantenimientos,nombre',
            'estado' => 'required|boolean',
        ]);

        $tipo = TipoMantenimiento::create($validated);

        return response()->json([
            'message' => 'Tipo de mantenimiento creado correctamente',
            'data' => $tipo,
        ], 201);
    }

    /**
     * Mostrar un tipo de mantenimiento específico.
     */
    public function show($id)
    {
        $tipo = TipoMantenimiento::findOrFail($id);
        return response()->json($tipo);
    }

    /**
     * Actualizar un tipo de mantenimiento.
     */
    public function update(Request $request, $id)
    {
        $tipo = TipoMantenimiento::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:tipo_mantenimientos,nombre,' . $tipo->id,
            'estado' => 'required|boolean',
        ]);

        $tipo->update($validated);

        return response()->json([
            'message' => 'Tipo de mantenimiento actualizado correctamente',
            'data' => $tipo,
        ]);
    }

    /**
     * Eliminar un tipo de mantenimiento.
     */
    public function destroy($id)
    {
        $tipo = TipoMantenimiento::findOrFail($id);
        $tipo->delete();

        return response()->json([
            'message' => 'Tipo de mantenimiento eliminado correctamente',
        ]);
    }
}
