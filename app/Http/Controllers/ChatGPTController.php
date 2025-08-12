<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Equipo;
use App\Models\ReservaAula;
use App\Models\ReservaEquipo;
use App\Models\TipoReserva;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ChatGPTController extends Controller
{

    public function sugerirEspacios(Request $request)
    {
        $fecha      = $request->input('fecha');       // YYYY-MM-DD
        $horaInicio = $request->input('horaInicio');  // HH:mm
        $horaFin    = $request->input('horaFin');     // HH:mm
        $personas   = $request->input('personas');
        $fechaFin   = $request->input('fecha_fin');   // opcional
        $user       = Auth::user();
        $userId     = $user?->id;

        if (!$fecha || !$horaInicio || !$horaFin || !$personas) {
            return response()->json(['error' => 'Datos incompletos.'], 422);
        }

        // 1️⃣ Primero: validar si existe una RESERVA DE EQUIPO del usuario que se solape
        $reservaEquipo = ReservaEquipo::where('user_id', $userId)
            ->whereDate('fecha_reserva', $fecha)
            ->whereIn('estado', ['Pendiente', 'Aprobado'])
            ->where(function ($q) use ($horaInicio, $horaFin) {
                // solape: inicio < fin_reserva AND fin > inicio_reserva
                $q->whereRaw('? < TIME(fecha_entrega) AND ? > TIME(fecha_reserva)', [$horaInicio, $horaFin]);
            })
            ->first();

        if ($reservaEquipo) {
            return response()->json([
                'tipo'     => 'reserva_equipo',
                'mensaje'  => 'Ya tienes una reserva de equipo para este horario. Puedes editarla o cancelarla.',
                'reserva'  => $reservaEquipo,
            ]);
        }

        // 2️⃣ Luego: validar si existe una RESERVA DE AULA del usuario (con aula cargada)
        $reservaAula = ReservaAula::with([
            'aula:id,name,path_modelo',
        ])
            ->where('user_id', $userId)
            ->whereDate('fecha', $fecha)
            ->whereIn('estado', ['Pendiente', 'Aprobado'])
            ->whereHas('bloques', function ($q) use ($horaInicio, $horaFin) {
                $q->where(function ($query) use ($horaInicio, $horaFin) {
                    // solape: inicio < fin_bloque AND fin > inicio_bloque
                    $query->whereRaw('? < hora_fin AND ? > hora_inicio', [$horaInicio, $horaFin]);
                });
            })
            ->first();

        if ($reservaAula) {
            $a = $reservaAula->aula; // puede venir null si se borró el aula
            return response()->json([
                'tipo'    => 'reserva_aula',
                'mensaje' => 'Ya tienes una reserva de aula para este horario. Puedes usarla o cancelarla.',
                'reserva' => [
                    'id'       => $reservaAula->id,
                    'fecha'    => optional($reservaAula->fecha)->format('Y-m-d'),
                    'horario'  => $reservaAula->horario,
                    'estado'   => $reservaAula->estado,
                    'aula_id'  => $reservaAula->aula_id,
                    'aula'     => $a ? [
                        'id'          => $a->id,
                        'nombre'      => $a->name,
                        'path_modelo' => $a->path_modelo,
                    ] : null,
                ],
            ]);
        }

        // 3️⃣ Si no hay conflictos: sugerir aulas disponibles (flujo existente)
        $aulas = Aula::where('capacidad_maxima', '>=', $personas)
            ->with(['reservas' => function ($q) use ($fecha) {
                $q->where('fecha', $fecha)
                    ->whereIn('estado', ['Pendiente', 'Aprobado']);
            }, 'imagenes'])
            ->get()
            ->filter(function ($aula) use ($horaInicio, $horaFin) {
                foreach ($aula->reservas as $reserva) {
                    $resHorario    = $reserva->horario ? explode('-', $reserva->horario) : [null, null];
                    $resHoraInicio = trim($resHorario[0]);
                    $resHoraFin    = trim($resHorario[1]);
                    if (
                        $resHoraInicio && $resHoraFin &&
                        (strtotime($horaInicio) < strtotime($resHoraFin) && strtotime($horaFin) > strtotime($resHoraInicio))
                    ) {
                        return false; // cruza con una reserva existente
                    }
                }
                return true; // disponible
            })
            ->take(5)
            ->values();

        $respuesta = $aulas->map(function ($aula) {
            return [
                'id'                => $aula->id,
                'nombre'            => $aula->name,
                'capacidad_maxima'  => $aula->capacidad_maxima,
                'descripcion'       => $aula->descripcion,
                'path_modelo'       => $aula->path_modelo,
            ];
        });

        return response()->json([
            'tipo'  => 'sugerencia',
            'aulas' => $respuesta,
        ]);
    }
}
