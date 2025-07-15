<?php

namespace App\Http\Controllers;

use App\Models\TipoEquipo;
use Illuminate\Http\Request;

class TipoEquipoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tiposEquipos = TipoEquipo::where('is_deleted', false)->get();
        return response()->json($tiposEquipos);
    }

    public function obtenerTipo(Request $request)
    {
        $tiposEquipos = TipoEquipo::where('is_deleted', false)->paginate(10);
        return response()->json($tiposEquipos);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $request->validate([
        'nombre' => 'required|string|max:255',
    ]);

    $nombre = $request->input('nombre');

    // Comparación insensible a mayúsculas/minúsculas
    $existe = TipoEquipo::whereRaw('LOWER(nombre) = ?', [strtolower($nombre)])
        ->where('is_deleted', false)
        ->exists();

    if ($existe) {
        return response()->json([
            'message' => 'El nombre ya existe.'
        ], 422); // Código HTTP 422: Unprocessable Entity
    }

    $tipoEquipo = TipoEquipo::create([
        'nombre' => $nombre,
        'is_deleted' => false,
    ]);

    return response()->json($tipoEquipo, 201);
}


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tipoEquipo = TipoEquipo::where('id', $id)->where('is_deleted', false)->first();

        if (!$tipoEquipo) {
            return response()->json(['error' => 'Tipo de equipo no encontrado'], 404);
        }

        return response()->json($tipoEquipo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
{
    $request->validate([
        'nombre' => 'required|string|max:255',
    ]);

    $tipoEquipo = TipoEquipo::where('id', $id)->where('is_deleted', false)->first();

    if (!$tipoEquipo) {
        return response()->json(['error' => 'Tipo de equipo no encontrado'], 404);
    }

    $nombreNuevo = $request->input('nombre');

    // Comparación insensible a mayúsculas/minúsculas, excluyendo el mismo ID
    $existe = TipoEquipo::whereRaw('LOWER(nombre) = ?', [strtolower($nombreNuevo)])
        ->where('id', '!=', $id)
        ->where('is_deleted', false)
        ->exists();

    if ($existe) {
        return response()->json(['message' => 'El nombre ya existe.'], 422);
    }

    $tipoEquipo->nombre = $nombreNuevo;
    $tipoEquipo->save();

    return response()->json($tipoEquipo);
}


    /**
     * Remove the specified resource from storage (eliminado lógico).
     */
    public function destroy(string $id)
    {
        $tipoEquipo = TipoEquipo::where('id', $id)->where('is_deleted', false)->first();

        if (!$tipoEquipo) {
            return response()->json(['error' => 'Tipo de equipo no encontrado'], 404);
        }

        $tipoEquipo->is_deleted = true;
        $tipoEquipo->save();

        return response()->json(['message' => 'Tipo de equipo eliminado con éxito'], 200);
    }
}
