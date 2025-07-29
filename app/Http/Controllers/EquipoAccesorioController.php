<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Equipo;
use App\Models\ModeloAccesorio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            ->where('estado_id', 1) // Added condition for estado = 1
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

    // Retorna el equipo con sus insumos (modelo y marca incluidos)
    public function show($id)
    {
        $equipo = Equipo::with(['insumos.modelo.marca', 'modelo', 'marca'])
            ->findOrFail($id);

        return response()->json($equipo);
    }

    // ✅ Asociar un insumo a un equipo
    public function store(Request $request, $equipoId)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'modelo_id' => 'required|exists:modelos,id',
            ]);

            $equipo = Equipo::with('modelo.marca')->findOrFail($equipoId);

            // Buscar un insumo físico disponible
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

            // Guardar estado anterior para bitácora
            $serieAnterior = $insumo->serie_asociada;

            // Actualizar el numero_serie del insumo
            $insumo->serie_asociada = $equipo->numero_serie;
            $insumo->save();

            // Registrar en bitácora
            $user = Auth::user();
            $descripcion = ($user ? "{$user->first_name} {$user->last_name}" : 'Sistema') .
                " asoció el insumo al equipo:\n" .
                "Equipo: {$equipo->modelo->marca->nombre} {$equipo->modelo->nombre} (S/N: {$equipo->numero_serie})\n" .
                "Insumo: {$insumo->modelo->marca->nombre} {$insumo->modelo->nombre} (ID: {$insumo->id})\n" .
                "Serie asociada: " . ($serieAnterior ?? 'Ninguna') . " → {$equipo->numero_serie}";

            Bitacora::create([
                'user_id' => $user?->id,
                'nombre_usuario' => $user ? "{$user->first_name} {$user->last_name}" : 'Sistema',
                'accion' => 'Asociación',
                'modulo' => 'Inventario',
                'descripcion' => $descripcion,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Insumo asociado correctamente y número de serie actualizado.',
                'insumo' => $insumo,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al asociar insumo: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al asociar el insumo',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // ✅ Quitar un insumo de un equipo
    public function destroy($equipoId, $insumoId)
    {
        DB::beginTransaction();

        try {
            $equipo = Equipo::with('modelo.marca')->findOrFail($equipoId);
            $insumo = Equipo::with('modelo.marca')->findOrFail($insumoId);

            // Verificar asociación
            if (!$equipo->insumos->contains($insumoId)) {
                return response()->json(['message' => 'El insumo no está asociado a este equipo.'], 404);
            }

            // Guardar info para bitácora antes de eliminar
            $serieAsociada = $insumo->serie_asociada;

            // Eliminar la relación
            $equipo->insumos()->detach($insumoId);

            // Limpiar el campo `serie_asociada`
            $insumo->serie_asociada = null;
            $insumo->save();

            // Registrar en bitácora
            $user = Auth::user();
            $descripcion = ($user ? "{$user->first_name} {$user->last_name}" : 'Sistema') .
                " desasoció el insumo del equipo:\n" .
                "Equipo: {$equipo->modelo->marca->nombre} {$equipo->modelo->nombre} (S/N: {$equipo->numero_serie})\n" .
                "Insumo: {$insumo->modelo->marca->nombre} {$insumo->modelo->nombre} (ID: {$insumo->id})\n" .
                "Serie asociada previa: {$serieAsociada} → Ninguna";

            Bitacora::create([
                'user_id' => $user?->id,
                'nombre_usuario' => $user ? "{$user->first_name} {$user->last_name}" : 'Sistema',
                'accion' => 'Desasociación',
                'modulo' => 'Inventario',
                'descripcion' => $descripcion,
            ]);

            DB::commit();

            return response()->json(['message' => 'Insumo eliminado correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al desasociar insumo: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al desasociar el insumo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
