<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bitacora;

class BitacoraController extends Controller
{
    public function index()
    {
        return Bitacora::latest()->paginate(20);
    }

    public function historialReserva($reservaId)
    {
        return Bitacora::where('modulo', 'Reservas')
            ->where('descripcion', 'like', "%reserva_id:{$reservaId}%")
            ->select(['id', 'nombre_usuario', 'accion', 'created_at', 'descripcion'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
