<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Equipo;
use App\Models\TipoReserva;
use Carbon\Carbon;

class ChatGPTController extends Controller
{

    public function sugerirEspacios(Request $request)
    {
        $fecha = $request->input('fecha'); // formato YYYY-MM-DD
        $horaInicio = $request->input('horaInicio'); // formato HH:mm
        $horaFin = $request->input('horaFin'); // formato HH:mm
        $personas = $request->input('personas');

        // Validación rápida
        if (!$fecha || !$horaInicio || !$horaFin || !$personas) {
            return response()->json(['error' => 'Datos incompletos.'], 422);
        }

        // Busca aulas con capacidad suficiente
        $aulas = Aula::where('capacidad_maxima', '>=', $personas)
            ->with(['reservas' => function ($q) use ($fecha) {
                $q->where('fecha', $fecha)
                    ->whereIn('estado', ['Pendiente', 'Aprobado']);
            }, 'imagenes'])
            ->get()
            ->filter(function ($aula) use ($horaInicio, $horaFin) {
                // Verifica que no haya reservas que se crucen con el horario solicitado
                foreach ($aula->reservas as $reserva) {
                    $resHorario = $reserva->horario ? explode('-', $reserva->horario) : [null, null];
                    $resHoraInicio = trim($resHorario[0]);
                    $resHoraFin = trim($resHorario[1]);
                    if (
                        $resHoraInicio && $resHoraFin &&
                        // Detecta cruce: inicio < fin reserva Y fin > inicio reserva
                        (strtotime($horaInicio) < strtotime($resHoraFin) && strtotime($horaFin) > strtotime($resHoraInicio))
                    ) {
                        return false; // Cruce detectado
                    }
                }
                return true; // No cruza con ninguna reserva, aula disponible
            })
            ->take(5) // Solo 5 aulas
            ->values();

        // Arma el listado de respuesta
        $respuesta = $aulas->map(function ($aula) {
            return [
                'id' => $aula->id,
                'nombre' => $aula->name,
                'capacidad_maxima' => $aula->capacidad_maxima,
                'descripcion' => $aula->descripcion,
                'imagenes' => $aula->imagenes->map(fn($img) => $img->url),
                'path_modelo' => $aula->path_modelo,
            ];
        });

        return response()->json([
            'aulas' => $respuesta,
        ]);
    }
}
