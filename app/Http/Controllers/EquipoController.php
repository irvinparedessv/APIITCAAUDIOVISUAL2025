<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use Illuminate\Http\Request;

class EquipoController extends Controller
{
    // Listar todos los equipos
    public function index()
    {
        $equipos = Equipo::all();
        return response()->json($equipos);
    }

    // Mostrar el formulario para crear (solo si usas Blade, si es API puedes ignorar)
    public function create()
    {
        // Si es API, probablemente no necesitas esta función.
    }

    // Guardar un nuevo equipo
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'required|boolean',
            'cantidad' => 'required|integer',
            'is_deleted' => 'required|boolean', // Validar el campo is_deleted
            'tipo_equipo_id' => 'required|exists:tipo_equipos,id', // Validar tipo_equipo_id (debe existir en la tabla tipo_equipos)
        ]);

        // Crear el nuevo equipo
        $equipo = Equipo::create([
            'nombre' => $request->input('nombre'),
            'descripcion' => $request->input('descripcion'),
            'estado' => $request->input('estado'),
            'cantidad' => $request->input('cantidad'),
            'is_deleted' => $request->input('is_deleted'),
            'tipo_equipo_id' => $request->input('tipo_equipo_id'),
        ]);

        return response()->json($equipo, 201);
    }

    // Mostrar un solo equipo
    public function show(string $id)
    {
        $equipo = Equipo::findOrFail($id);
        return response()->json($equipo);
    }

    // Mostrar el formulario para editar (solo si usas Blade)
    public function edit(string $id)
    {
        // Si es API, probablemente no necesitas esta función.
    }

    // Actualizar equipo
    public function update(Request $request, string $id)
    {
        $equipo = Equipo::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'sometimes|required|boolean',
            'cantidad' => 'sometimes|required|integer',
            'is_deleted' => 'sometimes|required|boolean', // Validar el campo is_deleted
            'tipo_equipo_id' => 'sometimes|required|exists:tipo_equipos,id', // Validar tipo_equipo_id (debe existir en la tabla tipo_equipos)
        ]);

        $equipo->update($request->only([
            'nombre', 
            'descripcion', 
            'estado', 
            'cantidad', 
            'is_deleted', 
            'tipo_equipo_id'
        ]));

        return response()->json($equipo);
    }

    // Eliminar equipo
    public function destroy(string $id)
    {
        $equipo = Equipo::findOrFail($id);
        $equipo->delete();

        return response()->json(['message' => 'Equipo eliminado correctamente.']);
    }
}
