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
}
