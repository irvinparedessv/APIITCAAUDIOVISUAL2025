<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        $user = User::where('email', $request->email)->first();

        // 🚫 Validación del estado del usuario
        if ($user->estado == 0 || $user->is_deleted) {
            return response()->json(['message' => 'Este usuario está inactivo o eliminado.'], 403);
        }

        // Eliminar todos los tokens anteriores del usuario
        $user->tokens->each(function ($token) {
            $token->delete();
        });

        // Crear nuevo token
        $token = $user->createToken('api-token')->plainTextToken;

        $user->load('role');

        return response()->json([
            'token' => $token,
            'user' => [
                'id'    => $user->id,
                'first_name'  => $user->first_name,
                'last_name'  => $user->last_name,
                'email' => $user->email,
                'role'  => $user->role->id, // Aquí aseguramos enviar el nombre del rol
                'roleName'  => $user->role->nombre, // Aquí aseguramos enviar el nombre del rol
                'image' => $user->image, // Asegúrate de que la imagen esté disponible
                'estado' => $user->estado,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        // Eliminar todos los tokens del usuario
        $request->user()->tokens()->delete();


        return response()->json(['message' => 'Sesión cerrada']);
    }
}
