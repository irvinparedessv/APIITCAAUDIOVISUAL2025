<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Insumo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EquipoController extends Controller
{
    // Método para obtener todos los items (equipos e insumos)
    public function index(Request $request)
{
    $tipo = $request->input('tipo', 'todos');
    $perPage = $request->input('perPage', 10);
    $page = $request->input('page', 1);

    // Selección explícita de columnas idénticas para ambas consultas
    $commonSelect = [
        'id',
        'tipo_equipo_id',
        'marca_id',
        'modelo_id',
        'estado_id',
        'tipo_reserva_id',
        'detalles',
        'fecha_adquisicion',
        'created_at',
        'updated_at',
        'is_deleted',
        DB::raw('null as numero_serie'), // Placeholder para insumos
        DB::raw('null as vida_util'),    // Placeholder para insumos
        DB::raw('null as cantidad'),     // Placeholder para equipos
    ];

    // Consulta base para equipos
    $equiposQuery = Equipo::where('equipos.is_deleted', false)
        ->with(['tipoEquipo', 'marca', 'modelo', 'estado', 'tipoReserva'])
        ->select(array_merge($commonSelect, [
            DB::raw('"equipo" as tipo'),
            DB::raw('numero_serie'), // Sobrescribe el placeholder null
            DB::raw('vida_util'),    // Sobrescribe el placeholder null
            DB::raw('numero_serie as identificador')
        ]));

    // Consulta base para insumos
    $insumosQuery = Insumo::where('insumos.is_deleted', false)
        ->with(['tipoEquipo', 'marca', 'modelo', 'estado', 'tipoReserva'])
        ->select(array_merge($commonSelect, [
            DB::raw('"insumo" as tipo'),
            DB::raw('cantidad'), // Sobrescribe el placeholder null
            DB::raw('CONCAT("Cantidad: ", cantidad) as identificador')
        ]));

    // Aplicar filtros comunes (igual que antes)
    if ($request->has('search')) {
        // ... (mantener misma lógica de filtrado)
    }

    // Aplicar otros filtros (tipo_equipo_id, marca_id, estado_id)
    // ... (mantener misma lógica de filtrado)

    if ($tipo === 'equipos') {
        $resultados = $equiposQuery->orderBy('created_at', 'desc')->paginate($perPage);
    } elseif ($tipo === 'insumos') {
        $resultados = $insumosQuery->orderBy('created_at', 'desc')->paginate($perPage);
    } else {
        // Primero obtener los conteos por separado para la paginación
        $totalEquipos = $equiposQuery->count();
        $totalInsumos = $insumosQuery->count();
        $total = $totalEquipos + $totalInsumos;

        // Calcular límites para cada consulta
        $limit = $perPage;
        $equiposLimit = min($totalEquipos, $limit);
        $insumosLimit = $limit - $equiposLimit;

        // Obtener equipos e insumos por separado
        $equipos = $equiposQuery->orderBy('created_at', 'desc')
            ->limit($equiposLimit)
            ->get();

        $insumos = $insumosQuery->orderBy('created_at', 'desc')
            ->limit($insumosLimit)
            ->get();

        // Combinar y ordenar
        $items = $equipos->concat($insumos)
            ->sortByDesc('created_at')
            ->values();

        // Crear paginador manual
        $resultados = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query()
            ]
        );
    }

    // Transformar los items para la respuesta
    $resultados->getCollection()->transform(function ($item) {
        $tipo = $item->tipo;
        $modelo = $item->modelo ?? null;
        
        return [
            'id' => $item->id,
            'tipo' => $tipo,
            'detalles' => $item->detalles,
            'estado_id' => $item->estado_id,
            'tipo_equipo_id' => $item->tipo_equipo_id,
            'marca_id' => $item->marca_id,
            'modelo_id' => $item->modelo_id,
            'tipo_reserva_id' => $item->tipo_reserva_id,
            'fecha_adquisicion' => $item->fecha_adquisicion,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
            'numero_serie' => $tipo === 'equipo' ? $item->numero_serie : null,
            'vida_util' => $tipo === 'equipo' ? $item->vida_util : null,
            'cantidad' => $tipo === 'insumo' ? $item->cantidad : null,
            'identificador' => $item->identificador,
            'tipo_equipo' => $item->tipoEquipo,
            'marca' => $item->marca,
            'modelo' => $modelo,
            'estado' => $item->estado,
            'tipo_reserva' => $item->tipoReserva,
            'imagen_url' => $modelo && $modelo->imagen_normal 
                ? asset('storage/modelos/' . $modelo->imagen_normal)
                : ($tipo === 'equipo' 
                    ? asset('storage/equipos/default.png') 
                    : asset('storage/insumos/default.png'))
        ];
    });

    return response()->json([
        'data' => $resultados->items(),
        'total' => $resultados->total(),
        'current_page' => $resultados->currentPage(),
        'per_page' => $resultados->perPage(),
        'last_page' => $resultados->lastPage(),
    ]);
}

    // Método para obtener un item específico
    public function show(string $id, Request $request)
    {
        $tipo = $request->input('tipo'); // 'equipo' o 'insumo'
        
        if ($tipo === 'insumo') {
            $item = Insumo::with(['tipoEquipo', 'marca', 'modelo', 'estado', 'tipoReserva'])->findOrFail($id);
            $item->tipo = 'insumo';
        } else {
            $item = Equipo::with(['tipoEquipo', 'marca', 'modelo', 'estado', 'tipoReserva'])->findOrFail($id);
            $item->tipo = 'equipo';
        }
        
        // Agregar URL de imagen
        if ($item->modelo && $item->modelo->imagen_normal) {
            $item->imagen_url = asset('storage/modelos/' . $item->modelo->imagen_normal);
        } else {
            $item->imagen_url = $item->tipo === 'equipo' 
                ? asset('storage/equipos/default.png')
                : asset('storage/insumos/default.png');
        }
        
        return response()->json($item);
    }

    // Método para crear un nuevo item (equipo o insumo)
    public function store(Request $request)
    {
        $tipo = $request->input('tipo'); // 'equipo' o 'insumo'
        
        if ($tipo === 'insumo') {
            return $this->storeInsumo($request);
        } else {
            return $this->storeEquipo($request);
        }
    }
    
    protected function storeEquipo(Request $request)
    {
        $request->validate([
            'tipo_equipo_id' => 'required|exists:tipo_equipos,id',
            'marca_id' => 'required|exists:marcas,id',
            'modelo_id' => 'required|exists:modelos,id',
            'estado_id' => 'required|exists:estados,id',
            'tipo_reserva_id' => 'nullable|exists:tipo_reservas,id',
            'numero_serie' => 'required|string|unique:equipos,numero_serie',
            'vida_util' => 'nullable|integer',
            'detalles' => 'nullable|string',
            'fecha_adquisicion' => 'nullable|date',
        ]);
        
        // Verificar si el número de serie ya existe
        if (Equipo::where('numero_serie', $request->numero_serie)->exists()) {
            return response()->json(['message' => 'El número de serie ya está registrado'], 422);
        }
        
        $equipo = Equipo::create([
            'tipo_equipo_id' => $request->tipo_equipo_id,
            'marca_id' => $request->marca_id,
            'modelo_id' => $request->modelo_id,
            'estado_id' => $request->estado_id,
            'tipo_reserva_id' => $request->tipo_reserva_id,
            'numero_serie' => $request->numero_serie,
            'vida_util' => $request->vida_util,
            'detalles' => $request->detalles,
            'fecha_adquisicion' => $request->fecha_adquisicion,
            'is_deleted' => false,
        ]);
        
        return response()->json([
            'message' => 'Equipo creado exitosamente',
            'data' => $equipo,
            'tipo' => 'equipo'
        ], 201);
    }
    
    protected function storeInsumo(Request $request)
    {
        $request->validate([
            'tipo_equipo_id' => 'required|exists:tipo_equipos,id',
            'marca_id' => 'required|exists:marcas,id',
            'modelo_id' => 'required|exists:modelos,id',
            'estado_id' => 'required|exists:estados,id',
            'tipo_reserva_id' => 'nullable|exists:tipo_reservas,id',
            'cantidad' => 'required|integer|min:1',
            'detalles' => 'nullable|string',
            'fecha_adquisicion' => 'nullable|date',
        ]);
        
        $insumo = Insumo::create([
            'tipo_equipo_id' => $request->tipo_equipo_id,
            'marca_id' => $request->marca_id,
            'modelo_id' => $request->modelo_id,
            'estado_id' => $request->estado_id,
            'tipo_reserva_id' => $request->tipo_reserva_id,
            'cantidad' => $request->cantidad,
            'detalles' => $request->detalles,
            'fecha_adquisicion' => $request->fecha_adquisicion,
            'is_deleted' => false,
        ]);
        
        return response()->json([
            'message' => 'Insumo creado exitosamente',
            'data' => $insumo,
            'tipo' => 'insumo'
        ], 201);
    }

    // Método para actualizar un item
    public function update(Request $request, string $id)
    {
        $tipo = $request->input('tipo'); // 'equipo' o 'insumo'
        
        if ($tipo === 'insumo') {
            return $this->updateInsumo($request, $id);
        } else {
            return $this->updateEquipo($request, $id);
        }
    }
    
    protected function updateEquipo(Request $request, $id)
    {
        $equipo = Equipo::findOrFail($id);
        
        $request->validate([
            'tipo_equipo_id' => 'sometimes|required|exists:tipo_equipos,id',
            'marca_id' => 'sometimes|required|exists:marcas,id',
            'modelo_id' => 'sometimes|required|exists:modelos,id',
            'estado_id' => 'sometimes|required|exists:estados,id',
            'tipo_reserva_id' => 'nullable|exists:tipo_reservas,id',
            'numero_serie' => 'sometimes|required|string|unique:equipos,numero_serie,'.$id,
            'vida_util' => 'nullable|integer',
            'detalles' => 'nullable|string',
            'fecha_adquisicion' => 'nullable|date',
        ]);
        
        $equipo->update($request->all());
        
        return response()->json([
            'message' => 'Equipo actualizado exitosamente',
            'data' => $equipo,
            'tipo' => 'equipo'
        ]);
    }
    
    protected function updateInsumo(Request $request, $id)
    {
        $insumo = Insumo::findOrFail($id);
        
        $request->validate([
            'tipo_equipo_id' => 'sometimes|required|exists:tipo_equipos,id',
            'marca_id' => 'sometimes|required|exists:marcas,id',
            'modelo_id' => 'sometimes|required|exists:modelos,id',
            'estado_id' => 'sometimes|required|exists:estados,id',
            'tipo_reserva_id' => 'nullable|exists:tipo_reservas,id',
            'cantidad' => 'sometimes|required|integer|min:1',
            'detalles' => 'nullable|string',
            'fecha_adquisicion' => 'nullable|date',
        ]);
        
        $insumo->update($request->all());
        
        return response()->json([
            'message' => 'Insumo actualizado exitosamente',
            'data' => $insumo,
            'tipo' => 'insumo'
        ]);
    }

    // Método para eliminar (lógicamente) un item
    public function destroy(Request $request, string $id)
    {
        $tipo = $request->input('tipo'); // 'equipo' o 'insumo'
        
        if ($tipo === 'insumo') {
            $item = Insumo::findOrFail($id);
        } else {
            $item = Equipo::findOrFail($id);
        }
        
        $item->is_deleted = true;
        $item->save();
        
        return response()->json([
            'message' => ucfirst($tipo) . ' eliminado lógicamente',
            'tipo' => $tipo
        ]);
    }

    // Método para obtener equipos por tipo de reserva
    public function getEquiposPorTipoReserva($tipoReservaId)
    {
        $equipos = DB::table('equipos')
            ->join('tipo_equipos', 'equipos.tipo_equipo_id', '=', 'tipo_equipos.id')
            ->where('equipos.tipo_reserva_id', $tipoReservaId)
            ->where('equipos.estado_id', 1) // Asumiendo que estado_id 1 es "Disponible"
            ->where('equipos.is_deleted', false)
            ->where('tipo_equipos.is_deleted', false)
            ->select('equipos.id', 'equipos.numero_serie as nombre', 'equipos.tipo_equipo_id')
            ->get();

        return response()->json($equipos);
    }
}