<?php

namespace App\Http\Controllers;

use App\Models\ValoresCaracteristica;
use Illuminate\Http\Request;

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
}
