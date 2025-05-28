<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

    if (!$user) {
        return response()->json([
            'error' => 'No autenticado',
        ], 401);
    }

    return response()->json([
        'notifications' => $user->notifications,
    ]);
    }

    public function show($id, Request $request)
    {
        $user = $request->user();

        $notification = $user->notifications()->findOrFail($id);

        // Marcar como leída
        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json([
            'notification' => $notification,
        ]);
    }
}
