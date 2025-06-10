<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    /**
     * Obtener notificaciones activas (no archivadas y no eliminadas)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $notifications = Notification::where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->where('is_archived', false)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mostrar detalle de notificación
     */
    public function show($id, Request $request)
    {
        $user = $request->user();

        $notification = Notification::where('id', $id)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->first();

        if (!$notification) {
            return response()->json(['error' => 'Notificación no encontrada'], 404);
        }

        // Marcar como leída si no lo está
        if (is_null($notification->read_at)) {
            $notification->markAsRead();
            $notification->refresh(); // Para obtener los cambios
        }

        return response()->json([
            'notification' => $notification
        ]);
    }

    /**
     * Archivar notificación (mover al historial)
     */
    public function archive($id, Request $request)
    {
        $user = $request->user();

        $notification = Notification::where('id', $id)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->whereNull('deleted_at')
            ->firstOrFail();

        $notification->update(['is_archived' => true]);

        return response()->json([
            'message' => 'Notificación archivada',
            'notification_id' => $id
        ]);
    }

    /**
     * Limpiar todas las notificaciones activas (archivar todas)
     */
    public function archiveAll(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $count = Notification::where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->where('is_archived', false)
            ->whereNull('deleted_at')
            ->update(['is_archived' => true]);

        return response()->json([
            'message' => "$count notificaciones archivadas",
            'count' => $count
        ]);
    }

    /**
     * Obtener historial de notificaciones archivadas
     */
    public function history(Request $request)
    {
         $user = $request->user();

    if (!$user) {
        return response()->json(['error' => 'No autenticado'], 401);
    }

    $notifications = Notification::where('notifiable_id', $user->id)
        ->where('notifiable_type', get_class($user))
        ->whereNull('deleted_at')
        ->orderByDesc('created_at')
        ->get();

    return response()->json([
        'notifications' => $notifications,
        'count' => $notifications->count()
    ]);
    }

    /**
     * Eliminar notificación (soft delete)
     */
    public function destroy($id, Request $request)
    {
        $user = $request->user();

        $notification = Notification::where('id', $id)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->first();

        if (!$notification) {
            return response()->json([
                'message' => 'La notificación no fue encontrada (ya puede estar eliminada)'
            ], 200);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notificación eliminada'
        ]);
    }


    /**
     * Eliminar todas del historial 
     */
    public function destroyAll(Request $request)
    {
        $user = $request->user();

        Notification::where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->withoutTrashed() // esto asegura que se incluyan solo no eliminadas
            ->delete(); // esto hace soft delete correctamente

        return response()->json([
            'message' => 'Todas las notificaciones han sido eliminadas lógicamente.'
        ]);
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