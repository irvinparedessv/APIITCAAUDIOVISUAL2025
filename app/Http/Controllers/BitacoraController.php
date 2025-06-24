<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bitacora;
use Illuminate\Support\Carbon;

class BitacoraController extends Controller
{
    public function index(Request $request)
    {
        $query = Bitacora::query();

        if ($request->filled('modulo') && $request->modulo !== 'todos') {
            $query->where('modulo', $request->modulo);
        }

        if ($request->filled('fecha_inicio') && !$request->filled('fecha_fin')) {
            $query->whereDate('created_at', Carbon::parse($request->fecha_inicio));
        } elseif ($request->filled('fecha_fin') && !$request->filled('fecha_inicio')) {
            $query->whereDate('created_at', Carbon::parse($request->fecha_fin));
        } elseif ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->fecha_inicio)->startOfDay(),
                Carbon::parse($request->fecha_fin)->endOfDay(),
            ]);
        }
        
        return $query->latest()->paginate(10);
    }

    public function historialReserva($reservaId)
    {
        return Bitacora::where(function($query) use ($reservaId) {
                $query->where('modulo', 'Reserva Equipo')
                    ->orWhere('modulo', 'Reservas');
            })
            ->where(function($query) use ($reservaId) {
                $query->where('descripcion', 'like', "%reserva_id:{$reservaId}%")
                    ->orWhere('descripcion', 'like', "%ID Reserva: {$reservaId}%");
            })
            ->select(['id', 'nombre_usuario', 'accion', 'created_at', 'descripcion'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function historialReservaAula($reservaId)
{
    return Bitacora::where('modulo', 'LIKE', '%Aula%')
        ->where(function($query) use ($reservaId) {
            $query->where('descripcion', 'LIKE', "%{$reservaId}%")
                ->orWhere('descripcion', 'LIKE', "%reserva_id:{$reservaId}%")
                ->orWhere('descripcion', 'LIKE', "%Reserva Aula {$reservaId}%");
        })
        ->select(['id', 'nombre_usuario', 'accion', 'created_at', 'descripcion'])
        ->orderBy('created_at', 'desc')
        ->get();
}

}

    
