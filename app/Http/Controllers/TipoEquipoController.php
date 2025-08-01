<?php

namespace App\Http\Controllers;

use App\Models\TipoEquipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TipoEquipoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TipoEquipo::query()
            ->where('is_deleted', false)
            ->with('categoria'); // Opcional: carga relaciones necesarias

        // Búsqueda por nombre (si se envía el parámetro 'search')
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = strtolower($request->search);
            $query->whereRaw('LOWER(nombre) LIKE ?', ["%{$searchTerm}%"]);
        }

        // Paginación (si se envía 'limit')
        if ($request->has('limit')) {
            return response()->json($query->paginate($request->limit));
        }

        return response()->json($query->get());
    }

    public function obtenerTipo(Request $request)
{
    $query = TipoEquipo::with(['categoria', 'caracteristicas'])
        ->where('is_deleted', false);

    // Añade búsqueda si existe
    if ($request->has('search') && !empty($request->search)) {
        $searchTerm = strtolower($request->search);
        $query->whereRaw('LOWER(nombre) LIKE ?', ["%{$searchTerm}%"]);
    }

    return response()->json($query->paginate(10));
}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string',
            'categoria_id' => 'required|exists:categorias,id',
            'caracteristicas' => 'array',
            'caracteristicas.*.id' => 'sometimes|exists:caracteristicas,id',
            'caracteristicas.*.nombre' => 'sometimes|string',
            'caracteristicas.*.tipo_dato' => 'sometimes|in:string,integer,decimal,boolean',
        ]);

        // Validar nombre único manualmente entre activos
        $existe = TipoEquipo::whereRaw('LOWER(nombre) = ?', [strtolower($validated['nombre'])])
            ->where('is_deleted', false)
            ->exists();

        if ($existe) {
            return response()->json(['message' => 'El nombre ya existe.'], 422);
        }

        DB::beginTransaction();

        try {
            $tipoEquipo = TipoEquipo::create([
                'nombre' => $validated['nombre'],
                'categoria_id' => $validated['categoria_id'],
            ]);

            // Procesar características (igual que antes)
            $caracteristicasIds = [];

            if (isset($validated['caracteristicas'])) {
                foreach ($validated['caracteristicas'] as $caracteristica) {
                    if (isset($caracteristica['id'])) {
                        $caracteristicasIds[] = $caracteristica['id'];
                    } elseif (isset($caracteristica['nombre'])) {
                        $nueva = \App\Models\Caracteristica::create([
                            'nombre' => $caracteristica['nombre'],
                            'tipo_dato' => $caracteristica['tipo_dato'] ?? 'string',
                        ]);
                        $caracteristicasIds[] = $nueva->id;
                    }
                }

                $tipoEquipo->caracteristicas()->attach($caracteristicasIds);
            }

            DB::commit();

            return response()->json([
                'message' => 'Tipo de equipo creado exitosamente',
                'data' => $tipoEquipo->load('caracteristicas')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear el tipo de equipo',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tipoEquipo = TipoEquipo::with('caracteristicas') // <--- incluye las características
            ->where('id', $id)
            ->where('is_deleted', false)
            ->first();

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
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'categoria_id' => 'required|exists:categorias,id',
            'caracteristicas' => 'array',
            'caracteristicas.*.id' => 'sometimes|exists:caracteristicas,id',
            'caracteristicas.*.nombre' => 'sometimes|string',
            'caracteristicas.*.tipo_dato' => 'sometimes|in:string,integer,decimal,boolean',
        ]);

        $tipoEquipo = TipoEquipo::where('id', $id)->where('is_deleted', false)->first();

        if (!$tipoEquipo) {
            return response()->json(['error' => 'Tipo de equipo no encontrado'], 404);
        }

        // Validar nombre único ignorando el actual
        // Validar nombre único ignorando el actual
        $existe = TipoEquipo::whereRaw('LOWER(nombre) = ?', [strtolower($validated['nombre'])])
            ->where('id', '!=', $id)
            ->where('is_deleted', false)
            ->exists();

        if ($existe) {
            return response()->json(['message' => 'El nombre ya existe.'], 422);
        }


        DB::beginTransaction();

        try {
            // Actualizar nombre y categoría
            $tipoEquipo->nombre = $validated['nombre'];
            $tipoEquipo->categoria_id = $validated['categoria_id'];
            $tipoEquipo->save();

            // Manejo de características
            $nuevasCaracteristicasIds = [];

            if (isset($validated['caracteristicas'])) {
                foreach ($validated['caracteristicas'] as $caracteristica) {
                    if (isset($caracteristica['id']) && $caracteristica['id'] > 0) {
                        // Característica existente
                        $nuevasCaracteristicasIds[] = $caracteristica['id'];
                    } elseif (isset($caracteristica['nombre'])) {
                        // Característica nueva
                        $nueva = \App\Models\Caracteristica::create([
                            'nombre' => $caracteristica['nombre'],
                            'tipo_dato' => $caracteristica['tipo_dato'] ?? 'string',
                        ]);
                        $nuevasCaracteristicasIds[] = $nueva->id;
                    }
                }

                // Actualizar la relación (detach + attach)
                $tipoEquipo->caracteristicas()->sync($nuevasCaracteristicasIds);
            } else {
                // Si no se envía ninguna, quitamos todas las relaciones
                $tipoEquipo->caracteristicas()->detach();
            }

            DB::commit();

            return response()->json([
                'message' => 'Tipo de equipo actualizado exitosamente',
                'data' => $tipoEquipo->load('categoria', 'caracteristicas')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el tipo de equipo',
                'error' => $e->getMessage()
            ], 500);
        }
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

    /**
     * Obtener características asociadas a un tipo de equipo
     */
    public function getCaracteristicas($id)
    {
        $tipoEquipo = TipoEquipo::with(['caracteristicas' => function ($query) {
            $query->where('is_deleted', false);
        }])->findOrFail($id);

        return response()->json($tipoEquipo->caracteristicas);
    }

    public function checkEquipos($id)
    {
        $count = DB::table('equipos')
            ->where('tipo_equipo_id', $id)
            ->count();

        return response()->json([
            'tiene_equipos' => $count > 0,
            'count' => $count // ← Devuelve el número exacto (útil para mensajes)
        ]);
    }


    // En tu controlador (ej: TipoEquipoController.php)
    public function checkEquiposMasivo(Request $request)
    {
        $ids = $request->input('ids', []); // IDs de tipos a verificar

        $resultados = DB::table('equipos')
            ->select('tipo_equipo_id', DB::raw('COUNT(*) as count'))
            ->whereIn('tipo_equipo_id', $ids)
            ->groupBy('tipo_equipo_id')
            ->pluck('count', 'tipo_equipo_id')
            ->toArray();

        return response()->json($resultados);
    }
}
