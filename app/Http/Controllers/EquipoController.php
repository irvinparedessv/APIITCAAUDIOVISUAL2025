<?php

// app/Http/Controllers/EquipoController.php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\ReservaEquipo;
use App\Models\VistaResumenEquipo;
use Illuminate\Http\Request;

class EquipoController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);

        $query = Equipo::with([
            'tipoEquipo',
            'modelo.marca',
            'modelo',
            'estado',
            'tipoReserva',
            'valoresCaracteristicas.caracteristica',  // <-- carga características relacionadas
        ])->where('is_deleted', false);

        if ($request->filled('tipo')) {
            if ($request->input('tipo') === 'equipo') {
                $query->whereNotNull('numero_serie');
            } elseif ($request->input('tipo') === 'insumo') {
                $query->whereNotNull('cantidad');
            }
        }

        $equipos = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $equipos->getCollection()->transform(function ($item) {
            $tipo = $item->numero_serie ? 'equipo' : 'insumo';

            return [
                'id' => $item->id,
                'tipo' => $tipo,
                'numero_serie' => $item->numero_serie,
                'vida_util' => $item->vida_util,
                'cantidad' => 1,
                'detalles' => $item->detalles,
                'tipo_equipo_id' => $item->tipo_equipo_id,
                'modelo_id' => $item->modelo_id,
                'estado_id' => $item->estado_id,
                'tipo_reserva_id' => $item->tipo_reserva_id,
                'fecha_adquisicion' => $item->fecha_adquisicion,
                'imagen_url' => $item->imagen_normal
                    ? asset('storage/equipos/' . $item->imagen_normal)
                    : asset('storage/equipos/default.png'),
                'marca' => $item->modelo->marca->nombre ?? null,
                'tipoEquipo' => $item->tipoEquipo,
                'modelo' => $item->modelo,
                'estado' => $item->estado,
                'tipoReserva' => $item->tipoReserva,
                'caracteristicas' => $item->valoresCaracteristicas->map(function ($vc) {
                    return [
                        'id' => $vc->id,
                        'caracteristica_id' => $vc->caracteristica_id,
                        'nombre' => $vc->caracteristica->nombre ?? null,
                        'valor' => $vc->valor,
                    ];
                }),
            ];
        });


        return response()->json($equipos);
    }


    public function show($id)
    {
        $equipo = Equipo::with(['tipoEquipo', 'modelo', 'estado', 'tipoReserva'])->findOrFail($id);

        $equipo->tipo = $equipo->numero_serie ? 'equipo' : 'insumo';
        $equipo->imagen_url = $equipo->imagen_normal
            ? asset('storage/equipos/' . $equipo->imagen_normal)
            : asset('storage/equipos/default.png');

        return response()->json($equipo);
    }

    public function store(Request $request)
    {
        $tipo = $request->input('tipo'); // equipo o insumo

        $rules = [
            'tipo_equipo_id' => 'required|exists:tipo_equipos,id',
            'modelo_id' => 'required|exists:modelos,id',
            'estado_id' => 'required|exists:estados,id',
            'tipo_reserva_id' => 'nullable|exists:tipo_reservas,id',
            'detalles' => 'nullable|string',
            'fecha_adquisicion' => 'nullable|date',
            'caracteristicas' => 'nullable|array',
        ];

        if ($tipo === 'equipo') {
            $rules['numero_serie'] = 'required|string|unique:equipos,numero_serie';
            $rules['vida_util'] = 'nullable|integer';
        } else {
            $rules['cantidad'] = 'required|integer|min:1';
        }

        $request->validate($rules);

        if ($tipo === 'equipo') {
            // Equipo único
            $equipo = Equipo::create(array_merge(
                $request->except('caracteristicas'),
                ['es_componente' => false]
            ));

            // Guardar características
            if ($request->has('caracteristicas')) {
                foreach ($request->input('caracteristicas') as $caracteristica) {
                    $equipo->valoresCaracteristicas()->create([
                        'caracteristica_id' => $caracteristica['id'],
                        'valor' => $caracteristica['valor']
                    ]);
                }
            }

            return response()->json([
                'message' => 'Equipo registrado correctamente',
                'data' => $equipo->load('valoresCaracteristicas.caracteristica'),
            ], 201);
        } else {
            // Insumos: crear múltiples registros
            $cantidad = (int) $request->input('cantidad');
            $insumos = [];

            for ($i = 0; $i < $cantidad; $i++) {
                $nuevoInsumo = Equipo::create(array_merge(
                    $request->except('caracteristicas', 'cantidad', 'numero_serie', 'vida_util'),
                    ['es_componente' => true]
                ));

                if ($request->has('caracteristicas')) {
                    foreach ($request->input('caracteristicas') as $caracteristica) {
                        $nuevoInsumo->valoresCaracteristicas()->create([
                            'caracteristica_id' => $caracteristica['id'],
                            'valor' => $caracteristica['valor']
                        ]);
                    }
                }

                $insumos[] = $nuevoInsumo->load('valoresCaracteristicas.caracteristica');
            }

            return response()->json([
                'message' => 'Insumos registrados correctamente',
                'data' => $insumos,
            ], 201);
        }
    }


    public function update(Request $request, $id)
    {
        $equipo = Equipo::findOrFail($id);
        $tipo = $equipo->numero_serie ? 'equipo' : 'insumo';

        $rules = [
            'tipo_equipo_id' => 'sometimes|required|exists:tipo_equipos,id',
            'modelo_id' => 'sometimes|required|exists:modelos,id',
            'estado_id' => 'sometimes|required|exists:estados,id',
            'tipo_reserva_id' => 'nullable|exists:tipo_reservas,id',
            'detalles' => 'nullable|string',
            'fecha_adquisicion' => 'nullable|date',
        ];

        if ($tipo === 'equipo') {
            $rules['numero_serie'] = 'sometimes|required|string|unique:equipos,numero_serie,' . $id;
            $rules['vida_util'] = 'nullable|integer';
        } else {
            $rules['cantidad'] = 'sometimes|required|integer|min:1';
        }

        $request->validate($rules);
        $equipo->update($request->all());

        return response()->json([
            'message' => 'Actualizado correctamente',
            'data' => $equipo,
        ]);
    }

    public function destroy($id)
    {
        $equipo = Equipo::findOrFail($id);
        $equipo->is_deleted = true;
        $equipo->save();

        return response()->json(['message' => 'Eliminado lógicamente']);
    }

    public function getEquiposPorTipoReserva($tipoReservaId)
    {
        $equipos = Equipo::where('tipo_reserva_id', $tipoReservaId)
            ->where('estado_id', 1)
            ->where('is_deleted', false)
            ->whereNotNull('numero_serie') // solo equipos
            ->get(['id', 'numero_serie as nombre', 'tipo_equipo_id']);

        return response()->json($equipos);
    }

    public function equiposDisponiblesPorTipoYFecha(Request $request)
    {
        $request->validate([
            'tipo_reserva_id' => 'required|integer',
            'fecha' => 'required|date',
            'startTime' => 'required',
            'endTime' => 'required',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string'
        ]);

        $page = $request->input('page', 1);
        $limit = $request->input('limit', 5);
        $search = $request->input('search', '');

        $fechaInicio = $request->fecha . ' ' . $request->startTime;
        $fechaFin = $request->fecha . ' ' . $request->endTime;

        $reservados = ReservaEquipo::whereDate('fecha_reserva', $request->fecha)
            ->where('tipo_reserva_id', $request->tipo_reserva_id)
            ->whereIn('estado', ['Aprobada', 'Pendiente'])
            ->where(function ($query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween('fecha_reserva', [$fechaInicio, $fechaFin])
                    ->orWhereBetween('fecha_entrega', [$fechaInicio, $fechaFin])
                    ->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                        $q->where('fecha_reserva', '<=', $fechaInicio)
                            ->where('fecha_entrega', '>=', $fechaFin);
                    });
            })
            ->with('equipos.modelo')
            ->get();

        $reservasPorModelo = collect();

        foreach ($reservados as $reserva) {
            foreach ($reserva->equipos as $equipo) {
                $modeloId = $equipo->modelo_id;
                $actual = $reservasPorModelo->get($modeloId, 0);
                $reservasPorModelo->put($modeloId, $actual + 1);
            }
        }

        $query = VistaResumenEquipo::query();

        if (!empty($search)) {
            $query->where('nombre_modelo', 'like', "%$search%");
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        $filtered = $paginator->getCollection()->transform(function ($item) use ($reservasPorModelo) {
            $item->cantidad_enreserva = $reservasPorModelo->get($item->modelo_id, 0);
            $item->disponibles_finales = $item->cantidad_disponible - $item->cantidad_enreserva;
            return $item;
        })->filter(function ($item) {
            return $item->disponibles_finales > 0;
        })->values(); // Reindexar la colección

        // Crear nueva instancia de LengthAwarePaginator con datos filtrados
        $result = new \Illuminate\Pagination\LengthAwarePaginator(
            $filtered,
            $filtered->count(), // total real de elementos
            $limit,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return response()->json($result);
    }
}
