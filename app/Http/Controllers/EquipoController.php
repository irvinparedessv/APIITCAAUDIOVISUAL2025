<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EquipoController extends Controller
{
    public function index(Request $request)
    {
        $query = Equipo::activos()->with('tipoEquipo'); // Cargar relación tipoEquipo

        $query->orderBy('created_at', 'desc');

        // Filtro de búsqueda
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%$search%")
                    ->orWhere('descripcion', 'like', "%$search%")
                    // Buscar en nombre del tipoEquipo (relación)
                    ->orWhereHas('tipoEquipo', function ($q2) use ($search) {
                        $q2->where('nombre', 'like', "%$search%");
                    })
                    // Buscar en estado convertido a texto
                    ->orWhereRaw("CASE WHEN estado = 1 THEN 'Disponible' ELSE 'No Disponible' END LIKE ?", ["%$search%"]);
            });
        }

        // Filtro por tipo de equipo (id)
        if ($request->has('tipo_equipo_id')) {
            $query->where('tipo_equipo_id', $request->input('tipo_equipo_id'));
        }

        // Filtro por estado booleano exacto
        if ($request->has('estado')) {
            $estado = filter_var($request->input('estado'), FILTER_VALIDATE_BOOLEAN);
            $query->where('estado', $estado);
        }

        // Paginación
        $perPage = $request->input('perPage', 10);
        $page = $request->input('page', 1);

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        // Agregar URL de imagen si es necesario
        $paginated->getCollection()->transform(function ($equipo) {
            $equipo->imagen_url = $equipo->imagen_url;
            return $equipo;
        });

        return response()->json([
            'data' => $paginated->items(),
            'total' => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'per_page' => $paginated->perPage(),
            'last_page' => $paginated->lastPage(),
        ]);
    }



    public function obtenerEquipos(Request $request)
    {
        $query = Equipo::where('is_deleted', false)->where('estado', true);

        // Filtro por tipo de equipo
        if ($request->filled('tipo_equipo_id')) {
            $query->where('tipo_equipo_id', $request->tipo_equipo_id);
        }

        // Búsqueda por nombre
        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', '%' . $request->buscar . '%');
        }

        // Selección de campos y paginación
        $equipos = $query->select('id', 'nombre', 'descripcion', 'cantidad', 'tipo_equipo_id', 'imagen')
            ->orderBy('nombre')
            ->paginate(10); // Cambia 10 por la cantidad que desees por página

        // Transformar la colección para incluir la URL de la imagen
        $equipos->getCollection()->transform(function ($equipo) {
            // Agregar la URL completa de la imagen
            $equipo->imagen_url = $equipo->imagen
                ? asset('storage/equipos/' . $equipo->imagen)
                : asset('storage/equipos/default.png');

            return $equipo;
        });

        return response()->json($equipos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'required|boolean',
            'cantidad' => 'required|integer',
            'is_deleted' => 'required|boolean',
            'tipo_equipo_id' => 'required|exists:tipo_equipos,id',
            'tipo_reserva_id' => 'required|exists:tipo_reservas,id',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only([
            'nombre',
            'descripcion',
            'estado',
            'cantidad',
            'tipo_equipo_id',
            'tipo_reserva_id',
        ]);

        $data['is_deleted'] = false;

        if ($request->hasFile('imagen')) {
            $image = $request->file('imagen');
            Log::info("Nombre del archivo: " . $image->getClientOriginalName());
            Log::info("Extensión del archivo: " . $image->getClientOriginalExtension());

            $imageName = Str::random(20) . '.' . $image->getClientOriginalExtension();
            $image->storeAs('equipos', $imageName, 'public');
            $data['imagen'] = $imageName;
        } else {
            Log::info("No se ha subido una imagen.");
            $data['imagen'] = 'default.png';
        }

        $equipo = Equipo::create($data);

        return response()->json($equipo, 201);
    }

    public function show(string $id)
    {
        $equipo = Equipo::findOrFail($id);
        $equipo->imagen_url = $equipo->imagen_url;
        return response()->json($equipo);
    }

    public function update(Request $request, string $id)
    {
        $equipo = Equipo::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'sometimes|required|boolean',
            'cantidad' => 'sometimes|required|integer',
            'is_deleted' => 'sometimes|required|boolean',
            'tipo_equipo_id' => 'sometimes|required|exists:tipo_equipos,id',
            'tipo_reserva_id' => 'sometimes|required|exists:tipo_reservas,id',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only([
            'nombre',
            'descripcion',
            'estado',
            'cantidad',
            'tipo_equipo_id',
            'tipo_reserva_id',
        ]);

        if ($request->has('is_deleted')) {
            $data['is_deleted'] = $request->input('is_deleted');
        }

        if ($request->hasFile('imagen')) {
            if ($equipo->imagen && $equipo->imagen !== 'default.png') {
                Storage::disk('public')->delete('equipos/' . $equipo->imagen);
            }

            $image = $request->file('imagen');
            Log::info("Nombre del archivo: " . $image->getClientOriginalName());
            Log::info("Extensión del archivo: " . $image->getClientOriginalExtension());

            $imageName = Str::random(20) . '.' . $image->getClientOriginalExtension();
            $image->storeAs('equipos', $imageName, 'public');
            $data['imagen'] = $imageName;
        }

        $equipo->update($data);

        return response()->json($equipo);
    }

    public function destroy(string $id)
    {
        $equipo = Equipo::findOrFail($id);
        $equipo->is_deleted = true;
        $equipo->save();

        return response()->json(['message' => 'Equipo eliminado lógicamente.']);
    }

    public function getEquiposPorTipoReserva($tipoReservaId)
    {
        $equipos = Equipo::where('tipo_reserva_id', $tipoReservaId)
            ->where('estado', true)
            ->where('is_deleted', false)
            ->get(['id', 'nombre', 'tipo_equipo_id']);

        return response()->json($equipos);
    }
}
