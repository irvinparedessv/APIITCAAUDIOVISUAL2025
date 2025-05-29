<?php

namespace App\Http\Controllers;

use App\Helpers\BitacoraHelper;
use App\Models\Aula;
use App\Models\ReservaAula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Notifications\EstadoReservaAulaNotification;

class ReservaAulaController extends Controller
{
    public function aulas()
    {
        $aulas = Aula::with('primeraImagen')
            ->get()
            ->map(function ($aula) {
                return [
                    'id' => $aula->id,
                    'name' => $aula->name,
                    'image_path' => $aula->primeraImagen
                        ? url($aula->primeraImagen->image_path)
                        : null,
                ];
            });

        return response()->json($aulas);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'aula_id' => 'required|exists:aulas,id',
            'fecha' => 'required|date',
            'horario' => 'required|string',
            'user_id' => 'required|exists:users,id',
            'estado' => 'nullable|string|in:pendiente,confirmada,cancelada',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $reserva = ReservaAula::create([
            'aula_id' => $request->aula_id,
            'fecha' => $request->fecha,
            'horario' => $request->horario,
            'user_id' => $request->user_id,
            'estado' => $request->estado ?? 'pendiente',
        ]);

        return response()->json([
            'message' => 'Reserva creada exitosamente',
            'reserva' => $reserva
        ], 201);
    }

    public function reservas(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $query = ReservaAula::with(['aula', 'user']);

        if ($from && $to) {
            $query->whereBetween('fecha', [$from, $to]);
        }

        return response()->json($query->get());
    }
    
    public function actualizarEstado(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|in:approved,rejected,returned',
            'comentario' => 'nullable|string',
        ]);

        $reserva = ReservaAula::with('user')->findOrFail($id);

        $estadoAnterior = $reserva->estado;
        $reserva->estado = $request->estado;
        $reserva->comentario = $request->comentario;
        $reserva->save();

        if ($reserva->user) {
            // Notificar al usuario
            $reserva->user->notify(new EstadoReservaAulaNotification($reserva));

            // Registrar en bitácora
            BitacoraHelper::registrarCambioEstadoReservaAula(
                $id,
                $estadoAnterior,
                $request->estado,
                $reserva->user->first_name . ' ' . $reserva->user->last_name
            );
        }

        return response()->json(['message' => 'Estado actualizado correctamente']);
    }

}
