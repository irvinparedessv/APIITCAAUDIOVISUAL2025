<?php

namespace App\Http\Controllers;

use App\Models\ReservaAula;
use Illuminate\Http\Request;
use App\Models\ReservaEquipo;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    public function reporteReservasPorRango(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'estado' => 'nullable|string|in:Pendiente,Aprobado,Rechazado,Cancelado,Devuelto,Todos',
            'tipo_reserva' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = ReservaEquipo::with([
            'user',
            'equipos' => function ($query) {
                $query->with(['modelo']);
            },
            'tipoReserva'
        ])
            ->whereDate('fecha_reserva', '>=', $request->fecha_inicio)
            ->whereDate('fecha_reserva', '<=', $request->fecha_fin);

        // Filtrar por estado
        if ($request->filled('estado') && $request->estado !== 'Todos') {
            $query->where('estado', $request->estado);
        }

        // Filtrar por tipo de reserva
        if ($request->filled('tipo_reserva')) {
            $tipoReserva = $request->tipo_reserva;
            $query->whereHas('tipoReserva', function ($q) use ($tipoReserva) {
                $q->where('nombre', $tipoReserva);
            });
        }

        $perPage = $request->input('per_page', 15);

        $reservas = $query->orderBy('fecha_reserva', 'desc')->paginate($perPage);

        // Formatear respuesta
        $reservas->getCollection()->transform(function ($reserva) {
            $equiposInfo = $reserva->equipos->map(function ($equipo) {
                $info = $equipo->modelo->nombre ?? 'Sin modelo';

                if ($equipo->es_componente && $equipo->numero_serie) {
                    $info .= ' (S/N: ' . $equipo->numero_serie . ')';
                }

                return $info;
            })->implode(', ');

            return [
                'id' => $reserva->id,
                'usuario' => $reserva->user ? $reserva->user->first_name . ' ' . $reserva->user->last_name : 'N/A',
                'tipo' => $reserva->tipoReserva->nombre ?? 'Sin tipo',
                'nombre_recurso' => $equiposInfo, // Aquí está el cambio principal
                'fecha' => \Carbon\Carbon::parse($reserva->fecha_reserva)->format('Y-m-d'),
                'horario' => \Carbon\Carbon::parse($reserva->fecha_reserva)->format('H:i') . ' - ' .
                    \Carbon\Carbon::parse($reserva->fecha_entrega)->format('H:i'),
                'estado' => $reserva->estado,
                'documento_url' => $reserva->documento_evento_url,
            ];
        });

        return response()->json($reservas);
    }

    public function reporteReservasPorUsuario(Request $request)
    {
        $request->validate([
            'usuario_id' => 'required|exists:users,id',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'estado' => 'nullable|string|in:Pendiente,Aprobado,Rechazado,Cancelado,Devuelto,Todos',
            'tipo_reserva' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = ReservaEquipo::with([
            'user',
            'equipos' => function ($query) {
                $query->with(['modelo']);
            },
            'tipoReserva'
        ])
            ->where('user_id', $request->usuario_id);

        // Filtros opcionales
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_reserva', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_reserva', '<=', $request->fecha_fin);
        }

        if ($request->filled('estado') && $request->estado !== 'Todos') {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_reserva')) {
            $query->whereHas('tipoReserva', function ($q) use ($request) {
                $q->where('nombre', $request->tipo_reserva);
            });
        }

        $perPage = $request->input('per_page', 15);
        $reservas = $query->orderBy('fecha_reserva', 'desc')->paginate($perPage);

        // Formatear respuesta
        $reservas->getCollection()->transform(function ($reserva) {
            $equiposInfo = $reserva->equipos->map(function ($equipo) {
                $info = $equipo->modelo->nombre ?? 'Sin modelo';

                if ($equipo->es_componente && $equipo->numero_serie) {
                    $info .= ' (S/N: ' . $equipo->numero_serie . ')';
                }

                return $info;
            })->implode(', ');

            return [
                'id' => $reserva->id,
                'usuario' => $reserva->user ? $reserva->user->first_name . ' ' . $reserva->user->last_name : 'N/A',
                'tipo' => $reserva->tipoReserva->nombre ?? 'Sin tipo',
                'nombre_recurso' => $equiposInfo, // Modelo + número de serie si es componente
                'fecha' => \Carbon\Carbon::parse($reserva->fecha_reserva)->format('Y-m-d'),
                'horario' => \Carbon\Carbon::parse($reserva->fecha_reserva)->format('H:i') . ' - ' .
                    \Carbon\Carbon::parse($reserva->fecha_entrega)->format('H:i'),
                'estado' => $reserva->estado,
                'documento_url' => $reserva->documento_evento_url ?? null, // Agregado por si acaso
            ];
        });

        return response()->json($reservas);
    }

    public function reporteUsoAulas(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'estado' => 'nullable|string',
            'search' => 'nullable|string',
            'usuario_id' => 'nullable|integer|exists:users,id', // <-- ya validado
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
        $estado = $request->input('estado');
        $search = $request->input('search');
        $usuarioId = $request->input('usuario_id'); // <-- nuevo
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $query = ReservaAula::with(['aula', 'user'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if ($estado && strtolower($estado) !== 'todos') {
            $query->where('estado', $estado);
        }

        // ✅ NUEVO: filtro por usuario_id si viene del request
        if ($usuarioId) {
            $query->where('user_id', $usuarioId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('aula', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%$search%");
                })->orWhereHas('user', function ($q3) use ($search) {
                    $q3->where('first_name', 'like', "%$search%")
                        ->orWhere('last_name', 'like', "%$search%");
                });
            });
        }

        $reservasPaginadas = $query->orderBy('fecha', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $reservas = $reservasPaginadas->getCollection()->map(function ($reserva) {
            return [
                'id' => $reserva->id,
                'aula' => $reserva->aula->name ?? '',
                'usuario' => $reserva->user ? ($reserva->user->first_name . ' ' . $reserva->user->last_name) : '',
                'fecha' => $reserva->fecha->format('Y-m-d'),
                'horario' => $reserva->horario,
                'estado' => $reserva->estado,
            ];
        });

        return response()->json([
            'data' => $reservas,
            'current_page' => $reservasPaginadas->currentPage(),
            'last_page' => $reservasPaginadas->lastPage(),
            'per_page' => $reservasPaginadas->perPage(),
            'total' => $reservasPaginadas->total(),
        ]);
    }

    // En tu controlador ReporteController o similar

    public function reporteUsoPorAula(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'aula_id' => 'required|integer|exists:aulas,id',
            'estado' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
        $estado = $request->input('estado');
        $aulaId = $request->input('aula_id');
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $query = ReservaAula::with(['aula', 'user'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('aula_id', $aulaId);

        if ($estado && strtolower($estado) !== 'todos') {
            $query->where('estado', $estado);
        }

        $reservasPaginadas = $query->orderBy('fecha', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $reservas = $reservasPaginadas->getCollection()->map(function ($reserva) {
            return [
                'id' => $reserva->id,
                'aula' => $reserva->aula->name ?? '',
                'usuario' => $reserva->user ? ($reserva->user->first_name . ' ' . $reserva->user->last_name) : '',
                'fecha' => $reserva->fecha->format('Y-m-d'),
                'horario' => $reserva->horario,
                'estado' => $reserva->estado,
            ];
        });

        return response()->json([
            'data' => $reservas,
            'current_page' => $reservasPaginadas->currentPage(),
            'last_page' => $reservasPaginadas->lastPage(),
            'per_page' => $reservasPaginadas->perPage(),
            'total' => $reservasPaginadas->total(),
        ]);
    }



    public function reporteUsoEquipos(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'tipo_equipo' => 'nullable|string',
        ]);

        $query = DB::table('equipo_reserva')
            ->join('reserva_equipos', 'equipo_reserva.reserva_equipo_id', '=', 'reserva_equipos.id')
            ->join('equipos', 'equipo_reserva.equipo_id', '=', 'equipos.id')
            ->join('tipo_equipos', 'equipos.tipo_equipo_id', '=', 'tipo_equipos.id')
            ->leftJoin('modelos', 'equipos.modelo_id', '=', 'modelos.id') // Join con modelos
            ->select(
                'equipos.id',
                'modelos.nombre as modelo', // Nombre del modelo
                'equipos.numero_serie',
                'equipos.es_componente',
                'tipo_equipos.nombre as tipo_equipo',
                DB::raw('SUM(equipo_reserva.cantidad) as total_cantidad')
            )
            ->where('equipos.estado_id', 1) // Equipos activos
            ->groupBy('equipos.id', 'modelos.nombre', 'equipos.numero_serie', 'equipos.es_componente', 'tipo_equipos.nombre')
            ->orderByDesc('total_cantidad');

        // Filtros
        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('reserva_equipos.fecha_reserva', [$request->from, $request->to]);
        }

        if ($request->filled('tipo_equipo')) {
            $query->where('tipo_equipos.nombre', $request->tipo_equipo);
        }

        $resultados = $query->get();

        // Transformar los resultados para mostrar la información combinada
        $resultadosTransformados = $resultados->map(function ($item) {
            $nombreEquipo = $item->modelo ?? 'Sin modelo';

            if ($item->es_componente && $item->numero_serie) {
                $nombreEquipo .= ' (S/N: ' . $item->numero_serie . ')';
            }

            return [
                'equipo_id' => $item->id,
                'equipo' => $nombreEquipo,
                'tipo_equipo' => $item->tipo_equipo,
                'total_cantidad' => $item->total_cantidad,
                'es_componente' => $item->es_componente,
                'numero_serie' => $item->numero_serie
            ];
        });

        return response()->json($resultadosTransformados);
    }

    public function reporteHorariosSolicitados(Request $request)
{
    $request->validate([
        'from' => 'nullable|date',
        'to' => 'nullable|date|after_or_equal:from',
        'tipo' => 'nullable|string|in:aula,equipo',
        'aula_id' => 'nullable|integer|exists:aulas,id',
        'equipo_id' => 'nullable|integer|exists:equipos,id',
    ]);

    $from = $request->input('from');
    $to = $request->input('to');
    $tipo = $request->input('tipo');

    // Consulta para Aulas (se mantiene igual)
    $aulasQuery = DB::table('reserva_aulas as ra')
        ->select(
            'ra.horario',
            DB::raw("'aula' as tipo"),
            DB::raw('NULL as recurso_nombre'),
            DB::raw('COUNT(*) as total')
        )
        ->when($from && $to, fn($q) => $q->whereBetween('ra.fecha', [$from, $to]))
        ->when($tipo === 'aula' && $request->aula_id, fn($q) => $q->where('ra.aula_id', $request->aula_id))
        ->groupBy('ra.horario');

    // Consulta para Equipos (modificada)
    $equiposQuery = DB::table('reserva_equipos as re')
        ->join('equipo_reserva as er', 'er.reserva_equipo_id', '=', 're.id')
        ->join('equipos as e', 'e.id', '=', 'er.equipo_id')
        ->leftJoin('modelos as m', 'e.modelo_id', '=', 'm.id') // Join con modelos
        ->select(
            DB::raw("DATE_FORMAT(re.fecha_reserva, '%H:%i') as horario"),
            DB::raw("'equipo' as tipo"),
            DB::raw("CASE 
                WHEN e.es_componente = 1 AND e.numero_serie IS NOT NULL 
                THEN CONCAT(m.nombre, ' (S/N: ', e.numero_serie, ')')
                ELSE m.nombre
                END as recurso_nombre"),
            DB::raw('COUNT(*) as total')
        )
        ->when($from && $to, fn($q) => $q->whereBetween(DB::raw('DATE(re.fecha_reserva)'), [$from, $to]))
        ->when($tipo === 'equipo' && $request->equipo_id, fn($q) => $q->where('e.id', $request->equipo_id))
        ->groupBy('horario', 'recurso_nombre');

    // Lógica para combinar resultados
    if ($tipo === 'aula') {
        $result = $aulasQuery->orderBy('horario')->get();
    } elseif ($tipo === 'equipo') {
        $result = $equiposQuery->orderBy('horario')->get();
    } else {
        $unionSql = $aulasQuery->unionAll($equiposQuery)->toSql();
        $bindings = array_merge($aulasQuery->getBindings(), $equiposQuery->getBindings());

        $result = DB::table(DB::raw("($unionSql) as sub"))
            ->setBindings($bindings)
            ->orderBy('horario')
            ->orderBy('tipo')
            ->get();
    }

    return response()->json($result);
}


    public function reporteInventarioEquipos(Request $request)
    {
        $tipoId = $request->input('tipo_id');
        $estadoId = $request->input('estado');
        $busqueda = $request->input('busqueda'); // Nuevo parámetro de búsqueda
        $perPage = $request->input('per_page', 20);

        $query = DB::table('equipos')
            ->select(
                'equipos.id',
                'equipos.numero_serie',
                'equipos.comentario',
                'equipos.created_at',
                'equipos.estado_id',
                'estados.nombre as estado_nombre',
                'tipo_equipos.id as tipo_equipo_id',
                'tipo_equipos.nombre as tipo_nombre',
                'categorias.nombre as categoria_nombre',
                'modelos.id as modelo_id',
                'modelos.nombre as modelo_nombre',
                'marcas.nombre as marca_nombre'
            )
            ->leftJoin('tipo_equipos', 'equipos.tipo_equipo_id', '=', 'tipo_equipos.id')
            ->leftJoin('categorias', 'tipo_equipos.categoria_id', '=', 'categorias.id')
            ->leftJoin('modelos', 'equipos.modelo_id', '=', 'modelos.id')
            ->leftJoin('marcas', 'modelos.marca_id', '=', 'marcas.id')
            ->leftJoin('estados', 'equipos.estado_id', '=', 'estados.id')
            ->where('equipos.is_deleted', false);

        // Filtro por tipo
        if ($tipoId) {
            $query->where('equipos.tipo_equipo_id', $tipoId);
        }

        // Filtro por estado
        if ($estadoId && $estadoId !== '') {
            $query->where('equipos.estado_id', $estadoId);
        }

        // Filtro de búsqueda general (incluyendo características)
        if ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('equipos.numero_serie', 'like', "%{$busqueda}%")
                    ->orWhere('equipos.comentario', 'like', "%{$busqueda}%")
                    ->orWhere('tipo_equipos.nombre', 'like', "%{$busqueda}%")
                    ->orWhere('categorias.nombre', 'like', "%{$busqueda}%")
                    ->orWhere('modelos.nombre', 'like', "%{$busqueda}%")
                    ->orWhere('marcas.nombre', 'like', "%{$busqueda}%")
                    ->orWhere('estados.nombre', 'like', "%{$busqueda}%")
                    ->orWhereExists(function ($subQuery) use ($busqueda) {
                        $subQuery->select(DB::raw(1))
                            ->from('valores_caracteristicas')
                            ->join('caracteristicas', 'valores_caracteristicas.caracteristica_id', '=', 'caracteristicas.id')
                            ->whereColumn('valores_caracteristicas.equipo_id', 'equipos.id')
                            ->where(function ($innerQuery) use ($busqueda) {
                                $innerQuery->where('caracteristicas.nombre', 'like', "%{$busqueda}%")
                                    ->orWhere('valores_caracteristicas.valor', 'like', "%{$busqueda}%");
                            });
                    });
            });
        }

        // Paginación
        $equipos = $query->orderBy('equipos.created_at', 'desc')->paginate($perPage);

        $equipos->getCollection()->transform(function ($equipo) {
            $valores = DB::table('valores_caracteristicas')
                ->join('caracteristicas', 'valores_caracteristicas.caracteristica_id', '=', 'caracteristicas.id')
                ->where('valores_caracteristicas.equipo_id', $equipo->id)
                ->select('caracteristicas.nombre', 'valores_caracteristicas.valor')
                ->get();

            $equipo->caracteristicas = $valores;
            return $equipo;
        });

        return response()->json($equipos);
    }
}
