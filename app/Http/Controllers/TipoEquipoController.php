<?php

namespace App\Http\Controllers;

use App\Models\TipoEquipo;
use Illuminate\Http\Request;

class TipoEquipoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Obtener todos los tipos de equipo
        $tiposEquipos = TipoEquipo::all();
        return response()->json($tiposEquipos);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Este método puede ser útil si estás usando vistas y deseas mostrar un formulario de creación
        // Pero en una API generalmente no se necesita este método
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validar los datos recibidos
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        // Crear un nuevo tipo de equipo
        $tipoEquipo = TipoEquipo::create([
            'nombre' => $request->input('nombre'),
        ]);

        // Retornar el tipo de equipo creado
        return response()->json($tipoEquipo, 201); // Código HTTP 201 indica que se creó con éxito
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Buscar el tipo de equipo por ID
        $tipoEquipo = TipoEquipo::find($id);

        // Si no se encuentra, retornar un error
        if (!$tipoEquipo) {
            return response()->json(['error' => 'Tipo de equipo no encontrado'], 404);
        }

        // Retornar el tipo de equipo encontrado
        return response()->json($tipoEquipo);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Similar al método `create()`, este método puede ser útil si estás usando vistas y deseas mostrar un formulario de edición.
        // En una API, no se necesita este método.
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validar los datos recibidos
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        // Buscar el tipo de equipo por ID
        $tipoEquipo = TipoEquipo::find($id);

        // Si no se encuentra, retornar un error
        if (!$tipoEquipo) {
            return response()->json(['error' => 'Tipo de equipo no encontrado'], 404);
        }

        // Actualizar el tipo de equipo
        $tipoEquipo->nombre = $request->input('nombre');
        $tipoEquipo->save();

        // Retornar el tipo de equipo actualizado
        return response()->json($tipoEquipo);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Buscar el tipo de equipo por ID
        $tipoEquipo = TipoEquipo::find($id);

        // Si no se encuentra, retornar un error
        if (!$tipoEquipo) {
            return response()->json(['error' => 'Tipo de equipo no encontrado'], 404);
        }

        // Eliminar el tipo de equipo
        $tipoEquipo->delete();

        // Retornar una respuesta exitosa
        return response()->json(['message' => 'Tipo de equipo eliminado con éxito'], 200);
    }
}
