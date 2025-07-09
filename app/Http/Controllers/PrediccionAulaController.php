<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PrediccionAulaService;

class PrediccionAulaController extends Controller
{
    protected $prediccionService;

    public function __construct(PrediccionAulaService $prediccionService)
    {
        $this->prediccionService = $prediccionService;
    }

    public function predecir($aulaId, Request $request)
    {
        try {
            $meses = $request->input('meses', 6);

            $resultado = $this->prediccionService->predecirReservasPorAula($aulaId, $meses);

            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo realizar la predicción',
                'mensaje' => $e->getMessage(),
            ], 422);
        }
    }

    public function prediccionGeneralAulas()
    {
        try {
            $resultado = app(PrediccionAulaService::class)->predecirReservasAulasGenerales();
            return response()->json($resultado);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
