<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    // Mostrar datos del usuario autenticado (con su rol)
    public function show()
    {
        $user = Auth::user()->load('role'); // Cargar también la relación con Role

        return response()->json([
            'user' => $user,
        ]);
    }

    // Actualizar perfil del usuario autenticado
    public function update(Request $request)
    {
        $user = Auth::user();

        // Validar solo los campos que sí se pueden actualizar
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Manejar imagen si se sube una nueva
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $path = $image->store('user_images', 'public');
            $user->image = $path;
        }

        // Asignar campos manualmente para asegurar persistencia
        $user->first_name = $request->input('first_name', $user->first_name);
        $user->last_name = $request->input('last_name', $user->last_name);
        $user->phone = $request->input('phone', $user->phone);
        $user->address = $request->input('address', $user->address);

        $user->save();
        $user->refresh(); // Asegura que incluya el accesor actualizado

        return response()->json([
            'message' => 'Perfil actualizado con éxito',
            'user' => $user
        ]);
    }
}
