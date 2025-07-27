<?php
// app/Http/Controllers/PrediccionEquipoController.php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\TipoEquipo;
use App\Services\PrediccionEquipoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrediccionEquipoController extends Controller
{
    public function predecirReservas(Request $request, PrediccionEquipoService $predictor)
    {
        $request->validate([
            'meses' => 'sometimes|integer|min:1|max:12',
            'tipo_equipo_id' => 'sometimes|exists:tipo_equipos,id'
        ]);

        try {
            $mesesAPredecir = $request->input('meses', 6);
            $tipoEquipoId = $request->input('tipo_equipo_id');

            $resultado = $predictor->predecirReservasMensuales($mesesAPredecir, $tipoEquipoId);

            return response()->json([
                'success' => true,
                'data' => $this->formatearParaFrontend($resultado),
                'message' => 'Predicción generada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function tiposEquipoConPrediccion(PrediccionEquipoService $predictor)
    {
        try {
            $tiposEquipo = TipoEquipo::where('is_deleted', false)->get();
            $resultados = [];

            foreach ($tiposEquipo as $tipo) {
                try {
                    $prediccion = $predictor->predecirReservasMensuales(6, $tipo->id);
                    $resultados[] = [
                        'tipo_equipo' => $tipo,
                        'prediccion' => $this->formatearParaFrontend($prediccion),
                    ];
                } catch (\Exception $e) {
                    $resultados[] = [
                        'tipo_equipo' => $tipo,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $resultados,
                'message' => 'Predicciones por tipo de equipo generadas'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function buscarEquipos(Request $request)
    {
        $request->validate([
            'search' => 'sometimes|string|max:100',
            'limit' => 'sometimes|integer|min:1|max:50'
        ]);

        $query = Equipo::where('is_deleted', false)
            ->where('estado_id', 1) // Cambiado a estado_id para coincidir con tu tabla
            ->with(['modelo.marca', 'tipoEquipo'])
            ->orderBy('numero_serie'); // Usando numero_serie como identificador único

        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('numero_serie', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('modelo', function ($modeloQuery) use ($searchTerm) {
                        $modeloQuery->where('nombre', 'like', '%' . $searchTerm . '%')
                            ->orWhereHas('marca', function ($marcaQuery) use ($searchTerm) {
                                $marcaQuery->where('nombre', 'like', '%' . $searchTerm . '%');
                            });
                    });
            });
        }

        $equipos = $query->limit($request->input('limit', 10))->get();

        return response()->json([
            'success' => true,
            'data' => $equipos->map(function ($equipo) {
                return [
                    'id' => $equipo->id,
                    'numero_serie' => $equipo->numero_serie,
                    'marca' => $equipo->modelo->marca->nombre ?? 'Sin marca',
                    'modelo' => $equipo->modelo->nombre ?? 'Sin modelo',
                    'marca_modelo' => ($equipo->modelo->marca->nombre ?? '') . ' ' . ($equipo->modelo->nombre ?? ''),
                    'tipo' => $equipo->tipoEquipo->nombre ?? 'Sin tipo'
                ];
            })
        ]);
    }


    public function prediccionPorEquipo($id, PrediccionEquipoService $predictor)
    {
        try {
            $prediccion = $predictor->predecirReservasMensualesPorEquipo(6, $id);

            return response()->json([
                'success' => true,
                'data' => $this->formatearParaFrontend($prediccion),
                'equipo' => Equipo::findOrFail($id)->only('id', 'nombre', 'codigo')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    protected function formatearParaFrontend(array $resultado): array
    {
        $historicoFormateado = [];
        foreach ($resultado['historico'] as $mes => $data) {
            $historicoFormateado[] = [
                'mes' => $data['mes_nombre'],
                'cantidad' => $data['total'],
                'tipo' => 'Histórico',
                'mes_numero' => $mes,
            ];
        }

        $prediccionesFormateadas = [];
        foreach ($resultado['predicciones'] as $mes => $data) {
            $prediccionesFormateadas[] = [
                'mes' => $data['mes'],
                'cantidad' => $data['prediccion'],
                'tipo' => 'Predicción',
                'mes_numero' => $mes,
                'detalle' => [
                    'regresion_lineal' => $data['regresion_lineal'],
                    'svr' => $data['svr'],
                ],
            ];
        }

        return [
            'historico' => $historicoFormateado,
            'predicciones' => $prediccionesFormateadas,
            'precision' => $resultado['precision'] ?? null,
            'datos_crudos' => $resultado, // Para depuración
        ];
    }


    public function top5EquiposConPrediccion(PrediccionEquipoService $predictor)
    {
        try {
            $fechaInicio = Carbon::now()->subMonths(6);

            // Consulta con joins para contar las reservas válidas de cada equipo
            $equipos = DB::table('equipos as e')
                ->join('modelos as mo', 'e.modelo_id', '=', 'mo.id')
                ->join('marcas as ma', 'mo.marca_id', '=', 'ma.id')
                ->join('estados as es', 'e.estado_id', '=', 'es.id')
                ->leftJoin('equipo_reserva as er', 'er.equipo_id', '=', 'e.id')
                ->leftJoin('reserva_equipos as r', function ($join) use ($fechaInicio) {
                    $join->on('r.id', '=', 'er.reserva_equipo_id')
                        ->whereIn('r.estado', ['Aprobado', 'Completado'])
                        ->where('r.fecha_reserva', '>=', $fechaInicio);
                })
                ->where('e.is_deleted', false)
                ->where('es.id', 1) // estado activo
                ->select(
                    'e.id',
                    'e.numero_serie', // Añadido el número de serie
                    'ma.nombre as marca',
                    'mo.nombre as modelo',
                    DB::raw("CONCAT(ma.nombre, ' ', mo.nombre) as marca_modelo"),
                    DB::raw('COUNT(er.id) as total_reservas')
                )
                ->groupBy('e.id', 'e.numero_serie', 'ma.nombre', 'mo.nombre')
                ->orderByDesc('total_reservas')
                ->orderBy('e.numero_serie') // Orden secundario por número de serie
                ->limit(5)
                ->get();

            $resultados = [];

            foreach ($equipos as $equipo) {
                try {
                    $prediccion = $predictor->predecirReservasMensualesPorEquipo(6, $equipo->id);
                    $resultados[] = [
                        'equipo' => [
                            'id' => $equipo->id,
                            'numero_serie' => $equipo->numero_serie, // Incluido en la respuesta
                            'marca' => $equipo->marca,
                            'modelo' => $equipo->modelo,
                            'marca_modelo' => $equipo->marca_modelo,
                            'total_reservas' => $equipo->total_reservas,
                        ],
                        'prediccion' => $this->formatearParaFrontend($prediccion),
                    ];
                } catch (\Exception $e) {
                    $resultados[] = [
                        'equipo' => (array)$equipo,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $resultados,
                'message' => 'Top 5 equipos activos con predicción (últimos 6 meses) ordenados por reservas y número de serie',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
