<?php

namespace App\Http\Controllers;

use App\Helpers\BitacoraHelper;
use App\Mail\EstadoReservaMailable;
use App\Models\Bitacora;
use App\Models\CodigoQrReserva;
use App\Models\CodigoQrReservaEquipo;
use App\Models\EquipmentReservation;
use App\Models\Equipo;
use App\Models\ReservaEquipo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Notifications\ConfirmarReservaUsuario;
use App\Notifications\EstadoReservaEquipoNotification;
use App\Notifications\EstadoReservaNotification;
use App\Notifications\NotificarResponsableReserva;
use App\Notifications\NuevaReservaNotification;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;


class ReservaEquipoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user(); // Usuario autenticado
        $query = ReservaEquipo::query();

        // Si NO es superusuario (por nombre del rol), filtra por su propio ID
        if ($user->role->nombre !== 'Administrador') {
            $query->where('user_id', $user->id);
        }

        $reservas = $query->with(['user', 'equipos', 'codigoQr', 'tipoReserva'])->get();

        return response()->json($reservas);
    }


    public function getByUser(Request $request, $id)
    {
        /** @var User $user */
        $user = auth()->user();
        $perPage = $request->input('per_page', 15); // Items por página

        $query = ReservaEquipo::with(['user', 'equipos', 'codigoQr', 'tipoReserva'])
            ->orderBy('created_at', 'DESC');

        // Filtros opcionales
        if ($request->has('estado') && $request->estado !== 'Todos') {
            $query->where('estado', $request->estado);
        }
        if ($request->has('tipo_reserva') && $request->tipo_reserva !== 'Todos') {
            $tipoReserva = $request->tipo_reserva;
            $query->whereHas('tipoReserva', function ($q) use ($tipoReserva) {
                $q->where('nombre', $tipoReserva);
            });
        }

        if (in_array($user->role->nombre, ['Administrador', 'Encargado'])) {
            // Admin ve todas las reservas paginadas
            $reservas = $query->paginate($perPage);
        } else {
            // Usuarios normales solo ven sus propias reservas
            if ($user->id !== (int)$id) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            $reservas = $query->where('user_id', $id)->paginate($perPage);
        }

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
            'usuario' => $reserva->user->first_name . ' ' . $reserva->user->last_name,
            'equipo' => $reserva->equipos->pluck('nombre')->toArray(), // Relación equipos
            'aula' => $reserva->aula, // Relación aula
            'dia' => $reserva->dia,
            'horaSalida' => $reserva->fecha_reserva,
            'horaEntrada' => $reserva->fecha_entrega,
            'estado' => $reserva->estado,
            'tipoReserva' => $reserva->tipoReserva->nombre ?? null,
        ]);
    }

    public function equiposReserva()
    {
        $equipos = Equipo::activos()->get(['id', 'nombre']);

        return $equipos;
    }
    
    public function store(Request $request)
    {
        // Validar datos
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'equipo' => 'required|array',
            'equipo.*.id' => 'required|exists:equipos,id',
            'equipo.*.cantidad' => 'required|integer|min:1',
            'aula' => 'required',
            'fecha_reserva' => 'required|date',
            'startTime' => 'required|date_format:H:i',
            'endTime' => 'required|date_format:H:i',
            'tipo_reserva_id' => 'required|exists:tipo_reservas,id',
        ]);

        // Verificar disponibilidad
        foreach ($validated['equipo'] as $equipo) {
            $equipoModel = Equipo::find($equipo['id']);
            $inicio = Carbon::parse($validated['fecha_reserva'] . ' ' . $validated['startTime']);
            $fin = Carbon::parse($validated['fecha_reserva'] . ' ' . $validated['endTime']);

            $disponibilidad = $equipoModel->disponibilidadPorRango($inicio, $fin);

            if ($equipo['cantidad'] > $disponibilidad['cantidad_disponible']) {
                return response()->json([
                    'message' => 'No hay suficientes unidades disponibles del equipo: ' . $equipoModel->nombre,
                    'equipo' => $equipoModel->nombre,
                    'disponible' => $disponibilidad['cantidad_disponible']
                ], 400);
            }
        }

        // Crear la reserva
        $reserva = ReservaEquipo::create([
            'user_id' => $validated['user_id'],
            'fecha_reserva' => Carbon::parse($validated['fecha_reserva'] . ' ' . $validated['startTime']),
            'fecha_entrega' => Carbon::parse($validated['fecha_reserva'] . ' ' . $validated['endTime']),
            'aula' => $validated['aula'],
            'estado' => 'Pendiente',
            'tipo_reserva_id' => $validated['tipo_reserva_id'],
        ]);

        // Asociar equipos con cantidades
        $equiposConCantidad = [];
        foreach ($validated['equipo'] as $equipo) {
            $equiposConCantidad[$equipo['id']] = ['cantidad' => $equipo['cantidad']];
        }

        $reserva->equipos()->attach($equiposConCantidad);

        // Crear código QR
        CodigoQrReservaEquipo::create([
            'id' => (string) Str::uuid(),
            'reserva_id' => $reserva->id,
        ]);

        // Cargar relaciones para notificaciones
        $reserva->load(['user', 'equipos', 'aula', 'tipoReserva']);

        // Obtener disponibilidad actualizada de los equipos
        $equiposActualizados = [];
        foreach ($validated['equipo'] as $equipo) {
            $equipoModel = Equipo::find($equipo['id']);
            $disponibilidad = $equipoModel->disponibilidadPorRango(
                $reserva->fecha_reserva,
                $reserva->fecha_entrega
            );
            $equiposActualizados[] = [
                'id' => $equipoModel->id,
                'disponibilidad' => $disponibilidad
            ];
        }

        // Calcular la página donde cae esta reserva
        $pagina = $this->calcularPaginaReserva($reserva->id);

        $userId = $reserva->user->id;
        // Obtener responsables (encargados y administradores), excluyendo al usuario que hizo la reserva
        $responsableRoleIds = Role::whereIn('nombre', ['encargado', 'administrador'])->pluck('id');
        $responsables = User::whereIn('role_id', $responsableRoleIds)
            ->where('id', '!=', $userId) // Excluye al usuario que hizo la reserva
            ->get();
        Log::info('Responsables encontrados:', $responsables->pluck('id')->toArray());

        foreach ($responsables as $responsable) {
            // Evitar duplicar notificación si el responsable es quien hizo la reserva
            if ($responsable->id === $reserva->user->id) {
                continue;
            }

            // Enviar notificación real-time (broadcast + db)
            $responsable->notify(new NuevaReservaNotification($reserva, $responsable->id, $pagina));
            Log::info("Notificación enviada");
            // Enviar correo personalizado
            //$responsable->notify(new NotificarResponsableReserva($reserva));
        }
        // Notificación por correo al usuario
        //$reserva->user->notify(new ConfirmarReservaUsuario($reserva));

        return response()->json([
            'message' => 'Reserva creada exitosamente',
            'reserva' => $reserva->load('equipos'),
            'equipos_actualizados' => $equiposActualizados
        ], 201);
    }



    public function actualizarEstado(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|in:Aprobado,Rechazado,Devuelto',
            'comentario' => 'nullable|string',
        ]);

        $reserva = ReservaEquipo::with(['user.role', 'equipos.tipoEquipo', 'tipoReserva'])->findOrFail($id);

        // Validación adicional
        if (!$reserva->user) {
            Log::error('No se puede actualizar estado: Reserva sin usuario', ['reserva_id' => $id]);
            return response()->json(['error' => 'La reserva no tiene usuario asociado'], 400);
        }

        $estadoAnterior = $reserva->estado;
        $reserva->estado = $request->estado;
        $reserva->comentario = $request->comentario;
        $reserva->save();

        BitacoraHelper::registrarCambioEstadoReserva(
            $id,
            $estadoAnterior,
            $request->estado,
            $reserva->user->first_name . ' ' . $reserva->user->last_name
        );

        try {
            if (strtolower($reserva->user->role->nombre) === 'prestamista') {
                Log::info('Notificando al prestamista...', [
                    'user_id' => $reserva->user->id,
                    'reserva_id' => $reserva->id
                ]);

                $pagina = $this->calcularPaginaReserva($reserva->id);
                $reserva->user->notify(new EstadoReservaEquipoNotification($reserva, $reserva->user->id, $pagina));
            }

            Log::info("Enviando correo a prestamista: {$reserva->user->email}");

            // Mail::to($reserva->user->email)->queue(new EstadoReservaMailable($reserva));

            return response()->json([
                'message' => 'Estado actualizado correctamente',
                'notificacion_enviada' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Error al notificar', [
                'error' => $e->getMessage(),
                'reserva_id' => $reserva->id,
                'user_id' => $reserva->user->id ?? null
            ]);

            return response()->json([
                'message' => 'Estado actualizado pero falló la notificación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getNotificaciones(Request $request)
    {
        $user = $request->user();

        // Obtener solo las notificaciones NO archivadas
        $notificaciones = $user->notifications()
            ->where('is_archived', false) 
            ->orderBy('created_at', 'desc')
            ->get();

        // Para loguear datos antes del map
        Log::info('Notificaciones enviadas al frontend:', [
            'count' => $notificaciones->count(),
            'sample_data' => $notificaciones->first()?->data
        ]);

        // Mapear para transformar estructura 
        $result = $notificaciones->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $notification->data,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
            ];
        });

        return response()->json($result);
    }

    public function verificarDisponibilidad(Request $request, $equipoId)
    {
        $request->validate([
            'fecha' => 'required|date',
            'startTime' => 'required|date_format:H:i',
            'endTime' => 'required|date_format:H:i',
        ]);

        $inicio = Carbon::parse($request->fecha . ' ' . $request->startTime);
        $fin = Carbon::parse($request->fecha . ' ' . $request->endTime);

        $equipo = Equipo::findOrFail($equipoId);
        $disponibilidad = $equipo->disponibilidadPorRango($inicio, $fin);

        return response()->json([
            'disponibilidad' => $disponibilidad,
            'rango' => [
                'inicio' => $inicio->toDateTimeString(),
                'fin' => $fin->toDateTimeString()
            ]
        ]);
    }

    public function reservasDelDia(Request $request)
    {
        $hoy = Carbon::today()->toDateString();

        $user = $request->user();

        if (!in_array($user->role->nombre, ['Administrador', 'Encargado'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $perPage = $request->get('per_page', 15);

        $query = ReservaEquipo::with(['user', 'equipos', 'tipoReserva'])
            ->whereDate('fecha_reserva', $hoy)
            ->orderBy('created_at', 'DESC');

        // Filtros opcionales
        if ($request->has('estado') && $request->estado !== 'Todos') {
            $query->where('estado', $request->estado);
        }
        if ($request->has('tipo_reserva') && $request->tipo_reserva !== 'Todos') {
            $tipoReserva = $request->tipo_reserva;
            $query->whereHas('tipoReserva', function ($q) use ($tipoReserva) {
                $q->where('nombre', $tipoReserva);
            });
        }

        $reservas = $query->paginate($perPage);

        return response()->json($reservas);
    }

    private function calcularPaginaReserva(int $reservaId, int $porPagina = 15): int
    {
        $ids = ReservaEquipo::orderBy('created_at', 'desc')->pluck('id')->toArray();
        $index = array_search($reservaId, $ids);

        return $index === false ? 1 : (int) ceil(($index + 1) / $porPagina);
    }



}
