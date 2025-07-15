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
            'name' => 'required|string|max:255|unique:aulas,name',
            'render_images.*' => 'nullable|file',
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
                    $is360 = filter_var($request->input("render_images_is360.$index"), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if (is_null($is360)) {
                        $is360 = false;
                    }
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
                    empty($time['days']) || !is_array($time['days'])
                ) {
                    return response()->json([
                        'message' => 'Cada horario debe tener start_date, end_date, y days.'
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
            }
        }

        return response()->json(['message' => 'Aula actualizada correctamente.']);
    }



    public function list(Request $request)
    {
        // Incluye encargados con relación hasMany o belongsToMany según tu modelo
        $query = Aula::withCount('imagenes')
            ->with('encargados')->where('deleted', false);
        // 👈 Incluye encargados

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhereHas('encargados', function ($subquery) use ($search) {
                        $subquery->where('first_name', 'like', "%$search%")
                            ->orWhere('last_name', 'like', "%$search%");
                    });
            });
        }


        if ($request->has('has_images')) {
            $value = filter_var($request->input('has_images'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value !== null) {
                $query->has('imagenes', $value ? '>' : '=', 0);
            }
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


    public function disponibilidad(Request $request, Aula $aula)
    {
        $rangos = $aula->horarios;

        $resultados = [];

        $filterStart = $request->query('startDate');
        $filterEnd = $request->query('endDate');

        foreach ($rangos as $rango) {
            $days = $rango->days ?? [];
            if (is_string($days)) {
                $days = json_decode($days, true);
            }

            $start_date = max($rango->start_date, $filterStart);
            $end_date = min($rango->end_date, $filterEnd);

            if ($start_date > $end_date) {
                continue; // no hay intersección
            }

            $periodo = new \DatePeriod(
                new \DateTime($start_date),
                new \DateInterval('P1D'),
                (new \DateTime($end_date))->modify('+1 day')
            );

            $diasValidos = [];
            foreach ($periodo as $date) {
                if (in_array($date->format('l'), $days)) {
                    $diasValidos[] = $date->format('Y-m-d');
                }
            }

            $bloquesTotales = count($diasValidos);

            // Reservas aprobadas con detalle orden descendente
            $reservasAprobadas = \App\Models\ReservaAula::where('aula_id', $aula->id)
                ->whereBetween('fecha', [$start_date, $end_date])
                ->where('estado', 'Aprobado')
                ->orderBy('fecha', 'desc')
                ->orderBy('horario', 'desc')
                ->get(['fecha', 'horario', 'estado']);

            // Reservas pendientes con detalle orden descendente
            $reservasPendientes = \App\Models\ReservaAula::where('aula_id', $aula->id)
                ->whereBetween('fecha', [$start_date, $end_date])
                ->where('estado', 'Pendiente')
                ->orderBy('fecha', 'desc')
                ->orderBy('horario', 'desc')
                ->get(['fecha', 'horario', 'estado']);

            $resultados[] = [
                'rango' => [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'days' => $days,
                ],
                'bloques_totales' => $bloquesTotales,
                'cupos_ocupados' => $reservasAprobadas->count(),
                'cupos_pendientes' => $reservasPendientes->count(),
                'cupos_libres' => max($bloquesTotales - $reservasAprobadas->count(), 0),
                'reservas_aprobadas' => $reservasAprobadas,
                'reservas_pendientes' => $reservasPendientes,
            ];
        }

        return response()->json(['resultados' => $resultados]);
    }


    public function destroy($id)
    {
        $aula = Aula::findOrFail($id);
        $aula->deleted = true;
        $aula->save();
        $aula->reservas()
            ->where(function ($query) {
                $query->where('estado', 'Pendiente')
                    ->orWhere(function ($query) {
                        $query->where('estado', 'Aprobada')
                            ->where('fecha', '>', now());
                    });
            })
            ->update(['estado' => 'Cancelada']);
        return response()->json(['message' => 'Aula marcada como eliminada']);
    }

    public function getaulas(Request $request)
    {
        $query = Aula::with('primeraImagen')->where('deleted', false);

        if ($request->has('buscar') && $request->buscar) {
            $query->where('name', 'LIKE', '%' . $request->buscar . '%');
        }

        $aulas = $query->paginate(10);

        $result = $aulas->map(function ($aula) {
            return [
                'id' => $aula->id,
                'nombre' => $aula->name,
                'capacidad' => 0, // pon la capacidad si tienes un campo, si no deja fijo o agrégalo después
            ];
        });

        return response()->json([
            'data' => $result,
            'last_page' => $aulas->lastPage(),
        ]);
    }






    public function index(): JsonResponse
    {
        $aulas = Aula::with(['primeraImagen'])
            ->where('deleted', false)
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
