<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\ReservaAula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReservaAulaController extends Controller
{
    public function aulas()
    {
        return response()->json(Aula::all());
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
}
