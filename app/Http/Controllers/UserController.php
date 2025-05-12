<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Listar todos los usuarios con su rol
    public function index()
    {
        $usuarios = User::with('role')->get();
        return response()->json($usuarios);
    }

    // Crear un nuevo usuario
    public function store(StoreUserRequest $request)
    {
        // Si no se envía estado, se asigna 'pendiente' (3) por defecto
        $request->merge([
            'estado' => $request->estado ?? 3,
        ]);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'estado' => 'required|in:0,1,3', // Solo permite 0, 1 o 3
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('user_images', 'public');
        }

        $usuario = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'phone' => $request->phone,
            'address' => $request->address,
            'estado' => $request->estado, // Se asigna el estado recibido
            'image' => $imagePath,
            'is_deleted' => false,
        ]);

        return response()->json($usuario, 201);
    }

    // Mostrar un usuario específico
    public function show(string $id)
    {
        $usuario = User::with('role')->findOrFail($id);
        return response()->json($usuario);
    }

    // Actualizar datos de un usuario
    public function update(UpdateUserRequest $request, string $id)
    {
        $usuario = User::findOrFail($id);

        // Validación de los campos permitidos
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $usuario->id,
            'password' => 'nullable|string|min:6',
            'role_id' => 'sometimes|required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'estado' => ['required', Rule::in([0, 1, 3])], // <-- VALIDACIÓN CORRECTA
        ]);

        // Si se incluye el campo 'estado', actualizar también 'is_deleted'
        if (isset($validated['estado'])) {
            $usuario->estado = $validated['estado'];
            $usuario->is_deleted = $validated['estado'] == 0;
        }

        // Actualizar contraseña si viene en la solicitud
        if (!empty($validated['password'])) {
            $usuario->password = Hash::make($validated['password']);
            unset($validated['password']); // Evita que se vuelva a asignar sin encriptar abajo
        }

        // Actualizar otros campos
        $usuario->fill($validated);
        $usuario->save();

        return response()->json($usuario);
    }


    // Eliminar usuario (solo si está desactivado)
    public function destroy(string $id)
    {
        $usuario = User::findOrFail($id);

        // En lugar de eliminar físicamente, se marca como inactivo y eliminado
        $usuario->estado = 0; // Inactivo
        $usuario->is_deleted = true;
        $usuario->save();

        return response()->json(['message' => 'Usuario desactivado correctamente.']);
    }
}
