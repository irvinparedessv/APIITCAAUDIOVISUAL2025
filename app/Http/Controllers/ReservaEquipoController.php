<?php

namespace App\Http\Controllers;

use App\Helpers\BitacoraHelper;
use App\Mail\EstadoReservaMailable;
use App\Models\Bitacora;
use App\Models\CodigoQrReserva;
use App\Models\CodigoQrReservaEquipo;
use App\Models\EquipmentReservation;
use App\Models\ReservaEquipo;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ConfirmarReservaUsuario;
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
        $userId = $request->query('user_id');

        $query = ReservaEquipo::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $reservas = $query->with(['user', 'equipos', 'codigoQr', 'tipoReserva'])->get();

        return response()->json($reservas);
    }
    public function getByUser($id)
    {
        // Buscar todas las reservas de ese usuario
        $reservas = ReservaEquipo::where('user_id', $id)
            ->with(['user', 'equipos', 'codigoQr', 'tipoReserva']) // Relación con user, equipos, y codigo qr
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
            'tipo_reserva_id' => 'required|exists:tipo_reservas,id',
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
            'tipo_reserva_id' => $validated['tipo_reserva_id'],
        ]);

        // Asociar equipos
        $reserva->equipos()->attach($validated['equipo']);
        CodigoQrReservaEquipo::create([
            'id' => (string) Str::uuid(), // generas un nuevo UUID para el QR
            'reserva_id' => $reserva->id, // vinculamos a la reserva

        ]);

        // ✅ CARGA las relaciones necesarias antes de notificar
        $reserva->load(['user', 'equipos', 'aula', 'tipoReserva']); // Asegúrate de tener definida la relación 'aula' en el modelo

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
        $responsable->notify(new NuevaReservaNotification($reserva, $responsable->id));
        Log::info("Notificación enviada");
        // Enviar correo personalizado
        //$responsable->notify(new NotificarResponsableReserva($reserva));
    }
        // Notificación por correo al usuario
       //$reserva->user->notify(new ConfirmarReservaUsuario($reserva));

        return response()->json([
                'message' => 'Reserva creada exitosamente',
                'reserva' => $reserva->load('equipos'),
            ], 201);
    }



    public function actualizarEstado(Request $request, $id)
{
    $request->validate([
        'estado' => 'required|in:approved,rejected,returned',
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
        $reserva->user->first_name.' '.$reserva->user->last_name
    );

    try {
        if (strtolower($reserva->user->role->nombre) === 'prestamista') {
            Log::info('Notificando al prestamista...', [
                'user_id' => $reserva->user->id,
                'reserva_id' => $reserva->id
            ]);
            
            $reserva->user->notify(new EstadoReservaNotification($reserva, $reserva->user->id));
        }

        Log::info("Enviando correo a prestamista: {$reserva->user->email}");
        // Descomenta cuando esté listo el Mailable
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
    // Agrega estos métodos al controlador

    public function getNotificaciones(Request $request)
    {
        $user = $request->user();
        $notificaciones = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                ];

                Log::info('Notificaciones enviadas al frontend:', [
        'count' => $notifications->count(),
        'sample_data' => $notifications->first()?->data
    ]);
            });

            

        return response()->json($notificaciones);
    }

    public function marcarComoLeidas(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }

    public function marcarComoLeida(Request $request, $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        
        if ($notification) {
            $notification->markAsRead();
            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false, 'message' => 'Notificación no encontrada'], 404);
    }

}
