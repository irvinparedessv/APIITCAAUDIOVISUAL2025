<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Listar todos los usuarios
    public function index()
    {
        $usuarios = User::with('role')->get();
        return response()->json($usuarios);
    }

    // Crear un nuevo usuario
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'estado' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('user_images', 'public');
        }

        $usuario = User::create([
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')), // Usando Hash para asegurar la contraseña
            'role_id' => $request->input('role_id'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'estado' => $request->input('estado', true), // Si no se pasa, por defecto será true
            'image' => $imagePath,
            'is_deleted' => false, // Se asegura que el nuevo usuario no esté marcado como eliminado
        ]);

        return response()->json($usuario, 201);
    }

    // Mostrar un solo usuario
    public function show(string $id)
    {
        $usuario = User::with('role')->findOrFail($id);
        return response()->json($usuario);
    }

    // Actualizar un usuario (activar/desactivar)
    public function update(Request $request, string $id)
    {
        $usuario = User::findOrFail($id);

        $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $usuario->id,
            'password' => 'nullable|string|min:6',
            'role_id' => 'sometimes|required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'estado' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Si se actualiza el estado (activar/desactivar)
        if ($request->has('estado')) {
            $usuario->estado = $request->input('estado');
            $usuario->save();
        }

        // Si se actualiza la imagen
        if ($request->hasFile('image')) {
            // Eliminar imagen anterior si existe
            if ($usuario->image) {
                Storage::disk('public')->delete($usuario->image);
            }

            $usuario->image = $request->file('image')->store('user_images', 'public');
        }

        // Si el usuario está desactivado, lo marcamos como eliminado (eliminación lógica)
        if ($usuario->estado === false && !$usuario->is_deleted) {
            $usuario->is_deleted = true;
            $usuario->save();
        }

        // Actualizar otros campos
        $usuario->update([
            'first_name' => $request->input('first_name', $usuario->first_name),
            'last_name' => $request->input('last_name', $usuario->last_name),
            'email' => $request->input('email', $usuario->email),
            'password' => $request->filled('password') ? Hash::make($request->input('password')) : $usuario->password,
            'role_id' => $request->input('role_id', $usuario->role_id),
            'phone' => $request->input('phone', $usuario->phone),
            'address' => $request->input('address', $usuario->address),
        ]);

        return response()->json($usuario);
    }

    // Eliminar un usuario (solo si está desactivado)
    public function destroy(string $id)
    {
        $usuario = User::findOrFail($id);

        // Verificar si el usuario está desactivado
        if ($usuario->estado) {
            return response()->json([
                'message' => 'El usuario debe estar desactivado (estado = false) antes de ser eliminado.'
            ], 400);
        }

        // Eliminar la imagen si existe
        if ($usuario->image) {
            Storage::disk('public')->delete($usuario->image);
        }

        // Eliminar físicamente de la base de datos
        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente.']);
    }
}
