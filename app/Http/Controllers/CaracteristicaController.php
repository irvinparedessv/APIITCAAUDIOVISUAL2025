<?php

namespace App\Http\Controllers;

use App\Models\Caracteristica;
use Illuminate\Http\Request;

class CaracteristicaController extends Controller
{
    public function index()
    {
        $caracteristicas = Caracteristica::activas()->get();
        return response()->json($caracteristicas);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|unique:caracteristicas,nombre',
            'tipo_dato' => 'required|in:string,integer,decimal,boolean',
        ]);

        $caracteristica = Caracteristica::create($request->only('nombre', 'tipo_dato'));
        return response()->json([
            'message' => 'Característica creada exitosamente',
            'data' => $caracteristica
        ], 201);
    }

    public function show($id)
    {
        $caracteristica = Caracteristica::findOrFail($id);
        return response()->json($caracteristica);
    }

    public function update(Request $request, $id)
    {
        $caracteristica = Caracteristica::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|unique:caracteristicas,nombre,' . $id,
            'tipo_dato' => 'required|in:string,integer,decimal,boolean',
        ]);

        $caracteristica->update($request->only('nombre', 'tipo_dato'));

        return response()->json([
            'message' => 'Característica actualizada',
            'data' => $caracteristica
        ]);
    }

    public function destroy($id)
    {
        $caracteristica = Caracteristica::findOrFail($id);
        // Borrado lógico
        $caracteristica->is_deleted = true;
        $caracteristica->save();

        return response()->json(['message' => 'Característica eliminada lógicamente']);
    }
}
