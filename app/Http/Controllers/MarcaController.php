<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MarcaController extends Controller
{
    public function index()
    {
        $marcas = Marca::where('is_deleted', false)->get();
        return response()->json($marcas);
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
}
