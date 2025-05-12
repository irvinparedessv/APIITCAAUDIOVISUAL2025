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

    // Verificar si el usuario existe primero
    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'Credenciales incorrectas'], 401);
    }

    // 🚫 Validación de estado del usuario antes de intentar login
    if ($user->estado == 0 || $user->is_deleted) {
        return response()->json(['message' => 'Este usuario está inactivo o eliminado.'], 403);
    }

    // Solo si está activo, se intenta autenticar
    if (!Auth::attempt($request->only('email', 'password'))) {
        return response()->json(['message' => 'Credenciales incorrectas'], 401);
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
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->role->id,
            'roleName' => $user->role->nombre,
            'image' => $user->image
        ],
    ]);
}


    public function logout(Request $request)
    {
        // Eliminar todos los tokens del usuario
        $request->user()->tokens->each(function ($token) {
            $token->delete();
        });

        return response()->json(['message' => 'Sesión cerrada']);
    }
}
