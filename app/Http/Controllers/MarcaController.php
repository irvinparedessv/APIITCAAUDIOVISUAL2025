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

        if ($request->has('search')) {
            $query->where('nombre', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->has('limit')) {
            return response()->json($query->paginate($request->limit));
        }

        return response()->json($query->get());
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
}
