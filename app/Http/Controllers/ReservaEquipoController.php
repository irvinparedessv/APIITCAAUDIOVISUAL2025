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
use App\Notifications\CancelarReservaEquipoPrestamista;
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

        $reservas->transform(function ($reserva) {
            return [
                ...$reserva->toArray(),
                'documento_url' => $reserva->documento_evento_url,
            ];
        });

        return response()->json($reservas);
    }


    public function getByUser(Request $request, $id)
    {
        $user = Auth::user();
        $perPage = $request->input('per_page', 15);

        $query = ReservaEquipo::with(['user', 'equipos', 'codigoQr', 'tipoReserva'])
            ->orderBy('created_at', 'DESC');

        // Filtro de búsqueda por texto
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                // Campos directos de reserva_equipos
                $q->where('aula', 'LIKE', "%{$searchTerm}%")
                ->orWhere('estado', 'LIKE', "%{$searchTerm}%")
                ->orWhere('comentario', 'LIKE', "%{$searchTerm}%")
                
                // Búsqueda en relación con usuario
                ->orWhereHas('user', function($q) use ($searchTerm) {
                    $q->where('first_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                })
                
                // Búsqueda en relación con tipo de reserva
                ->orWhereHas('tipoReserva', function($q) use ($searchTerm) {
                    $q->where('nombre', 'LIKE', "%{$searchTerm}%");
                })
                
                // Búsqueda en relación con equipos
                ->orWhereHas('equipos', function($q) use ($searchTerm) {
                    $q->where('nombre', 'LIKE', "%{$searchTerm}%");
                });
            });
        }

        // Filtro por estado
        if ($request->has('estado') && $request->estado !== 'Todos') {
            $query->where('estado', $request->estado);
        }

        // Filtro por tipo de reserva
        if ($request->has('tipo_reserva') && $request->tipo_reserva !== 'Todos') {
            $tipoReserva = $request->tipo_reserva;
            $query->whereHas('tipoReserva', function($q) use ($tipoReserva) {
                $q->where('nombre', $tipoReserva);
            });
        }

        // Filtro por fecha inicio
        if ($request->has('fecha_inicio')) {
            $query->whereDate('fecha_reserva', '>=', $request->fecha_inicio);
        }

        // Filtro por fecha fin
        if ($request->has('fecha_fin')) {
            $query->whereDate('fecha_reserva', '<=', $request->fecha_fin);
        }

        // Lógica de permisos
        if (in_array($user->role->nombre, ['Administrador', 'Encargado'])) {
            $reservas = $query->paginate($perPage);
        } else {
            if ($user->id !== (int)$id) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            $reservas = $query->where('user_id', $id)->paginate($perPage);
        }

        // Transformar para agregar la URL del documento
        $reservas->getCollection()->transform(function ($reserva) {
            return [
                ...$reserva->toArray(),
                'documento_url' => $reserva->documento_evento
                    ? asset('storage/' . $reserva->documento_evento)
                    : null,
            ];
        });

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
            'documento_evento' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);
        

        // Obtener fecha y hora actual
        $now = Carbon::now();
        $fechaReserva = Carbon::parse($validated['fecha_reserva']);
        $horaInicio = Carbon::parse($validated['fecha_reserva'] . ' ' . $validated['startTime']);

        Log::info('Ahora: ' . $now);
        Log::info('Hora inicio reserva: ' . $horaInicio);   

        // Validar que no sea más de 7 días de anticipación
        if ($fechaReserva->isAfter($now->copy()->addDays(7))) {
            return response()->json([
                'message' => 'Solo se pueden hacer reservas con hasta una semana de anticipación.'
            ], 422);
        }

        // Validar si es para el mismo día
        if ($fechaReserva->isToday()) {
            Log::info("Hora actual: $now, hora inicio: $horaInicio");
        
            $minAnticipacion = 30;

            $minutosDiferencia = $now->copy()->startOfMinute()->diffInMinutes($horaInicio->copy()->startOfMinute(), false);

            Log::info("Diferencia en minutos: $minutosDiferencia");

            if ($minutosDiferencia < $minAnticipacion) {
                return response()->json([
                    'message' => "Si reservas para hoy, debe ser al menos con $minAnticipacion minutos de anticipación."
                ], 422);
            }
        }
       
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

        // 📂 Guardar archivo si se subió
        $documentoPath = null;
        if ($request->hasFile('documento_evento')) {
            $documentoPath = $request->file('documento_evento')->store('eventos', 'public');
        }

        // Crear la reserva
        $reserva = ReservaEquipo::create([
            'user_id' => $validated['user_id'],
            'fecha_reserva' => Carbon::parse($validated['fecha_reserva'] . ' ' . $validated['startTime']),
            'fecha_entrega' => Carbon::parse($validated['fecha_reserva'] . ' ' . $validated['endTime']),
            'aula' => $validated['aula'],
            'estado' => 'Pendiente',
            'tipo_reserva_id' => $validated['tipo_reserva_id'],
            'documento_evento' => $documentoPath,
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
            'reserva' => [
                ...$reserva->toArray(),
                'documento_url' => $reserva->documento_evento_url,
            ],
            'equipos_actualizados' => $equiposActualizados
        ], 201);
    }



    public function actualizarEstado(Request $request, $id)
    {
        $user = Auth::user();

        $request->validate([
            'estado' => 'required|in:Aprobado,Rechazado,Devuelto,Cancelado',
            'comentario' => 'nullable|string',
        ]);

        $reserva = ReservaEquipo::with(['user.role', 'equipos.tipoEquipo', 'tipoReserva'])->findOrFail($id);

        // ✅ Validaciones si es prestamista
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

        BitacoraHelper::registrarCambioEstadoReserva(
            $id,
            $estadoAnterior,
            $request->estado,
            $reserva->user->first_name . ' ' . $reserva->user->last_name
        );

        $pagina = $this->calcularPaginaReserva($reserva->id);

        try {
           if (strtolower($user->role->nombre) === 'prestamista' && strtolower($request->estado) === 'cancelado') {
                // El prestamista cancela → notificar solo a admins y encargados
                $this->notificarResponsablesPorCancelacionEquipo($user, $reserva, $pagina);
            } else {
                // Admin o encargado cambia estado (incluye cancelar) → notificar al prestamista
                if ($reserva->user) {
                    $reserva->user->notify(new EstadoReservaEquipoNotification($reserva, $reserva->user->id, $pagina));
                    //Mail::to($reserva->user->email)->queue(new EstadoReservaMailable($reserva));
                }
            }

            // Log o envío correo
            Log::info("Enviando correo a prestamista: {$reserva->user->email}");

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

        $reservas->getCollection()->transform(function ($reserva) {
            return [
                ...$reserva->toArray(),
                'documento_url' => $reserva->documento_evento_url,
            ];
        });

        return response()->json($reservas);
    }

    private function calcularPaginaReserva(int $reservaId, int $porPagina = 15): int
    {
        $ids = ReservaEquipo::orderBy('created_at', 'desc')->pluck('id')->toArray();
        $index = array_search($reservaId, $ids);

        return $index === false ? 1 : (int) ceil(($index + 1) / $porPagina);
    }

    function roundUpToNextHalfHour(Carbon $dateTime): Carbon
    {
        $minutes = $dateTime->minute;

        if ($minutes === 0 || $minutes === 30) {
            // Ya está en media hora exacta
            return $dateTime->copy()->second(0);
        }

        // Si minutos están entre 1 y 29, subir a :30
        if ($minutes < 30) {
            return $dateTime->copy()->minute(30)->second(0);
        }

        // Si minutos están entre 31 y 59, subir a la próxima hora exacta
        return $dateTime->copy()->addHour()->minute(0)->second(0);
    }   

    private function notificarResponsablesPorCancelacionEquipo(User $prestamista, ReservaEquipo $reserva, int $pagina)
    {
        $responsables = User::whereHas('role', function ($q) {
            $q->whereIn('nombre', ['administrador', 'encargado']);
        })->where('id', '!=', $prestamista->id)->get();

        foreach ($responsables as $responsable) {
            $responsable->notify(new CancelarReservaEquipoPrestamista($reserva, $responsable->id, $pagina));
            // Enviar correo al responsable
            //Mail::to($responsable->email)->queue(new EstadoReservaMailable($reserva));
        }
    }

}
