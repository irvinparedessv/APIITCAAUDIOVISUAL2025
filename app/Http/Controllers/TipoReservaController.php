<?php

namespace App\Http\Controllers;

use App\Models\TipoReserva;
use Illuminate\Http\JsonResponse;

class TipoReservaController extends Controller
{
    /**
     * Retorna todos los tipos de evento no eliminados
     */
    public function index(): JsonResponse
    {
        $tipos = TipoReserva::where('is_deleted', false)
                    ->orderBy('nombre')
                    ->get(['id', 'nombre']);

        return response()->json($tipos);
    }
}
