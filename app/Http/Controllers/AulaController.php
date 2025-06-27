<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Aula;
use App\Models\ImagenesAula;
use App\Models\HorarioAulas;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class AulaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'render_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'available_times' => 'required|json',
        ]);

        DB::beginTransaction();

        try {
            // 1. Crear aula
            $aula = Aula::create([
                'name' => $request->input('name'),
            ]);

            // 2. Guardar imágenes
            if ($request->hasFile('render_images')) {
                foreach ($request->file('render_images') as $file) {
                    // Guarda en storage/app/public/render_images
                    $path = $file->store('render_images', 'public');

                    ImagenesAula::create([
                        'aula_id' => $aula->id,
                        'image_path' => 'storage/' . $path, // Ruta accesible públicamente
                    ]);
                }
            }

            // 3. Guardar horarios
            $availableTimes = json_decode($request->input('available_times'), true);

            foreach ($availableTimes as $time) {
                HorarioAulas::create([
                    'aula_id' => $aula->id,
                    'start_date' => $time['start_date'],
                    'end_date' => $time['end_date'],
                    'start_time' => $time['start_time'],
                    'end_time' => $time['end_time'],
                    'days' => json_encode($time['days']),
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Aula creada correctamente'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al crear el aula', 'details' => $e->getMessage()], 500);
        }
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'day' => 'nullable|string|max:255',
            'startTime' => 'nullable|string',
            'endTime' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $aula = Aula::findOrFail($id);
        $aula->name = $request->name;
        $aula->save();

        // Actualizar horario
        if ($request->filled(['day', 'startTime', 'endTime'])) {
            // Puedes eliminar los existentes si es un horario único
            $aula->horarioPersonalizado()->delete();

            $aula->horarioPersonalizado()->create([
                'dia' => $request->day,
                'hora_inicio' => $request->startTime,
                'hora_fin' => $request->endTime,
            ]);
        }

        // Guardar nuevas imágenes
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $path = $img->store('render_images', 'public');

                $aula->imagenes()->create([
                    'image_path' => Storage::url($path),
                ]);
            }
        }

        return response()->json(['message' => 'Aula actualizada correctamente.']);
    }

    public function list(Request $request)
    {
        // Traemos solo el conteo de imágenes, no las imágenes en sí
        $query = Aula::withCount('imagenes');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%$search%");
        }

        $perPage = $request->input('perPage', 5);
        $aulas = $query->paginate($perPage);

        // Convertimos los items para agregar una propiedad booleana si tiene imágenes
        $aulasTransformadas = $aulas->getCollection()->map(function ($aula) {
            return [
                'id' => $aula->id,
                'name' => $aula->name,
                // otras propiedades que necesitas devolver...
                'count_images' => $aula->imagenes_count,
                'has_images' => $aula->imagenes_count > 0,
            ];
        });

        return response()->json([
            'data' => $aulasTransformadas,
            'total' => $aulas->total(),
            'last_page' => $aulas->lastPage(),
        ]);
    }
    public function destroy($id)
    {
        $aula = Aula::findOrFail($id);
        $aula->delete();
        return response()->json(['message' => 'Aula eliminada']);
    }

    public function index(): JsonResponse
    {
        $aulas = Aula::with(['primeraImagen'])
            ->select('id', 'name')
            ->get()
            ->map(function ($aula) {
                return [
                    'id' => $aula->id,
                    'name' => $aula->name,
                    'image_path' => $aula->primeraImagen
                        ? url($aula->primeraImagen->image_path)
                        : null,
                ];
            });

        return response()->json($aulas);
    }
    public function show($id)
    {
        $aula = Aula::with(['imagenes', 'horarios'])->findOrFail($id);

        return response()->json($aula);
    }
}
