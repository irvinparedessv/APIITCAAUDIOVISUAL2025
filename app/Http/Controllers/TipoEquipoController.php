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
        $tiposEquipos = TipoEquipo::where('is_deleted', false)->get();
        return response()->json($tiposEquipos);
    }

    public function obtenerTipo(Request $request)
    {
        $tiposEquipos = TipoEquipo::where('is_deleted', false)->paginate(10);
        return response()->json($tiposEquipos);
    }



    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|unique:tipo_equipos,nombre',
            'categoria_id' => 'required|exists:categorias,id',
            'caracteristicas' => 'array',
            'caracteristicas.*.id' => 'sometimes|exists:caracteristicas,id',
            'caracteristicas.*.nombre' => 'sometimes|string',
            'caracteristicas.*.tipo_dato' => 'sometimes|in:string,integer,decimal,boolean',
        ]);

        // Iniciar transacción para asegurar integridad de datos
        DB::beginTransaction();

        try {
            // 1. Crear el tipo de equipo
            $tipoEquipo = TipoEquipo::create([
                'nombre' => $validated['nombre'],
                'categoria_id' => $validated['categoria_id'],
            ]);

            // 2. Procesar características
            if (isset($validated['caracteristicas'])) {
                $caracteristicasIds = [];

                foreach ($validated['caracteristicas'] as $caracteristica) {
                    // Si tiene ID, es una característica existente
                    if (isset($caracteristica['id']) && $caracteristica['id'] > 0) {
                        $caracteristicasIds[] = $caracteristica['id'];
                    }
                    // Si no tiene ID pero tiene nombre, es nueva
                    elseif (isset($caracteristica['nombre'])) {
                        $nuevaCaracteristica = \App\Models\Caracteristica::create([
                            'nombre' => $caracteristica['nombre'],
                            'tipo_dato' => $caracteristica['tipo_dato'] ?? 'string',
                        ]);
                        $caracteristicasIds[] = $nuevaCaracteristica->id;
                    }
                }

                // 3. Relacionar características con el tipo de equipo
                if (!empty($caracteristicasIds)) {
                    $tipoEquipo->caracteristicas()->attach($caracteristicasIds);
                }
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
}
