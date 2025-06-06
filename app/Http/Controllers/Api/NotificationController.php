<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
   public function index(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json(['error' => 'No autenticado'], 401);
    }

    $notifications = Notification::where('notifiable_id', $user->id)
        ->where('notifiable_type', get_class($user))
        ->whereNull('deleted_at') // <- EXCLUYE soft deleted
        ->orderByDesc('created_at')
        ->get();

    return response()->json([
        'notifications' => $notifications,
    ]);
}

    public function show($id, Request $request)
    {
        $user = $request->user();

        $notification = Notification::where('id', $id)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->whereNull('deleted_at') // Importante: excluir eliminadas
            ->firstOrFail();

        // Marcar como leída
        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json([
            'notification' => $notification,
        ]);
    }

    public function destroy($id, Request $request)
    {
        $user = $request->user();

       $notification = Notification::where('id', $id)
        ->where('notifiable_id', $user->id)
        ->where('notifiable_type', get_class($user))
        ->firstOrFail();


        $notification->delete(); // Ahora sí aplicará soft delete

        return response()->json([
            'message' => 'Notificación eliminada correctamente.',
        ]);
    }

    public function clearAll(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        Notification::where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->whereNull('deleted_at') // solo las que aún no han sido eliminadas
            ->delete(); // esto hará soft delete si el modelo lo usa

        return response()->json([
            'message' => 'Todas las notificaciones han sido eliminadas correctamente.',
        ]);
    }

    public function trashed(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::onlyTrashed()
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->orderByDesc('deleted_at')
            ->get();

        return response()->json(['notifications' => $notifications]);
    }


}
