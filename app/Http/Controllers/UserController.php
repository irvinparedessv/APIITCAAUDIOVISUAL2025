<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Listar todos los usuarios
    public function index()
    {
        $usuarios = User::with('role')->get(); // Trae usuarios con su rol
        return response()->json($usuarios);
    }

    // Guardar un nuevo usuario
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'estado' => 'nullable|boolean', // o string 
        ]);

        $usuario = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
            'role_id' => $request->input('role_id'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'estado' => $request->input('estado', true), // por defecto se puede usar true
        ]);

        return response()->json($usuario, 201);
    }

    // Mostrar un solo usuario
    public function show(string $id)
    {
        $usuario = User::with('role')->findOrFail($id);
        return response()->json($usuario);
    }

    // Actualizar un usuario
    public function update(Request $request, string $id)
    {
        $usuario = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $usuario->id,
            'password' => 'nullable|string|min:6',
            'role_id' => 'sometimes|required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'estado' => 'nullable|boolean', 
        ]);

        $usuario->update([
            'name' => $request->input('name', $usuario->name),
            'email' => $request->input('email', $usuario->email),
            'password' => $request->filled('password') ? bcrypt($request->input('password')) : $usuario->password,
            'role_id' => $request->input('role_id', $usuario->role_id),
            'phone' => $request->input('phone', $usuario->phone),
            'address' => $request->input('address', $usuario->address),
            'estado' => $request->input('estado', $usuario->estado),
        ]);

        return response()->json($usuario);
    }

    // Eliminar un usuario
    public function destroy(string $id)
    {
        $usuario = User::findOrFail($id);
        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente.']);
    }
}
