<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class EquipoController extends Controller
{
    // Listar todos los equipos
    public function index()
    {
        $equipos = Equipo::where('is_deleted', false)->get()->map(function ($equipo) {
            $equipo->imagen_url = $equipo->imagen_url;

            return $equipo;
        });

        return response()->json($equipos);
    }

    public function obtenerEquipos()
    {
        return Equipo::where('is_deleted', false)->select('id', 'nombre')->get();
    }

    // Guardar un nuevo equipo
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'required|boolean',
            'cantidad' => 'required|integer',
            'is_deleted' => 'required|boolean',
            'tipo_equipo_id' => 'required|exists:tipo_equipos,id',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only([
            'nombre', 'descripcion', 'estado', 'cantidad', 'tipo_equipo_id'
        ]);
        
        $data['is_deleted'] = false;
        

        if ($request->hasFile('imagen')) {
            $image = $request->file('imagen');
            
            // Registra información sobre la imagen para depuración
            Log::info("Nombre del archivo: " . $image->getClientOriginalName());
            Log::info("Extensión del archivo: " . $image->getClientOriginalExtension());
            
            // Genera un nombre aleatorio para la imagen
            $imageName = Str::random(20) . '.' . $image->getClientOriginalExtension();
            
            // Guarda la imagen en el disco 'public' en la carpeta 'equipos'
            $image->storeAs('equipos', $imageName, 'public');
            
            // Guarda el nombre de la imagen en la base de datos
            $data['imagen'] = $imageName;
        } else {
            Log::info("No se ha subido una imagen.");
            $data['imagen'] = 'default.png';
        }
        

        $equipo = Equipo::create($data);

        return response()->json($equipo, 201);
    }

    // Mostrar un solo equipo
    public function show(string $id)
    {
        $equipo = Equipo::findOrFail($id);

        $equipo->imagen_url = $equipo->imagen_url;

        return response()->json($equipo);
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
            'is_deleted' => 'sometimes|required|boolean', 
            'tipo_equipo_id' => 'sometimes|required|exists:tipo_equipos,id',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only([
            'nombre', 'descripcion', 'estado', 'cantidad', 'tipo_equipo_id'
        ]);

        
        if ($request->has('is_deleted')) {
            $data['is_deleted'] = $request->input('is_deleted');
        }

        if ($request->hasFile('imagen')) {
            // Eliminar imagen anterior si no es la default
            if ($equipo->imagen && $equipo->imagen !== 'default.png') {
                Storage::disk('public')->delete('equipos/' . $equipo->imagen);
            }

            $image = $request->file('imagen');
            
            // Registra información sobre la imagen para depuración
            Log::info("Nombre del archivo: " . $image->getClientOriginalName());
            Log::info("Extensión del archivo: " . $image->getClientOriginalExtension());
            
            // Genera un nombre aleatorio para la imagen
            $imageName = Str::random(20) . '.' . $image->getClientOriginalExtension();
            
            // Guarda la imagen en el disco 'public' en la carpeta 'equipos'
            $image->storeAs('equipos', $imageName, 'public');
            
            // Guarda el nombre de la imagen en la base de datos
            $data['imagen'] = $imageName;
        }

        // Actualizar los datos del equipo
        $equipo->update($data);

        return response()->json($equipo);
    }

    

    // Borrado lógico
    public function destroy(string $id)
    {
        $equipo = Equipo::findOrFail($id);
           // Actualiza el campo is_deleted a true
        $equipo->is_deleted = true;
        $equipo->save();

        return response()->json(['message' => 'Equipo eliminado lógicamente.']);
    }
}
