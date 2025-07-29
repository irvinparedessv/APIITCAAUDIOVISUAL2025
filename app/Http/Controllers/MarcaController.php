<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MarcaController extends Controller
{
    public function index(Request $request)
    {
        $query = Marca::where('is_deleted', false);

        // Búsqueda
        if ($request->has('search') && trim($request->search) !== '') {
            $query->where('nombre', 'LIKE', '%' . $request->search . '%');
        }

        // Paginación
        $perPage = intval($request->input('perPage', 5));
        $marcas = $query->withCount('modelos')->orderBy('nombre')->paginate($perPage);

        return response()->json($marcas);
    }

    public function show($id)
    {
        $marca = Marca::withCount('modelos')->find($id);
        if (!$marca || $marca->is_deleted) {
            return response()->json(['message' => 'Marca no encontrada'], 404);
        }
        return response()->json($marca);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:marcas,nombre',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $marca = Marca::create([
            'nombre' => $request->nombre,
            'is_deleted' => false,
        ]);

        return response()->json($marca, 201);
    }

    public function update(Request $request, $id)
    {
        $marca = Marca::find($id);
        if (!$marca || $marca->is_deleted) {
            return response()->json(['message' => 'Marca no encontrada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:marcas,nombre,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $marca->nombre = $request->nombre;
        $marca->save();

        return response()->json($marca);
    }

    public function destroy($id)
    {
        $marca = Marca::withCount('modelos')->find($id);

        if (!$marca || $marca->is_deleted) {
            return response()->json(['message' => 'Marca no encontrada'], 404);
        }

        if ($marca->modelos_count > 0) {
            return response()->json([
                'message' => 'No se puede eliminar la marca porque tiene modelos asociados.'
            ], 400);
        }

        $marca->is_deleted = true;
        $marca->save();

        return response()->json(['message' => 'Marca eliminada correctamente.']);
    }
    public function obtenerMarcas(Request $request)
    {
        $query = Marca::where('is_deleted', false);

        if ($request->filled('search')) {
            $query->where('nombre', 'LIKE', '%' . $request->search . '%');
        }

        $perPage = $request->input('per_page', 10);

        $marcas = $query->orderBy('nombre')->paginate($perPage);

        return response()->json($marcas);
    }

    public function searchForSelect(Request $request)
    {
        $query = Marca::where('is_deleted', false);

        if ($request->has('search')) {
            $query->where('nombre', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->has('limit')) {
            return response()->json($query->paginate($request->limit));
        }

        return response()->json($query->get());
    }
}
