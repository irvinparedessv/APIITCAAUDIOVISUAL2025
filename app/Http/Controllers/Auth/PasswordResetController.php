<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    // Enviar el enlace al correo
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $token = Password::getRepository()->create($user);
        $user->notify(new ResetPasswordNotification($token));

        return response()->json(['message' => 'Enlace enviado al correo.']);
    }

    // Cambiar la contraseña con el token
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'change_password' => true, // 👈 Marcamos como cambiada
                ])->save();
            }
        );

        return response()->json([
            'message' => __($status),
        ], $status === Password::PASSWORD_RESET ? 200 : 400);
    }
  public function updatePassword(Request $request)
{
    $request->validate([
        'current_password' => 'required',
        'new_password' => 'required|min:8|confirmed',
    ]);

    $user = $request->user(); // funciona con Sanctum o token guard

    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json(['message' => 'La contraseña actual es incorrecta.'], 400);
    }

    $user->password = Hash::make($request->new_password);
    $user->change_password = true;
    $user->save();

    // 🔐 Si estás usando Sanctum, revoca el token actual:
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'message' => 'Contraseña actualizada con éxito. Por favor inicia sesión nuevamente.',
        'logout' => true
    ]);
}
}
