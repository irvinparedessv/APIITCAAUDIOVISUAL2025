<?php
// app/Http/Controllers/PrediccionEquipoController.php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\TipoEquipo;
use App\Services\PrediccionEquipoService;
use Illuminate\Http\Request;

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
        ->with('tipoEquipo')
        ->orderBy('nombre');

    if ($request->has('search')) {
        $query->where('nombre', 'like', '%'.$request->search.'%');
    }

    $equipos = $query->limit($request->input('limit', 10))->get();

    return response()->json([
        'success' => true,
        'data' => $equipos->map(function ($equipo) {
            return [
                'id' => $equipo->id,
                'nombre' => $equipo->nombre,
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
        $equipos = Equipo::where('is_deleted', false)
            ->withCount(['reservas as total_reservas' => function ($query) {
                $query->whereIn('estado', ['Aprobado', 'Completado']);
            }])
            ->orderByDesc('total_reservas')
            ->take(5)
            ->get();

        $resultados = [];

        foreach ($equipos as $equipo) {
            try {
                $prediccion = $predictor->predecirReservasMensualesPorEquipo(6, $equipo->id);
                $resultados[] = [
                    'equipo' => $equipo,
                    'prediccion' => $this->formatearParaFrontend($prediccion),
                ];
            } catch (\Exception $e) {
                $resultados[] = [
                    'equipo' => $equipo,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $resultados,
            'message' => 'Top 5 equipos con predicción generada',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}



    
}