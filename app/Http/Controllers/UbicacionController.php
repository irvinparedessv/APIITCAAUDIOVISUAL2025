<?php

namespace App\Http\Controllers;

use App\Models\Ubicacion;
use Illuminate\Http\Request;


class UbicacionController extends Controller
{
    // Listar solo no eliminadas
    public function index()
    {
        return Ubicacion::where('is_deleted', false)->get();
    }

    // Crear
    public function store(Request $request)
    {
        $ubicacion = Ubicacion::create($request->only(['nombre', 'descripcion']));
        return response()->json($ubicacion, 201);
    }

    // Editar
    public function update(Request $request, $id)
    {
        $ubicacion = Ubicacion::findOrFail($id);
        $ubicacion->update($request->only(['nombre', 'descripcion']));
        return response()->json($ubicacion, 200);
    }

    // Soft Delete
    public function destroy($id)
    {
        $ubicacion = Ubicacion::findOrFail($id);
        $ubicacion->update(['is_deleted' => true]);
        return response()->json(['message' => 'Ubicación eliminada'], 200);
    }

    public function paginate(Request $request)
    {
        $perPage = $request->query('per_page', 5);
        $search = $request->query('search');

        $query = Ubicacion::where('is_deleted', false);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        $ubicaciones = $query->paginate($perPage);

        return response()->json($ubicaciones);
    }

    public function show($id)
    {
        $ubicacion = Ubicacion::where('is_deleted', false)->findOrFail($id);
        return response()->json($ubicacion, 200);
    }
}
