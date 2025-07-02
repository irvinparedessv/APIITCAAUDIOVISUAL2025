<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Aula;
use App\Models\User;
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
            'render_images_is360.*' => 'nullable|boolean',
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
                foreach ($request->file('render_images') as $index => $file) {
                    // Guarda en storage/app/public/render_images
                    $path = $file->store('render_images', 'public');
                    $is360 = $request->input("render_images_is360.$index") ? true : false;
                    ImagenesAula::create([
                        'aula_id' => $aula->id,
                        'image_path' => 'storage/' . $path, // Ruta accesible públicamente
                        'is360' => $is360,
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
            'available_times' => 'nullable|string', // Viene como JSON string porque usas FormData
            'render_images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:14000',
            'render_images_is360.*' => 'nullable|boolean',
        ]);

        // Parsear available_times JSON manualmente
        $availableTimes = [];
        if ($request->filled('available_times')) {
            $availableTimes = json_decode($request->available_times, true);

            if (!is_array($availableTimes)) {
                return response()->json([
                    'message' => 'El campo available_times debe ser un array válido.'
                ], 422);
            }

            // Validar cada objeto
            foreach ($availableTimes as $time) {
                if (
                    empty($time['start_date']) ||
                    empty($time['end_date']) ||
                    empty($time['start_time']) ||
                    empty($time['end_time']) ||
                    empty($time['days']) || !is_array($time['days'])
                ) {
                    return response()->json([
                        'message' => 'Cada horario debe tener start_date, end_date, start_time, end_time y days.'
                    ], 422);
                }
            }
        }

        // Buscar aula
        $aula = Aula::findOrFail($id);
        $aula->name = $request->name;
        $aula->save();

        // Actualizar horarios: eliminar todos y recrear
        $aula->horarios()->delete();

        foreach ($availableTimes as $time) {
            $aula->horarios()->create([
                'start_date' => $time['start_date'],
                'end_date' => $time['end_date'],
                'start_time' => $time['start_time'],
                'end_time' => $time['end_time'],
                'days' => json_encode($time['days']),
            ]);
        }
        foreach ($aula->imagenes as $img) {
            $path = str_replace('/storage/', '', $img->image_path);
            Storage::disk('public')->delete($path);
            $img->delete();
        }

        // Reemplazar imágenes si vienen nuevas
        if ($request->hasFile('render_images')) {
            // Eliminar imágenes previas de disco y DB

            // Guardar nuevas
            foreach ($request->file('render_images') as $index => $img) {

                $path = $img->store('render_images', 'public');
                $is360 = $request->input("render_images_is360.$index") ? true : false;
                ImagenesAula::create([
                    'aula_id' => $aula->id,
                    'image_path' => 'storage/' . $path, // Ruta accesible públicamente
                    'is360' => $is360,
                ]);
                $aula->imagenes()->create([
                    'image_path' => Storage::url($path),
                ]);
            }
        }

        return response()->json(['message' => 'Aula actualizada correctamente.']);
    }



    public function list(Request $request)
    {
        // Incluye encargados con relación hasMany o belongsToMany según tu modelo
        $query = Aula::withCount('imagenes')
            ->with('encargados'); // 👈 Incluye encargados

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%$search%");
        }

        $perPage = $request->input('perPage', 5);
        $aulas = $query->paginate($perPage);

        $aulasTransformadas = $aulas->getCollection()->map(function ($aula) {
            return [
                'id' => $aula->id,
                'name' => $aula->name,
                'count_images' => $aula->imagenes_count,
                'has_images' => $aula->imagenes_count > 0,
                'encargados' => $aula->encargados->map(function ($encargado) {
                    return [
                        'id' => $encargado->id,
                        'first_name' => $encargado->first_name,
                        'last_name' => $encargado->last_name,
                    ];
                }),
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
    public function asignarEncargados(Request $request, Aula $aula)
    {
        $request->validate([
            'user_ids' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    $invalidUsers = User::whereIn('id', $value)
                        ->whereHas('role', fn($q) => $q->where('nombre', '!=', 'EspacioEncargado'))
                        ->pluck('id')
                        ->toArray();

                    if (!empty($invalidUsers)) {
                        $fail('Solo usuarios con rol EspacioEncargado pueden ser asignados. IDs inválidos: ' . implode(', ', $invalidUsers));
                    }
                },
            ],
        ]);

        $aula->encargados()->sync($request->user_ids);

        return response()->json(['message' => 'Encargados asignados correctamente']);
    }
    public function encargados($id)
    {
        $aula = Aula::with('encargados')->findOrFail($id);
        return response()->json($aula);
    }
}
