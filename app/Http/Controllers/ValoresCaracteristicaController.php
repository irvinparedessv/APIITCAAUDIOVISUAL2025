<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\ValoresCaracteristica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValoresCaracteristicaController extends Controller
{
    public function index($equipoId)
    {
        $valores = ValoresCaracteristica::with('caracteristica')
            ->where('equipo_id', $equipoId)
            ->get();

        return response()->json($valores);
    }

    public function store(Request $request)
    {
        $request->validate([
            'equipo_id' => 'required|exists:equipos,id',
            'caracteristica_id' => 'required|exists:caracteristicas,id',
            'valor' => 'required|string', // Validar tipo de dato podría ser más avanzado según 'tipo_dato'
        ]);

        // Si quieres evitar duplicados para un mismo equipo y característica:
        $valorExistente = ValoresCaracteristica::where('equipo_id', $request->equipo_id)
            ->where('caracteristica_id', $request->caracteristica_id)
            ->first();

        if ($valorExistente) {
            $valorExistente->valor = $request->valor;
            $valorExistente->save();

            return response()->json([
                'message' => 'Valor actualizado',
                'data' => $valorExistente,
            ]);
        }

        $valor = ValoresCaracteristica::create($request->only('equipo_id', 'caracteristica_id', 'valor'));

        return response()->json([
            'message' => 'Valor creado',
            'data' => $valor,
        ], 201);
    }

    public function destroy($id)
    {
        $valor = ValoresCaracteristica::findOrFail($id);
        $valor->delete();

        return response()->json(['message' => 'Valor eliminado']);
    }

    public function caracteristicasConValoresPorEquipo($equipoId)
{
    $equipo = Equipo::with('tipoEquipo')->findOrFail($equipoId);
    $tipoEquipoId = $equipo->tipo_equipo_id;

    $caracteristicas = DB::table('caracteristicas as c')
        ->join('caracteristicas_tipo_equipo as cte', 'cte.caracteristica_id', '=', 'c.id')
        ->leftJoin('valores_caracteristicas as vc', function ($join) use ($equipoId) {
            $join->on('vc.caracteristica_id', '=', 'c.id')
                 ->where('vc.equipo_id', '=', $equipoId);
        })
        ->where('cte.tipo_equipo_id', $tipoEquipoId)
        ->where('c.is_deleted', false)
        ->select(
            'c.id',
            'c.nombre',
            'c.tipo_dato',
            DB::raw('COALESCE(vc.valor, "") as valor')
        )
        ->get();

    return response()->json($caracteristicas);
}

public function actualizarValoresPorEquipo(Request $request, $equipoId)
{
    $caracteristicas = $request->input('caracteristicas');

    // Validar que venga un array
    if (!is_array($caracteristicas)) {
        return response()->json(['error' => 'Datos inválidos para características'], 422);
    }

    // Validar cada característica
    foreach ($caracteristicas as $caracteristica) {
        if (!isset($caracteristica['id']) || !array_key_exists('valor', $caracteristica)) {
            return response()->json(['error' => 'Datos incompletos en características'], 422);
        }
    }

    DB::transaction(function () use ($equipoId, $caracteristicas) {
        $idsEnviados = collect($caracteristicas)
            ->pluck('id')
            ->filter()
            ->toArray();

        // Eliminar valores que no están en la nueva lista
        ValoresCaracteristica::where('equipo_id', $equipoId)
            ->whereNotIn('caracteristica_id', $idsEnviados)
            ->delete();

        // Insertar o actualizar
        foreach ($caracteristicas as $caracteristica) {
            $valor = trim($caracteristica['valor']);
            // Solo actualizar si valor no está vacío (si quieres permitir valores vacíos, quita esta condición)
            if ($valor !== '') {
                ValoresCaracteristica::updateOrCreate(
                    [
                        'equipo_id' => $equipoId,
                        'caracteristica_id' => $caracteristica['id'],
                    ],
                    ['valor' => $valor]
                );
            } else {
                // Si el valor está vacío, puedes decidir eliminar el registro
                ValoresCaracteristica::where('equipo_id', $equipoId)
                    ->where('caracteristica_id', $caracteristica['id'])
                    ->delete();
            }
        }
    });

    return response()->json([
        'message' => 'Valores de características actualizados correctamente'
    ]);
}





}
