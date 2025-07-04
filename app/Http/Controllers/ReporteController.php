<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReservaEquipo;

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
}
