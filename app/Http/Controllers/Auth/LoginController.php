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

    // Eliminar todos los tokens anteriores del usuario
    $user->tokens->each(function ($token) {
        $token->delete();
    });

    // Crear un nuevo token
    $token = $user->createToken('api-token')->plainTextToken;

    // Cargar relación 'role' (esto es lo nuevo)
    $user->load('role');

    return response()->json([
        'token' => $token,
        'user' => [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role->id, // Aquí aseguramos enviar el nombre del rol
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

