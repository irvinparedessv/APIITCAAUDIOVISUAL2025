<?php

namespace App\Http\Controllers;

use App\Models\Modelo;
use Illuminate\Http\Request;

class ModeloAccesorioController extends Controller
{
    public function index($modeloEquipoId)
    {
        $modelo = Modelo::with(['accesorios.marca'])->findOrFail($modeloEquipoId);
        
        return $modelo->accesorios->map(function ($accesorio) {
            return [
                'id' => $accesorio->id,
                'nombre' => $accesorio->nombre,
                'nombre_marca' => $accesorio->marca->nombre ?? null,
            ];
        });
    }

    public function listarInsumos()
    {
        $insumos = Modelo::with('marca')
            ->whereHas('equipos', function ($q) {
                $q->where('es_componente', true)
                  ->where('is_deleted', false);
            })
            ->where('is_deleted', false)
            ->get()
            ->map(function ($modelo) {
                return [
                    'id' => $modelo->id,
                    'nombre' => $modelo->nombre,
                    'nombre_marca' => $modelo->marca->nombre ?? null,
                ];
            });

        return response()->json($insumos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'modelo_equipo_id' => 'required|exists:modelos,id',
            'modelo_insumo_ids' => 'required|array',
            'modelo_insumo_ids.*' => 'exists:modelos,id',
        ]);

        $modeloEquipo = Modelo::with(['equipos.tipoEquipo.categoria'])
                        ->findOrFail($request->modelo_equipo_id);

        // Verificar que el modelo tenga equipos asociados
        if ($modeloEquipo->equipos->isEmpty()) {
            return response()->json(['error' => 'El modelo no tiene equipos asociados.'], 422);
        }

        // Verificar que sea de tipo Equipo
        $tipoEquipo = $modeloEquipo->equipos->first()->tipoEquipo;
        
        if (!$tipoEquipo || $tipoEquipo->categoria->nombre !== 'Equipo') {
            return response()->json(['error' => 'Solo se pueden asociar accesorios a modelos de tipo Equipo.'], 422);
        }

        // Verificar que los insumos sean válidos
        $insumosInvalidos = Modelo::whereIn('id', $request->modelo_insumo_ids)
            ->where(function($query) {
                $query->whereDoesntHave('equipos', function ($q) {
                        $q->where('es_componente', true);
                    })
                    ->orWhere('is_deleted', true);
            })
            ->exists();

        if ($insumosInvalidos) {
            return response()->json(['error' => 'Solo se pueden asociar modelos de tipo Insumo no eliminados.'], 422);
        }

        // Sincronizar accesorios
        $modeloEquipo->accesorios()->sync($request->modelo_insumo_ids);

        return response()->json([
            'message' => 'Accesorios asociados correctamente',
            'data' => $modeloEquipo->accesorios()->with('marca')->get()
        ]);
    }
}