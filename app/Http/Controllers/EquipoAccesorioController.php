<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquipoAccesorioController extends Controller
{
    // Listar insumos (accesorios) asignados a un equipo
    public function index($equipoId)
    {
        $equipo = Equipo::with('insumos')->findOrFail($equipoId);
        return response()->json($equipo->insumos);
    }

    // Listar insumos que NO están asignados a este equipo
   public function insumosNoAsignados($equipoId)
{
    $equipo = Equipo::findOrFail($equipoId);

    // IDs de insumos ya asignados
    $insumosAsignadosIds = $equipo->insumos()->pluck('insumo_id')->toArray();

    // Insumos que no están asignados a ningún equipo (si quieres, o solo que no estén asignados a este equipo)
    // Para que NO aparezcan insumos asignados a cualquier equipo, haz:

    $insumosNoAsignados = Equipo::where('es_componente', true)
        ->whereNotIn('id', $insumosAsignadosIds)
        ->get();

    return response()->json($insumosNoAsignados);
}


    // Asociar un insumo a un equipo
    public function store(Request $request, $equipoId)
    {
        $request->validate([
            'insumo_id' => 'required|exists:equipos,id|different:' . $equipoId,
        ]);

        // Verificar que el insumo no esté asignado a otro equipo
        $asignado = DB::table('equipo_accesorio')->where('insumo_id', $request->insumo_id)->exists();

        if ($asignado) {
            return response()->json(['message' => 'El insumo ya está asignado a otro equipo.'], 422);
        }

        $equipo = Equipo::findOrFail($equipoId);

        $equipo->insumos()->attach($request->insumo_id);

        return response()->json(['message' => 'Insumo asociado correctamente.'], 201);
    }


    // Quitar un insumo de un equipo
    public function destroy($equipoId, $insumoId)
    {
        $equipo = Equipo::findOrFail($equipoId);
        $equipo->insumos()->detach($insumoId);

        return response()->json(['message' => 'Insumo eliminado correctamente.']);
    }
}
