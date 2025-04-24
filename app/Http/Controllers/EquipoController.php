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
        ]);

        $equipo = Equipo::create($request->all());
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
        ]);

        $equipo->update($request->all());
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
