<?php

namespace App\Http\Controllers;

use App\Helpers\BitacoraHelper;
use App\Models\Aula;
use App\Models\ReservaAula;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ConfirmarReservaAulaUsuario;
use App\Notifications\EmailEstadoAulaNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Notifications\EstadoReservaAulaNotification;
use App\Notifications\NotificarResponsableReservaAula;
use App\Notifications\NuevaReservaAulaNotification;
use Illuminate\Support\Facades\Log;

class ReservaAulaController extends Controller
{
    public function aulas()
    {
        $aulas = Aula::with(['primeraImagen', 'horarios'])->get()->map(function ($aula) {
            return [
                'id' => $aula->id,
                'name' => $aula->name,
                'image_path' => $aula->primeraImagen
                    ? url($aula->primeraImagen->image_path)
                    : null,
                'horarios' => $aula->horarios->map(function ($horario) {
                    return [
                        'start_date' => $horario->start_date,
                        'end_date' => $horario->end_date,
                        'start_time' => $horario->start_time,
                        'end_time' => $horario->end_time,
                        'days' => json_decode($horario->days),
                    ];
                }),
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

        // Cargar relaciones para notificaciones
        $reserva->load(['user', 'aula']);

        // Obtener responsables (encargados y administradores), excluyendo al usuario que hizo la reserva
        $responsableRoleIds = Role::whereIn('nombre', ['encargado', 'administrador'])->pluck('id');
        $responsables = User::whereIn('role_id', $responsableRoleIds)
            ->where('id', '!=', $reserva->user_id) // Excluye al usuario que hizo la reserva
            ->get();

        Log::info('Responsables encontrados para aula:', $responsables->pluck('id')->toArray());

        foreach ($responsables as $responsable) {
            // Enviar notificación real-time (broadcast + db)
            $responsable->notify(new NuevaReservaAulaNotification($reserva, $responsable->id));
            Log::info("Notificación de aula enviada a: " . $responsable->id);
            //$responsable->notify(new NotificarResponsableReservaAula($reserva));
        }

        //$reserva->user->notify(new ConfirmarReservaAulaUsuario($reserva));

        return response()->json([
            'message' => 'Reserva de aula creada exitosamente',
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

        $reserva = ReservaAula::findOrFail($id);
        $reserva->estado = $request->estado;
        $reserva->comentario = $request->comentario;
        $reserva->save();
        $reserva = ReservaAula::with('user')->findOrFail($id);

        $estadoAnterior = $reserva->estado;
        $reserva->estado = $request->estado;
        $reserva->comentario = $request->comentario;
        $reserva->save();

        // Ver estado de las reservas de las aulas
        if ($reserva->user) {
            $reserva->user->notify(new EstadoReservaAulaNotification($reserva));
        }


        return response()->json(['message' => 'Estado actualizado correctamente']);
    }
}
