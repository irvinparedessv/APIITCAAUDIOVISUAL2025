<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Modelo;
use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ModeloController extends Controller
{
    public function index()
    {
        $modelos = Modelo::where('is_deleted', false)->get();
        return response()->json($modelos);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'marca_id' => 'required|exists:marcas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validar si ya existe un modelo con ese nombre y marca
        $existente = Modelo::whereRaw('LOWER(nombre) = ?', [strtolower($request->nombre)])
            ->where('marca_id', $request->marca_id)
            ->where('is_deleted', false)
            ->first();

        if ($existente) {
            return response()->json([
                'message' => 'El modelo ya existe para esta marca',
                'id' => $existente->id
            ], 409);
        }

        $modelo = Modelo::create([
            'nombre' => $request->nombre,
            'marca_id' => $request->marca_id,
            'is_deleted' => false,
        ]);

        return response()->json($modelo, 201);
    }

    // En ModeloController.php
    public function modelosInsumos()
    {
        $insumos = Equipo::with(['modelo.marca'])
            ->where('es_componente', 1) // o tu lógica para detectar insumos
            ->where('is_deleted', 0)
            ->get();

        return response()->json($insumos);
    }


    public function porMarcaYTipo($marcaId)
    {
        $query = Modelo::where('marca_id', $marcaId)
            ->where('is_deleted', false);

        if (request('tipoEquipoId')) {
            $query->where(function ($q) {
                $q->whereDoesntHave('equipos') // Modelos sin equipos
                    ->orWhereHas('equipos', function ($q) {
                        // Modelos con equipos del tipo seleccionado
                        $q->where('tipo_equipo_id', request('tipoEquipoId'))
                            ->where('is_deleted', false);
                    });
            });
        }

        if (request('search')) {
            $query->where('nombre', 'LIKE', '%' . request('search') . '%');
        }

        if (request('limit')) {
            $query->limit(request('limit'));
        }

        return $query->get();
    }

    public function modelosEquiposDisponibles()
    {
        $modelos = DB::table('vista_equipos')
            ->select('modelo_id', 'nombre_modelo')
            ->groupBy('modelo_id', 'nombre_modelo')
            ->orderBy('nombre_modelo', 'asc')
            ->get();

        return response()->json($modelos);
    }





    public function mod_index(Request $request)
    {
        $search = $request->query('search');
        $perPage = $request->query('perPage', 10);

        $query = Modelo::with('marca')->where('is_deleted', false);

        if ($search) {
            $query->where('nombre', 'like', "%$search%");
        }

        return $query->paginate($perPage);
    }


    public function mod_marcas()
    {
        return Marca::all();
    }

    public function mod_store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'marca_id' => 'required|exists:marcas,id',
        ]);

        $modelo = Modelo::create([
            'nombre' => $request->nombre,
            'marca_id' => $request->marca_id,
        ]);

        return response()->json($modelo, 201);
    }

    public function mod_update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string',
            'marca_id' => 'required|exists:marcas,id',
        ]);

        $modelo = Modelo::findOrFail($id);
        $modelo->update([
            'nombre' => $request->nombre,
            'marca_id' => $request->marca_id,
        ]);

        return response()->json($modelo);
    }
}
