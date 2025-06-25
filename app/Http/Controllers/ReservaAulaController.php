<?php

namespace App\Http\Controllers;

use App\Helpers\BitacoraHelper;
use App\Models\Aula;
use App\Models\ReservaAula;
use App\Models\Role;
use App\Models\User;
use App\Notifications\CancelarReservaAulaPrestamista;
use App\Notifications\ConfirmarReservaAulaUsuario;
use App\Notifications\EmailEstadoAulaNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Notifications\EstadoReservaAulaNotification;
use App\Notifications\NotificarResponsableReservaAula;
use App\Notifications\NuevaReservaAulaNotification;
use Illuminate\Support\Facades\Auth;
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
            'estado' => 'nullable|string|in:pendiente,aprobado,cancelado,rechazado',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        Log::info('Fecha recibida en request:', ['fecha' => $request->fecha]);

        $reserva = ReservaAula::create([
            'aula_id' => $request->aula_id,
            'fecha' => $request->fecha,
            'horario' => $request->horario,
            'user_id' => $request->user_id,
            'estado' => $request->estado ?? 'Pendiente',
        ]);

         // Calcular en qué página cae esta reserva (según paginación de 10 por página)
        $pagina = $this->calcularPaginaReserva($reserva->id, 10);

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
            $responsable->notify(new NuevaReservaAulaNotification($reserva, $responsable->id, $pagina));
            Log::info("Notificación de aula enviada a: " . $responsable->id);
            //$responsable->notify(new NotificarResponsableReservaAula($reserva));
        }

        //$reserva->user->notify(new ConfirmarReservaAulaUsuario($reserva));

    return response()->json([
        'message' => 'Reserva de aula creada exitosamente',
        'reserva' => [
            'id' => $reserva->id,
            'aula_id' => $reserva->aula_id,
            'fecha' => $reserva->fecha->format('Y-m-d'), // ✅ esto es lo que necesitas
            'horario' => $reserva->horario,
            'user_id' => $reserva->user_id,
            'estado' => $reserva->estado,
            'created_at' => $reserva->created_at->toDateTimeString(),
            'updated_at' => $reserva->updated_at->toDateTimeString(),
            'aula' => $reserva->aula,
            'user' => $reserva->user,
        ],
        'pagina' => $pagina,
    ], 201);        

    }

    public function reservas(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $status = $request->query('status');
        $search = $request->query('search'); // nuevo parámetro para buscar por aula o usuario
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);

        $query = ReservaAula::with(['aula', 'user']);

        if ($from && $to) {
            $query->whereBetween('fecha', [$from, $to]);
        }

        if ($status && strtolower($status) !== 'todos') {
            $query->where('estado', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('aula', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%$search%");
                })->orWhereHas('user', function ($q3) use ($search) {
                    $q3->where('first_name', 'like', "%$search%")
                        ->orWhere('last_name', 'like', "%$search%");
                });
            });
        }

        $reservas = $query->orderBy('fecha', 'desc')->paginate($perPage, ['*'], 'page', $page);

        return response()->json($reservas);
    }



    public function actualizarEstado(Request $request, $id)
    {
        $user = Auth::user();

        $request->validate([
            'estado' => 'required|in:Aprobado,Cancelado,Rechazado',
            'comentario' => 'nullable|string',
        ]);

        $reserva = ReservaAula::with('user')->findOrFail($id);

        if (strtolower($user->role->nombre) === 'prestamista') {
            if ($reserva->user_id !== $user->id) {
                return response()->json(['error' => 'No autorizado.'], 403);
            }

            if (strtolower($request->estado) !== 'cancelado') {
                return response()->json(['error' => 'Solo puedes cancelar tu reserva.'], 403);
            }

            if (strtolower($reserva->estado) !== 'pendiente') {
                return response()->json(['error' => 'Solo puedes cancelar reservas pendientes.'], 400);
            }
        }

        $estadoAnterior = $reserva->estado;
        $reserva->estado = $request->estado;
        $reserva->comentario = $request->comentario;
        $reserva->save();

        $pagina = $this->calcularPaginaReserva($id);

        // 🔥 Notificar dependiendo de quién realiza la acción
        if (strtolower($user->role->nombre) === 'prestamista' && strtolower($request->estado) === 'cancelado') {
            // No se notifica al prestamista (ya lo hizo él), sino a los encargados y admins
            $this->notificarResponsablesPorCancelacion($user, $reserva, $pagina);
        } else {
            // Encargado o admin cambia estado → notificar al prestamista
            if ($reserva->user) {
                $reserva->user->notify(new EstadoReservaAulaNotification($reserva, $pagina));
                //$reserva->user->notify(new EmailEstadoAulaNotification($reserva));
            }
        }

        BitacoraHelper::registrarCambioEstadoReservaAula(
            $id,
            $estadoAnterior,
            $request->estado,
            $user->first_name . ' ' . $user->last_name
        );

        $reserva->load(['aula', 'user']); // 👈 Asegura que vengan las relaciones

        return response()->json([
            'message' => 'Estado actualizado correctamente.',
            'reserva' => $reserva,
        ]);return response()->json(['message' => 'Estado actualizado correctamente.']); 
    }



    
    public function show($id)
    {
        $reserva = ReservaAula::with(['aula', 'user'])->findOrFail($id);

        return response()->json($reserva);
    }

    private function calcularPaginaReserva(int $reservaId, int $porPagina = 10): int
    {
        $ids = ReservaAula::orderBy('created_at', 'desc')->pluck('id')->toArray();
        $index = array_search($reservaId, $ids);

        return $index === false ? 1 : (int) ceil(($index + 1) / $porPagina);
    }

    private function notificarResponsablesPorCancelacion($usuario, ReservaAula $reserva, int $pagina)
    {
        $rolesResponsables = Role::whereIn('nombre', ['encargado', 'administrador'])->pluck('id');
        $responsables = User::whereIn('role_id', $rolesResponsables)->get();

        foreach ($responsables as $responsable) {
            Log::info("Enviando notificación de cancelación al responsable ID: {$responsable->id}");
            $responsable->notify(new CancelarReservaAulaPrestamista($reserva, $responsable->id, $pagina));
            //$responsable->notify(new EmailEstadoAulaNotification($reserva));

        }
    }


}
