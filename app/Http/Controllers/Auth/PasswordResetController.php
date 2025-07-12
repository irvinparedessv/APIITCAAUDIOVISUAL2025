<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    // Enviar el enlace al correo
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = \App\Models\User::where('email', $request->email)
            ->where('estado', 1)
            ->first();

        if ($user) {
            // Verificar si ya se envió un token recientemente
            $recent = DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->where('created_at', '>', Carbon::now()->subMinutes(15)) // por ejemplo: 15 minutos
                ->exists();
            if ($recent) {
                return response()->json([
                    'message' => 'Ya se ha enviado un enlace recientemente. Por favor, espera unos minutos antes de intentarlo de nuevo.'
                ], 429); // 429 Too Many Requests
            }

            if (!$recent) {
                $token = Password::getRepository()->create($user);
                $user->notify(new ResetPasswordNotification($token));
            }
        }

        return response()->json([
            'message' => 'Si el correo está registrado, se ha enviado un enlace para restablecer la contraseña.'
        ]);
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
                try {
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'change_password' => true,
                    ])->save();
                } catch (\Exception $e) {
                }
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            Log::info('Token eliminado exitosamente después del restablecimiento.', [
                'email' => $request->email,
                'hora' => now()
            ]);
        } else {
            Log::warning('Falló el restablecimiento de contraseña.', [
                'email' => $request->email,
                'status' => $status,
                'hora' => now()
            ]);
        }

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
