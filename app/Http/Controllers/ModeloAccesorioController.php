<?php

namespace App\Http\Controllers;

use App\Models\Modelo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModeloAccesorioController extends Controller
{
    public function index($modeloEquipoId)
    {
        $modelo = Modelo::with(['accesorios.marca', 'equipos'])->findOrFail($modeloEquipoId);

        // IDs de equipos físicos basados en este modelo
        $equiposIds = $modelo->equipos->pluck('id')->toArray();

        // Buscar insumos que están siendo usados físicamente por algún equipo
        $insumoIdsEnUso = DB::table('equipo_accesorio as ea')
            ->join('equipos as insumo_fisico', 'insumo_fisico.id', '=', 'ea.insumo_id')
            ->whereIn('ea.equipo_id', $equiposIds)
            ->select('insumo_fisico.modelo_id')
            ->distinct()
            ->pluck('insumo_fisico.modelo_id')
            ->toArray();

        return $modelo->accesorios->map(function ($accesorio) use ($insumoIdsEnUso) {
            return [
                'id' => $accesorio->id,
                'nombre' => $accesorio->nombre,
                'nombre_marca' => $accesorio->marca->nombre ?? null,
                'bloqueado' => in_array($accesorio->id, $insumoIdsEnUso), // ← NUEVO
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
            'modelo_insumo_ids' => 'nullable|array',
            'modelo_insumo_ids.*' => 'exists:modelos,id',
        ]);

        $modeloEquipo = Modelo::with(['equipos.tipoEquipo.categoria', 'equipos', 'accesorios'])
            ->findOrFail($request->modelo_equipo_id);

        // Validar que tiene al menos un equipo asociado
        if ($modeloEquipo->equipos->isEmpty()) {
            return response()->json(['error' => 'El modelo no tiene equipos asociados.'], 422);
        }

        // Validar que el tipo de equipo sea "Equipo"
        $tipoEquipo = $modeloEquipo->equipos->first()->tipoEquipo;
        if (!$tipoEquipo || $tipoEquipo->categoria->nombre !== 'Equipo') {
            return response()->json(['error' => 'Solo se pueden asociar accesorios a modelos de tipo Equipo.'], 422);
        }

        // Validar que los insumos nuevos sean válidos
        $insumosInvalidos = Modelo::whereIn('id', $request->modelo_insumo_ids)
            ->where(function ($query) {
                $query->whereDoesntHave('equipos', function ($q) {
                    $q->where('es_componente', true);
                })->orWhere('is_deleted', true);
            })->exists();

        if ($insumosInvalidos) {
            return response()->json(['error' => 'Solo se pueden asociar modelos de tipo Insumo no eliminados.'], 422);
        }

        // Obtener accesorios actuales y nuevos
        $idsActuales = $modeloEquipo->accesorios->pluck('id')->toArray();
        $idsNuevos = $request->modelo_insumo_ids ?? [];

        // Detectar cuáles se están eliminando
        $aEliminar = array_diff($idsActuales, $idsNuevos);

        if (!empty($aEliminar)) {
            // Verificar si hay equipos físicos con esos insumos ya asignados
            $insumosUsados = DB::table('equipos as eq')
                ->join('equipo_accesorio as ea', 'ea.equipo_id', '=', 'eq.id')
                ->join('equipos as insumo_fisico', 'insumo_fisico.id', '=', 'ea.insumo_id')
                ->where('eq.modelo_id', $modeloEquipo->id)
                ->whereIn('insumo_fisico.modelo_id', $aEliminar)
                ->select('insumo_fisico.modelo_id')
                ->distinct()
                ->pluck('insumo_fisico.modelo_id')
                ->toArray();

            if (!empty($insumosUsados)) {
                $nombresInsumos = Modelo::whereIn('id', $insumosUsados)->pluck('nombre')->toArray();
                return response()->json([
                    'error' => 'No se pueden eliminar insumos que ya están asignados a equipos físicos.',
                    'insumos_en_uso' => $nombresInsumos,
                ], 422);
            }
        }

        // Si todo está bien, sincronizar los nuevos accesorios
        $modeloEquipo->accesorios()->sync($idsNuevos);

        return response()->json([
            'message' => 'Accesorios asociados correctamente',
            'data' => $modeloEquipo->accesorios()->with('marca')->get()
        ]);
    }
}
