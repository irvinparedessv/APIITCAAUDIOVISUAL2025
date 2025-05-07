<?php

namespace App\Http\Controllers;

use App\Models\CodigoQrReserva;
use App\Models\CodigoQrReservaEquipo;
use App\Models\EquipmentReservation;
use App\Models\ReservaEquipo;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;


class ReservaEquipoController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->query('user_id');

        $query = ReservaEquipo::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $reservas = $query->with(['user', 'equipos', 'codigoQr'])->get();

        return response()->json($reservas);
    }
    public function getByUser($id)
    {
        // Buscar todas las reservas de ese usuario
        $reservas = ReservaEquipo::where('user_id', $id)
            ->with(['user', 'equipos', 'codigoQr']) // Relación con user, equipos, y codigo qr
            ->get();

        return response()->json($reservas);
    }
    public function show($idQr)
    {
        $codigoQr = CodigoQrReservaEquipo::with('reserva')->where('id', $idQr)->first();

        if (!$codigoQr || !$codigoQr->reserva) {
            return response()->json(['message' => 'Reserva no encontrada'], 404);
        }

        $reserva = $codigoQr->reserva;

        return response()->json([
            'usuario' => $reserva->user->name, // O como tengas la relación
            'equipo' => $reserva->equipos->pluck('nombre')->toArray(), // Relación equipos
            'aula' => $reserva->aula, // Relación aula
            'dia' => $reserva->dia,
            'horaSalida' => $reserva->fecha_reserva,
            'horaEntrada' => $reserva->fecha_entrega,
            'estado' => $reserva->estado,
        ]);
    }

    public function store(Request $request)
    {
        // Validar datos
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'equipo' => 'required|array',
            'equipo.*' => 'exists:equipos,id',
            'aula' => 'required',
            'fecha_reserva' => 'required|date', // solo la fecha
            'startTime' => 'required|date_format:H:i', // solo la hora
            'endTime' => 'required|date_format:H:i',   // solo la hora
        ]);

        // Unir fecha + hora usando Carbon
        $fechaReserva = Carbon::parse($validated['fecha_reserva'] . ' ' . $validated['startTime']);
        $fechaEntrega = Carbon::parse($validated['fecha_reserva'] . ' ' . $validated['endTime']);

        // Crear la reserva
        $reserva = ReservaEquipo::create([
            'user_id' => $validated['user_id'],
            'fecha_reserva' => $fechaReserva,
            'fecha_entrega' => $fechaEntrega,
            'aula' => $validated['aula'],
            'estado' => 'Pendiente', // Puedes recibirlo también del request si quieres
        ]);

        // Asociar equipos
        $reserva->equipos()->attach($validated['equipo']);
        CodigoQrReservaEquipo::create([
            'id' => (string) Str::uuid(), // generas un nuevo UUID para el QR
            'reserva_id' => $reserva->id, // vinculamos a la reserva

        ]);
        return response()->json([
            'message' => 'Reserva creada exitosamente',
            'reserva' => $reserva->load('equipos'),
        ], 201);
    }
}
