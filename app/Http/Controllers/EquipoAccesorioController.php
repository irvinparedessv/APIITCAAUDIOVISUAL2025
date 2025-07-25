<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\ModeloAccesorio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquipoAccesorioController extends Controller
{
    // ✅ Listar insumos (accesorios) asignados a un equipo
    public function index($equipoId)
    {
        $equipo = Equipo::with('insumos')->findOrFail($equipoId);
        return response()->json($equipo->insumos);
    }

    // ✅ Listar insumos que NO están asignados a este equipo
    public function insumosNoAsignados($equipoId)
    {
        $equipo = Equipo::findOrFail($equipoId);

        // Modelos ya asignados a este equipo
        $modelosAsignados = DB::table('equipo_accesorio')
            ->join('equipos as insumo', 'insumo.id', '=', 'equipo_accesorio.insumo_id')
            ->where('equipo_accesorio.equipo_id', $equipo->id)
            ->pluck('insumo.modelo_id')
            ->toArray();

        // IDs de modelos permitidos
        $modelosPermitidos = DB::table('modelo_accesorios')
            ->where('modelo_equipo_id', $equipo->modelo_id)
            ->pluck('modelo_insumo_id');

        // Insumos agrupados solo para modelos permitidos Y que NO estén asignados a este equipo
        $insumosAgrupados = Equipo::select('modelo_id', DB::raw('COUNT(*) as cantidad'))
            ->where('es_componente', true)
            ->where('is_deleted', 0)
            ->whereIn('modelo_id', $modelosPermitidos)
            ->whereNotIn('modelo_id', $modelosAsignados) // filtro para no mostrar modelos ya asignados
            ->whereNotIn('id', function ($query) {
                $query->select('insumo_id')->from('equipo_accesorio');
            })
            ->groupBy('modelo_id')
            ->with('modelo.marca')
            ->get();

        return response()->json($insumosAgrupados);
    }




    // ✅ Asociar un insumo a un equipo
    public function store(Request $request, $equipoId)
    {
        $request->validate([
            'modelo_id' => 'required|exists:modelos,id',
        ]);

        $equipo = Equipo::findOrFail($equipoId);

        // Buscar un insumo físico disponible del modelo que se seleccionó
        $insumo = Equipo::where('modelo_id', $request->modelo_id)
            ->where('es_componente', true)
            ->where('is_deleted', false)
            ->whereNotIn('id', function ($query) {
                $query->select('insumo_id')
                    ->from('equipo_accesorio');
            })
            ->first();

        if (!$insumo) {
            return response()->json(['message' => 'No hay insumos físicos disponibles para este modelo.'], 422);
        }

        // Validar que no esté asignado ya
        $asignado = DB::table('equipo_accesorio')
            ->where('insumo_id', $insumo->id)
            ->exists();

        if ($asignado) {
            return response()->json(['message' => 'El insumo ya está asignado a otro equipo.'], 422);
        }

        // Validar compatibilidad modelos
        $modeloRelacion = ModeloAccesorio::where('modelo_equipo_id', $equipo->modelo_id)
            ->where('modelo_insumo_id', $insumo->modelo_id)
            ->exists();

        if (!$modeloRelacion) {
            return response()->json(['message' => 'Este insumo no es compatible con este equipo.'], 422);
        }

        // Asociar insumo al equipo
        $equipo->insumos()->attach($insumo->id);

        // ACTUALIZAR el numero_serie del insumo para que sea igual al del equipo
        $insumo->serie_asociada = $equipo->numero_serie;
        $insumo->save();

        return response()->json([
            'message' => 'Insumo asociado correctamente y número de serie actualizado.',
            'insumo' => $insumo,
        ], 201);
    }

    // ✅ Quitar un insumo de un equipo
    public function destroy($equipoId, $insumoId)
    {
        $equipo = Equipo::findOrFail($equipoId);
        $equipo->insumos()->detach($insumoId);

        return response()->json(['message' => 'Insumo eliminado correctamente.']);
    }
}
