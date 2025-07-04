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

        $query = ReservaEquipo::with(['user', 'equipos', 'tipoReserva'])
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
            return [
                'id' => $reserva->id,
                'usuario' => $reserva->user ? $reserva->user->first_name . ' ' . $reserva->user->last_name : 'N/A',
                'tipo' => $reserva->tipoReserva->nombre ?? 'Sin tipo',
                'nombre_recurso' => $reserva->equipos->pluck('nombre')->implode(', '), // Equipos como string
                'fecha' => \Carbon\Carbon::parse($reserva->fecha_reserva)->format('Y-m-d'),
                'horario' => \Carbon\Carbon::parse($reserva->fecha_reserva)->format('H:i') . ' - ' . \Carbon\Carbon::parse($reserva->fecha_entrega)->format('H:i'),
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

        $query = ReservaEquipo::with(['user', 'equipos', 'tipoReserva'])
            ->where('user_id', $request->usuario_id);

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

        $reservas->getCollection()->transform(function ($reserva) {
            return [
                'id' => $reserva->id,
                'usuario' => $reserva->user ? $reserva->user->first_name . ' ' . $reserva->user->last_name : 'N/A',
                'tipo' => $reserva->tipoReserva->nombre ?? 'Sin tipo',
                'nombre_recurso' => $reserva->equipos->pluck('nombre')->implode(', '),
                'fecha' => \Carbon\Carbon::parse($reserva->fecha_reserva)->format('Y-m-d'),
                'horario' => \Carbon\Carbon::parse($reserva->fecha_reserva)->format('H:i') . ' - ' . \Carbon\Carbon::parse($reserva->fecha_entrega)->format('H:i'),
                'estado' => $reserva->estado,
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
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
        $estado = $request->input('estado');
        $search = $request->input('search');
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $query = ReservaAula::with(['aula', 'user'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if ($estado && strtolower($estado) !== 'todos') {
            $query->where('estado', $estado);
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

    public function reporteUsoEquipos(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $tipoEquipo = $request->input('tipo_equipo');

        $query = DB::table('equipo_reserva')
            ->join('reserva_equipos', 'equipo_reserva.reserva_equipo_id', '=', 'reserva_equipos.id')
            ->join('equipos', 'equipo_reserva.equipo_id', '=', 'equipos.id')
            ->join('tipo_equipos', 'equipos.tipo_equipo_id', '=', 'tipo_equipos.id')
            ->select(
                'equipos.nombre as equipo',
                'tipo_equipos.nombre as tipo_equipo',
                DB::raw('SUM(equipo_reserva.cantidad) as total_cantidad')
            )
            ->groupBy('equipos.id', 'equipos.nombre', 'tipo_equipos.nombre')
            ->orderByDesc('total_cantidad');

        if ($from && $to) {
            $query->whereBetween('reserva_equipos.fecha_reserva', [$from, $to]);
        }

        // FILTRO POR TIPO DE EQUIPO
        if ($tipoEquipo) {
            $query->where('tipo_equipos.nombre', $tipoEquipo);
        }

        return response()->json($query->get());
    }

    public function reporteHorariosSolicitados(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $tipo = $request->input('tipo');
        $aulaId = $request->input('aula_id');
        $equipoId = $request->input('equipo_id');

        // Aulas
        $aulasQuery = DB::table('reserva_aulas as ra')
            ->select(
                'ra.horario',
                DB::raw("'aula' as tipo"),
                DB::raw('NULL as equipo_nombre'),
                DB::raw('COUNT(*) as total')
            )
            ->when($from && $to, fn($q) => $q->whereBetween('ra.fecha', [$from, $to]))
            ->when($tipo === 'aula' && $aulaId, fn($q) => $q->where('ra.aula_id', $aulaId))
            ->groupBy('ra.horario');

        // Equipos
        $equiposQuery = DB::table('reserva_equipos as re')
            ->join('equipo_reserva as er', 'er.reserva_equipo_id', '=', 're.id')
            ->join('equipos as e', 'e.id', '=', 'er.equipo_id')
            ->select(
                DB::raw("DATE_FORMAT(re.fecha_reserva, '%H:%i') as horario"),
                DB::raw("'equipo' as tipo"),
                'e.nombre as equipo_nombre',
                DB::raw('COUNT(*) as total')
            )
            ->when($from && $to, fn($q) => $q->whereBetween(DB::raw('DATE(re.fecha_reserva)'), [$from, $to]))
            ->when($tipo === 'equipo' && $equipoId, fn($q) => $q->where('e.id', $equipoId))
            ->groupBy('horario', 'e.nombre');

        // Condición para retornar
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
        $tipo = $request->input('tipo_id'); // Coincide con el frontend
        $estado = $request->input('estado');
        $perPage = $request->input('per_page', 20); // Valor por defecto: 20

        $query = DB::table('equipos')
            ->select('equipos.id', 'equipos.nombre', 'equipos.cantidad', 'equipos.estado', 'tipo_equipos.nombre as tipo_nombre')
            ->leftJoin('tipo_equipos', 'equipos.tipo_equipo_id', '=', 'tipo_equipos.id')
            ->where('equipos.is_deleted', 0);

        if ($tipo) {
            $query->where('equipos.tipo_equipo_id', $tipo);
        }

        if ($estado === 'disponible') {
            $query->where('equipos.estado', 1)
                ->where('equipos.cantidad', '>', 0);
        } elseif ($estado === 'no_disponible') {
            $query->where(function ($q) {
                $q->where('equipos.estado', 0)
                    ->orWhere('equipos.cantidad', '=', 0);
            });
        }

        $equipos = $query->orderBy('equipos.nombre')->paginate($perPage);

        return response()->json($equipos);
    }
}
