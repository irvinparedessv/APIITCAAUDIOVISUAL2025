<?php


// app/Http/Controllers/EquipoController.php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Caracteristica;
use App\Models\Equipo;
use App\Models\EquipoReserva;
use App\Models\Estado;
use App\Models\Modelo;
use App\Models\ReservaEquipo;
use App\Models\TipoEquipo;
use App\Models\TipoReserva;
use App\Models\ValoresCaracteristica;
use App\Models\VistaEquipo;
use App\Models\VistaResumenEquipo;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EquipoController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);

        $query = Equipo::with([
            'tipoEquipo',
            'modelo.marca',
            'modelo',
            'estado',
            'tipoReserva',
            'valoresCaracteristicas.caracteristica',
        ])->where('is_deleted', false);

        if ($request->filled('tipo')) {
            if ($request->input('tipo') === 'equipo') {
                $query->whereNotNull('numero_serie');
            } elseif ($request->input('tipo') === 'insumo') {
                $query->whereNotNull('cantidad');
            }
        }

        $equipos = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Aquí reasignas la colección transformada
        $equipos->setCollection(
            $equipos->getCollection()->transform(function ($item) {
                $tipo = $item->numero_serie ? 'equipo' : 'insumo';

                return [
                    'id' => $item->id,
                    'tipo' => $tipo,
                    'numero_serie' => $item->numero_serie,
                    'serie_asociada' => $tipo === 'insumo' ? $item->serie_asociada : null,
                    'vida_util' => $item->vida_util,
                    'cantidad' => 1,
                    'detalles' => $item->detalles,
                    'tipo_equipo_id' => $item->tipo_equipo_id,
                    'modelo_id' => $item->modelo_id,
                    'estado_id' => $item->estado_id,
                    'tipo_reserva_id' => $item->tipo_reserva_id,
                    'fecha_adquisicion' => $item->fecha_adquisicion,
                    'imagen_url' => $item->imagen_normal
                        ? asset('storage/equipos/' . $item->imagen_normal)
                        : asset('storage/equipos/default.png'),
                    'marca' => $item->modelo->marca->nombre ?? null,
                    'tipoEquipo' => $item->tipoEquipo,
                    'modelo' => $item->modelo,
                    'estado' => $item->estado,
                    'tipoReserva' => $item->tipoReserva,
                    'caracteristicas' => $item->valoresCaracteristicas->map(function ($vc) {
                        return [
                            'id' => $vc->id,
                            'caracteristica_id' => $vc->caracteristica_id,
                            'nombre' => $vc->caracteristica->nombre ?? null,
                            'valor' => $vc->valor,
                        ];
                    }),
                ];
            })
        );

        return response()->json($equipos);
    }




    public function store(Request $request)
    {
        // Detectar tipo (puede venir como query param o form field)
        $tipo = $request->input('tipo', 'equipo'); // default a 'equipo'

        // Validación base con manejo explícito
        $rules = [
            'tipo_equipo_id' => 'required|exists:tipo_equipos,id',
            'modelo_id' => 'required|exists:modelos,id',
            'estado_id' => 'required|exists:estados,id',
            'detalles' => 'required|string',
            'imagen' => 'nullable|image|max:5120',
            'caracteristicas' => 'nullable|json',
            'cantidad' => $tipo === 'insumo' ? 'required|integer|min:1' : 'prohibited',
            'tipo_reserva_id' => 'required|exists:tipo_reservas,id',
            'fecha_adquisicion' => 'required|date',
        ];

        // Solo validar número_serie y reposo si es equipo
        if ($tipo === 'equipo') {
            $rules['numero_serie'] = 'required|string|unique:equipos,numero_serie';
            $rules['vida_util'] = 'nullable|integer|min:1';
            $rules['reposo'] = 'nullable|integer|min:0'; // Nuevo campo
        } else {
            $rules['reposo'] = 'prohibited'; // No permitir para insumos
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $errors = $validator->errors();

            // Mensaje más descriptivo si el error es por número de serie duplicado
            if ($errors->has('numero_serie') && str_contains($errors->first('numero_serie'), 'unique')) {
                return response()->json([
                    'message' => 'El número de serie ya existe en el sistema',
                    'errors' => $errors,
                ], 422);
            }

            // Otras validaciones
            return response()->json([
                'message' => 'Hay errores en el formulario',
                'errors' => $errors
            ], 422);
        }

        try {
            // Subida de imagen
            $imagePath = null;
            if ($request->hasFile('imagen')) {
                $imagePath = $request->file('imagen')->store('equipos', 'public');
            }

            // Cantidad: 1 por defecto si es equipo, >1 si es insumo
            $cantidad = $tipo === 'insumo' ? (int) $request->input('cantidad', 1) : 1;

            $createdEquipos = [];

            for ($i = 0; $i < $cantidad; $i++) {
                $equipoData = [
                    'tipo_equipo_id' => $request->tipo_equipo_id,
                    'modelo_id' => $request->modelo_id,
                    'estado_id' => $request->estado_id,
                    'detalles' => $request->detalles,
                    'imagen_url' => $imagePath,
                    'tipo_reserva_id' => $request->tipo_reserva_id,
                    'fecha_adquisicion' => $request->fecha_adquisicion,
                    'es_componente' => $tipo === 'insumo',
                ];

                if ($tipo === 'equipo') {
                    $equipoData['numero_serie'] = $request->numero_serie;
                    $equipoData['vida_util'] = $request->vida_util;
                    $equipoData['reposo'] = $request->reposo ?? 0; // Nuevo campo con valor por defecto
                }

                $equipo = Equipo::create($equipoData);

                // Características
                if ($request->has('caracteristicas')) {
                    $caracteristicas = json_decode($request->caracteristicas, true);
                    foreach ($caracteristicas as $caracteristica) {
                        $equipo->valoresCaracteristicas()->create([
                            'caracteristica_id' => $caracteristica['caracteristica_id'],
                            'valor' => $caracteristica['valor']
                        ]);
                    }
                }

                $createdEquipos[] = $equipo->load('valoresCaracteristicas');
            }

            return response()->json([
                'message' => 'Ítem(s) creado(s) exitosamente',
                'data' => $cantidad === 1 ? $createdEquipos[0] : $createdEquipos,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el ítem',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'trace' => config('app.debug') ? $e->getTrace() : null
            ], 500);
        }
    }





    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            Log::debug('Datos recibidos en el servidor:', $request->all());

            $equipo = Equipo::with('valoresCaracteristicas.caracteristica')->findOrFail($id);
            $tipo = $equipo->numero_serie ? 'equipo' : 'insumo';

            // Guardar estado original
            $originalData = $equipo->getOriginal();
            $originalCaracteristicas = $equipo->valoresCaracteristicas->mapWithKeys(function ($item) {
                return [$item->caracteristica_id => [
                    'valor' => $item->valor,
                    'nombre' => $item->caracteristica->nombre
                ]];
            })->toArray();

            // Normalizar características
            $caracteristicas = [];
            if ($request->has('caracteristicas')) {
                $raw = $request->input('caracteristicas');

                if (is_string($raw)) {
                    $caracteristicas = json_decode($raw, true) ?? [];
                } elseif (is_array($raw)) {
                    $caracteristicas = $raw;
                } else {
                    foreach ($request->all() as $key => $value) {
                        if (preg_match('/^caracteristicas\[(\d+)]\[caracteristica_id]$/', $key, $matches)) {
                            $index = $matches[1];
                            $caracteristicas[$index]['caracteristica_id'] = $value;
                        }
                        if (preg_match('/^caracteristicas\[(\d+)]\[valor]$/', $key, $matches)) {
                            $index = $matches[1];
                            $caracteristicas[$index]['valor'] = $value;
                        }
                    }
                }

                $request->merge(['caracteristicas' => array_values($caracteristicas)]);
            }

            // Validación
            $rules = [
                'tipo_equipo_id' => 'sometimes|required|exists:tipo_equipos,id',
                'modelo_id' => 'sometimes|required|exists:modelos,id',
                'estado_id' => 'sometimes|required|exists:estados,id',
                'tipo_reserva_id' => 'nullable|exists:tipo_reservas,id',
                'detalles' => 'nullable|string',
                'fecha_adquisicion' => 'nullable|date',
                'caracteristicas' => 'sometimes|array',
                'caracteristicas.*.caracteristica_id' => 'required|exists:caracteristicas,id',
                'caracteristicas.*.valor' => 'required',
            ];

            if ($tipo === 'equipo') {
                $rules['numero_serie'] = 'sometimes|required|string|unique:equipos,numero_serie,' . $id;
                $rules['vida_util'] = 'nullable|integer|min:1';
                $rules['reposo'] = 'nullable|integer|min:0';
            } else {
                $rules['cantidad'] = 'prohibited';
                $rules['reposo'] = 'prohibited';
            }

            $validatedData = $request->validate($rules);

            // Preparar campos para actualización
            $equipoFields = collect($validatedData)->only([
                'tipo_equipo_id',
                'modelo_id',
                'estado_id',
                'tipo_reserva_id',
                'detalles',
                'fecha_adquisicion',
                'numero_serie',
                'vida_util',
                'reposo',
            ])->toArray();

            // Detectar cambios en campos principales
            $cambios = [];
            foreach ($equipoFields as $campo => $nuevoValor) {
                if (array_key_exists($campo, $originalData) && $originalData[$campo] != $nuevoValor) {
                    // Obtener nombres legibles para relaciones
                    if (in_array($campo, ['tipo_equipo_id', 'modelo_id', 'estado_id', 'tipo_reserva_id'])) {
                        $modelo = match ($campo) {
                            'tipo_equipo_id' => TipoEquipo::class,
                            'modelo_id' => Modelo::class,
                            'estado_id' => Estado::class,
                            'tipo_reserva_id' => TipoReserva::class,
                        };

                        $anterior = $originalData[$campo] ? $modelo::find($originalData[$campo])->nombre : 'N/A';
                        $nuevo = $nuevoValor ? $modelo::find($nuevoValor)->nombre : 'N/A';
                    } else {
                        $anterior = $originalData[$campo] ?? 'N/A';
                        $nuevo = $nuevoValor ?? 'N/A';
                    }

                    $cambios[$campo] = [
                        'anterior' => $anterior,
                        'nuevo' => $nuevo
                    ];
                }
            }

            // Actualizar modelo
            $equipo->update($equipoFields);

            // Procesar características
            $caracteristicasCambiadas = [];
            if (!empty($caracteristicas)) {
                $caracteristicasActuales = $equipo->valoresCaracteristicas->keyBy('caracteristica_id');

                foreach ($caracteristicas as $caracteristica) {
                    $caracteristicaId = $caracteristica['caracteristica_id'];
                    $valorNuevo = $caracteristica['valor'];

                    if ($caracteristicasActuales->has($caracteristicaId)) {
                        $valorAnterior = $caracteristicasActuales[$caracteristicaId]->valor;

                        if ($valorAnterior != $valorNuevo) {
                            $caracteristicasActuales[$caracteristicaId]->update(['valor' => $valorNuevo]);

                            $caracteristicasCambiadas[] = [
                                'nombre' => $caracteristicasActuales[$caracteristicaId]->caracteristica->nombre,
                                'anterior' => $valorAnterior,
                                'nuevo' => $valorNuevo
                            ];
                        }
                    } else {
                        $nueva = $equipo->valoresCaracteristicas()->create([
                            'caracteristica_id' => $caracteristicaId,
                            'valor' => $valorNuevo
                        ]);

                        $caracteristicasCambiadas[] = [
                            'nombre' => $nueva->caracteristica->nombre,
                            'anterior' => 'N/A',
                            'nuevo' => $valorNuevo
                        ];
                    }
                }

                $idsNuevos = collect($caracteristicas)->pluck('caracteristica_id')->toArray();
                foreach ($caracteristicasActuales as $id => $caracteristica) {
                    if (!in_array($id, $idsNuevos)) {
                        $nombre = $caracteristica->caracteristica->nombre;
                        $valorAnterior = $caracteristica->valor;
                        $caracteristica->delete();

                        $caracteristicasCambiadas[] = [
                            'nombre' => $nombre,
                            'anterior' => $valorAnterior,
                            'nuevo' => 'Eliminado'
                        ];
                    }
                }
            }

            // Bitácora si hay cambios
            if (!empty($cambios) || !empty($caracteristicasCambiadas)) {
                $user = Auth::user();
                $tipoEquipo = $equipo->numero_serie ? 'Equipo' : 'Insumo';

                $equipo->loadMissing('modelo.marca');

                $nombreEquipo = trim(sprintf(
                    '%s %s',
                    $equipo->modelo->marca->nombre ?? 'Sin marca',
                    $equipo->modelo->nombre ?? 'Desconocido'
                ));

                $descripcion = ($user ? "{$user->first_name} {$user->last_name}" : 'Sistema') .
                    " actualizó el {$tipoEquipo} ID: {$equipo->id}";

                $descripcion .= "\nEquipo: " . $nombreEquipo;

                if (!empty($cambios)) {
                    $descripcion .= "\n\nCambios en campos principales:";
                    foreach ($cambios as $campo => $valores) {
                        $nombreCampo = str_replace('_', ' ', $campo);
                        $descripcion .= "\n- {$nombreCampo}: {$valores['anterior']} → {$valores['nuevo']}";
                    }
                }

                if (!empty($caracteristicasCambiadas)) {
                    $descripcion .= "\n\nCambios en características:";
                    foreach ($caracteristicasCambiadas as $cambio) {
                        $descripcion .= "\n- {$cambio['nombre']}: {$cambio['anterior']} → {$cambio['nuevo']}";
                    }
                }

                Bitacora::create([
                    'user_id' => $user?->id,
                    'nombre_usuario' => $user ? "{$user->first_name} {$user->last_name}" : 'Sistema',
                    'accion' => 'Actualización',
                    'modulo' => 'Inventario',
                    'descripcion' => $descripcion,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Equipo actualizado correctamente',
                'data' => $equipo->fresh()->load('valoresCaracteristicas.caracteristica'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar equipo: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al actualizar el equipo',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    protected function sincronizarCaracteristicas(Equipo $equipo, array $caracteristicas)
    {

        Log::debug("Sincronizando características", ['caracteristicas' => $caracteristicas]);

        // Obtener IDs nuevos que vienen en la petición
        $idsNuevos = collect($caracteristicas)->pluck('caracteristica_id')->all();

        // Log de IDs nuevos
        Log::debug("IDs nuevos enviados:", $idsNuevos);

        // Obtener los IDs existentes en la base para ese equipo
        $idsExistentes = ValoresCaracteristica::where('equipo_id', $equipo->id)
            ->pluck('caracteristica_id')
            ->all();

        // Log de IDs existentes en DB antes de eliminar
        Log::debug("IDs existentes en DB:", $idsExistentes);

        // Mostrar qué IDs serán eliminados (los que existen pero no están en nuevos)
        $idsAEliminar = array_diff($idsExistentes, $idsNuevos);
        Log::debug("IDs que se eliminarán:", $idsAEliminar);


        // Insertar o actualizar los valores recibidos
        foreach ($caracteristicas as $caracteristica) {
            ValoresCaracteristica::updateOrCreate(
                [
                    'equipo_id' => $equipo->id,
                    'caracteristica_id' => $caracteristica['caracteristica_id'],
                ],
                ['valor' => $caracteristica['valor']]
            );
        }
    }

    public function destroy($id)
    {
        $equipo = Equipo::findOrFail($id);
        $equipo->is_deleted = true;
        $equipo->save();

        return response()->json(['message' => 'Eliminado lógicamente']);
    }

    public function getEquiposPorTipoReserva($tipoReservaId)
    {
        $equipos = Equipo::where('tipo_reserva_id', $tipoReservaId)
            ->where('estado_id', 1)
            ->where('is_deleted', false)
            ->whereNotNull('numero_serie') // solo equipos
            ->get(['id', 'numero_serie as nombre', 'tipo_equipo_id']);

        return response()->json($equipos);
    }

    public function equiposDisponiblesPorTipoYFecha(Request $request)
    {
        $request->validate([
            'tipo_reserva_id' => 'required|integer',
            'fecha' => 'required|date',
            'startTime' => 'required',
            'endTime' => 'required',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string'
        ]);

        $page = $request->input('page', 1);
        $limit = $request->input('limit', 5);
        $search = $request->input('search', '');

        $fechaInicio = $request->fecha . ' ' . $request->startTime;
        $fechaFin = $request->fecha . ' ' . $request->endTime;

        // Equipos reservados
        $equiposReservados = DB::table('reserva_equipos as re')
            ->join('equipo_reserva as er', 're.id', '=', 'er.reserva_equipo_id')
            ->where('re.tipo_reserva_id', $request->tipo_reserva_id)
            ->whereIn('re.estado', ['Aprobada', 'Pendiente'])
            ->where(function ($query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween('re.fecha_reserva', [$fechaInicio, $fechaFin])
                    ->orWhereBetween('re.fecha_entrega', [$fechaInicio, $fechaFin])
                    ->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                        $q->where('re.fecha_reserva', '<=', $fechaInicio)
                            ->where('re.fecha_entrega', '>=', $fechaFin);
                    });
            })
            ->pluck('er.equipo_id');

        // Equipos en reposo
        $equiposEnReposo = DB::table('equipo_reserva')
            ->where(function ($query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween('fecha_inicio_reposo', [$fechaInicio, $fechaFin])
                    ->orWhereBetween('fecha_fin_reposo', [$fechaInicio, $fechaFin])
                    ->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                        $q->where('fecha_inicio_reposo', '<=', $fechaInicio)
                            ->where('fecha_fin_reposo', '>=', $fechaFin);
                    });
            })
            ->pluck('equipo_id');

        // Equipos disponibles (sin paginar todavía)
        $equiposDisponibles = VistaEquipo::query()
            ->where('tipo_reserva_id', $request->tipo_reserva_id)
            ->where('estado', 'Disponible')
            ->whereNotIn('equipo_id', $equiposReservados)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('nombre_modelo', 'like', "%$search%")
                        ->orWhere('nombre_marca', 'like', "%$search%");
                });
            })
            ->get();

        // Agrupar por modelo_id
        $agrupados = $equiposDisponibles
            ->groupBy('modelo_id')
            ->map(function ($equipos, $modelo_id) use ($equiposEnReposo) {
                $primer = $equipos->first();

                $equiposFormateados = $equipos->map(function ($e) use ($equiposEnReposo) {
                    return [
                        'equipo_id' => $e->equipo_id,
                        'modelo_id' => $e->modelo_id,
                        'numero_serie' => $e->numero_serie,
                        'tipo_equipo' => $e->tipo_equipo,
                        'imagen_glb' => $e->imagen_glb,
                        'imagen_normal' => $e->imagen_normal,
                        'estado' => $e->estado,
                        'escala' => $e->escala,
                        'en_reposo' => $equiposEnReposo->contains($e->equipo_id),
                    ];
                });

                $enReposoCount = $equiposFormateados->where('en_reposo', true)->count();

                return [
                    'modelo_id' => $modelo_id,
                    'nombre_modelo' => $primer->nombre_modelo,
                    'imagen_normal' => $primer->imagen_normal,
                    'imagen_glb' => $primer->imagen_glb,
                    'nombre_marca' => $primer->nombre_marca,
                    'en_reposo' => $enReposoCount, // cantidad de equipos con reposo en este grupo
                    'equipos' => $equiposFormateados->values(),
                ];
            })->values();

        // Paginación manual de grupos
        $total = $agrupados->count();
        $resultados = $agrupados->slice(($page - 1) * $limit, $limit)->values();

        $paginador = new LengthAwarePaginator(
            $resultados,
            $total,
            $limit,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json($paginador);
    }
    public function guardarObservacion(Request $request)
    {
        $validated = $request->validate([
            'reserva_id' => 'required|exists:reserva_equipos,id',
            'equipo_id' => 'required|exists:equipos,id',
            'comentario' => 'required|string|max:500',
        ]);

        $pivot = EquipoReserva::where('reserva_equipo_id', $request->reserva_id)
            ->where('equipo_id', $request->equipo_id)
            ->first();

        if (!$pivot) {
            return response()->json(['error' => 'No existe el equipo en la reserva.'], 404);
        }

        $pivot->comentario = $request->comentario;
        $pivot->save();

        return response()->json(['message' => 'Observación guardada correctamente.']);
    }


    public function obtenerEquiposDisponibilidad(Request $request)
    {
        $request->validate([
            'tipo_equipo_id' => 'nullable|integer',
            'modelo_id' => 'nullable|array',
            'modelo_id.*' => 'integer',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
            'fecha' => 'nullable|date',
            'startTime' => 'nullable|string',
            'endTime' => 'nullable|string',
        ]);

        $page = $request->input('page', 1);
        $limit = $request->input('limit', 5);
        $modeloIds = $request->input('modelo_id', []);

        $fechaInicio = $request->filled(['fecha', 'startTime']) ? $request->fecha . ' ' . $request->startTime : null;
        $fechaFin = $request->filled(['fecha', 'endTime']) ? $request->fecha . ' ' . $request->endTime : null;

        $equiposReservados = collect();
        if ($fechaInicio && $fechaFin) {
            $equiposReservados = DB::table('reserva_equipos as re')
                ->join('equipo_reserva as er', 're.id', '=', 'er.reserva_equipo_id')
                ->whereIn('re.estado', ['Aprobada', 'Pendiente'])
                ->where(function ($query) use ($fechaInicio, $fechaFin) {
                    $query->whereBetween('re.fecha_reserva', [$fechaInicio, $fechaFin])
                        ->orWhereBetween('re.fecha_entrega', [$fechaInicio, $fechaFin])
                        ->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                            $q->where('re.fecha_reserva', '<=', $fechaInicio)
                                ->where('re.fecha_entrega', '>=', $fechaFin);
                        });
                })
                ->pluck('er.equipo_id');
        }
        $equiposEnReposo = collect();
        if ($fechaInicio && $fechaFin) {
            $equiposEnReposo = DB::table('equipo_reserva')
                ->where(function ($query) use ($fechaInicio, $fechaFin) {
                    $query->whereBetween('fecha_inicio_reposo', [$fechaInicio, $fechaFin])
                        ->orWhereBetween('fecha_fin_reposo', [$fechaInicio, $fechaFin])
                        ->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                            $q->where('fecha_inicio_reposo', '<=', $fechaInicio)
                                ->where('fecha_fin_reposo', '>=', $fechaFin);
                        });
                })
                ->pluck('equipo_id');
        }
        $todosEquipos = VistaEquipo::query()
            ->when($request->filled('tipo_equipo_id'), fn($q) => $q->where('tipo_equipo_id', $request->tipo_equipo_id))
            ->when(!empty($modeloIds), fn($q) => $q->whereIn('modelo_id', $modeloIds))
            ->get();

        $agrupados = $todosEquipos
            ->groupBy('modelo_id')
            ->map(function ($equipos, $modelo_id) use ($equiposReservados, $equiposEnReposo) {
                $primer = $equipos->first();

                $disponibles = $equipos->where('estado', 'Disponible')
                    ->when($equiposReservados->isNotEmpty(), fn($q) => $q->whereNotIn('equipo_id', $equiposReservados))
                    ->count();

                $mantenimiento = $equipos->where('estado', 'Mantenimiento')->count();
                $reservados = $equipos->whereIn('equipo_id', $equiposReservados)->count();
                $enReposo = $equipos->whereIn('equipo_id', $equiposEnReposo)->count();

                return [
                    'modelo_id' => $modelo_id,
                    'nombre_modelo' => $primer->nombre_modelo,
                    'imagen_normal' => $primer->imagen_normal,
                    'imagen_glb' => $primer->imagen_glb,
                    'nombre_marca' => $primer->nombre_marca,
                    'disponibles' => $disponibles,
                    'en_reposo' => $enReposo,
                    'mantenimiento' => $mantenimiento,
                    'reservados' => $reservados,
                    'equipos' => $equipos->map(fn($e) => [
                        'equipo_id' => $e->equipo_id,
                        'modelo_id' => $e->modelo_id,
                        'numero_serie' => $e->numero_serie,
                        'tipo_equipo' => $e->tipo_equipo,
                        'imagen_glb' => $e->imagen_glb,
                        'imagen_normal' => $e->imagen_normal,
                        'estado' => $e->estado,
                    ])->values(),
                ];
            })->values();

        $total = $agrupados->count();
        $resultados = $agrupados->slice(($page - 1) * $limit, $limit)->values();

        return response()->json(new LengthAwarePaginator(
            $resultados,
            $total,
            $limit,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        ));
    }





    public function getResumenInventario(Request $request)
    {
        $query = DB::table('vista_resumen_inventario');

        // Filtros individuales
        if ($request->filled('categoria')) {
            $query->where('nombre_categoria', 'like', '%' . $request->categoria . '%');
        }

        if ($request->filled('tipo_equipo')) {
            $query->where('nombre_tipo_equipo', 'like', '%' . $request->tipo_equipo . '%');
        }

        if ($request->filled('marca')) {
            $query->where('nombre_marca', 'like', '%' . $request->marca . '%');
        }

        // Filtro global de búsqueda (modelo, tipo, marca)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre_modelo', 'like', "%$search%")
                    ->orWhere('nombre_marca', 'like', "%$search%")
                    ->orWhere('nombre_tipo_equipo', 'like', "%$search%");
            });
        }

        $perPage = $request->input('perPage', 10); // usa perPage del frontend
        $result = $query->paginate($perPage);

        return response()->json($result);
    }



    public function equiposPorModelo(Request $request, $modeloId)
    {
        $perPage = $request->input('perPage', 10);
        $search = $request->input('search');
        $tipo = $request->input('tipo');
        $estadoId = $request->input('estado_id');
        $caracteristicas = $request->input('caracteristicas'); // Nuevo parámetro para características

        $query = Equipo::with([
            'tipoEquipo',
            'modelo.marca',
            'estado',
            'tipoReserva',
            'valoresCaracteristicas.caracteristica',
            'insumos.modelo.marca',
            'equiposDondeEsInsumo.modelo.marca'
        ])
            ->where('is_deleted', false)
            ->where('modelo_id', $modeloId);

        // Filtro por tipo (equipo o insumo)
        if ($tipo) {
            if ($tipo === 'equipo') {
                $query->whereNotNull('numero_serie');
            } elseif ($tipo === 'insumo') {
                $query->whereNotNull('cantidad');
            }
        }

        // Filtro por estado
        if ($estadoId) {
            $query->where('estado_id', $estadoId);
        }

        // Filtro por características
        if ($caracteristicas && is_array($caracteristicas)) {
            foreach ($caracteristicas as $caracteristica) {
                if (isset($caracteristica['id']) && isset($caracteristica['valor'])) {
                    $query->whereHas('valoresCaracteristicas', function ($q) use ($caracteristica) {
                        $q->where('caracteristica_id', $caracteristica['id'])
                            ->where('valor', 'like', '%' . $caracteristica['valor'] . '%');
                    });
                }
            }
        }

        // Filtro de búsqueda general
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_serie', 'like', "%$search%")
                    ->orWhere('serie_asociada', 'like', "%$search%")
                    ->orWhere('detalles', 'like', "%$search%")
                    ->orWhereHas('modelo', function ($q) use ($search) {
                        $q->where('nombre', 'like', "%$search%")
                            ->orWhereHas('marca', function ($q) use ($search) {
                                $q->where('nombre', 'like', "%$search%");
                            });
                    })
                    ->orWhereHas('tipoEquipo', function ($q) use ($search) {
                        $q->where('nombre', 'like', "%$search%");
                    })
                    ->orWhereHas('valoresCaracteristicas', function ($q) use ($search) {
                        $q->where('valor', 'like', "%$search%")
                            ->orWhereHas('caracteristica', function ($q) use ($search) {
                                $q->where('nombre', 'like', "%$search%");
                            });
                    });
            });
        }
        $equipos = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $equipos->setCollection(
            $equipos->getCollection()->transform(function ($item) {
                $tipo = $item->numero_serie ? 'equipo' : 'insumo';

                $response = [
                    'id' => $item->id,
                    'tipo' => $tipo,
                    'numero_serie' => $item->numero_serie,
                    'serie_asociada' => $item->serie_asociada,
                    'vida_util' => $item->vida_util,
                    'cantidad' => $item->cantidad ?? 1, // Usar cantidad real
                    'detalles' => $item->detalles,
                    'reposo' => $item->reposo,
                    'tipo_equipo_id' => $item->tipo_equipo_id,
                    'modelo_id' => $item->modelo_id,
                    'estado_id' => $item->estado_id,
                    'tipo_reserva_id' => $item->tipo_reserva_id,
                    'fecha_adquisicion' => $item->fecha_adquisicion,
                    'imagen_url' => $item->imagen_normal
                        ? asset('storage/equipos/' . $item->imagen_normal)
                        : asset('storage/equipos/default.png'),
                    'marca' => $item->modelo->marca->nombre ?? null,
                    'tipoEquipo' => $item->tipoEquipo,
                    'modelo' => $item->modelo,
                    'estado' => $item->estado,
                    'tipoReserva' => $item->tipoReserva,
                    'caracteristicas' => $item->valoresCaracteristicas->map(function ($vc) {
                        return [
                            'id' => $vc->id,
                            'caracteristica_id' => $vc->caracteristica_id,
                            'nombre' => $vc->caracteristica->nombre ?? null,
                            'valor' => $vc->valor,
                        ];
                    }),
                ];

                // Agregar información de asignaciones según el tipo
                if ($tipo === 'equipo') {
                    $response['asignaciones'] = $item->insumos->map(function ($insumo) {
                        return [
                            'id' => $insumo->id,
                            'tipo' => 'insumo',
                            'modelo' => $insumo->modelo->nombre ?? 'N/A',
                            'marca' => $insumo->modelo->marca->nombre ?? 'N/A',
                            'numero_serie' => $insumo->numero_serie,
                            'serie_asociada' => $insumo->serie_asociada
                        ];
                    });
                } else {
                    $response['asignaciones'] = $item->equiposDondeEsInsumo->map(function ($equipo) {
                        return [
                            'id' => $equipo->id,
                            'tipo' => 'equipo',
                            'modelo' => $equipo->modelo->nombre ?? 'N/A',
                            'marca' => $equipo->modelo->marca->nombre ?? 'N/A',
                            'numero_serie' => $equipo->numero_serie,
                            'serie_asociada' => $equipo->serie_asociada
                        ];
                    });
                }

                return $response;
            })
        );

        return response()->json($equipos);
    }

    public function detalleEquipo($id)
    {
        // Consulta base con joins para traer equipo y características
        $rows = DB::table('equipos as e')
            ->join('tipo_equipos as te', 'e.tipo_equipo_id', '=', 'te.id')
            ->join('categorias as c', 'te.categoria_id', '=', 'c.id')
            ->leftJoin('tipo_reservas as tr', 'e.tipo_reserva_id', '=', 'tr.id')
            ->join('estados as es', 'e.estado_id', '=', 'es.id')
            ->join('modelos as mo', 'e.modelo_id', '=', 'mo.id')
            ->join('marcas as ma', 'mo.marca_id', '=', 'ma.id')
            ->leftJoin('valores_caracteristicas as vc', 'vc.equipo_id', '=', 'e.id')
            ->leftJoin('caracteristicas as ca', 'vc.caracteristica_id', '=', 'ca.id')
            ->where('e.is_deleted', 0)
            ->where('e.id', $id)
            ->orderBy('ca.nombre')
            ->select([
                'e.id as equipo_id',
                'c.nombre as categoria',
                'te.nombre as tipo_equipo',
                'tr.nombre as tipo_reserva',
                'es.nombre as estado',
                'ma.nombre as marca',
                'mo.nombre as modelo',
                'e.numero_serie',
                'e.vida_util',
                'e.detalles',
                'e.fecha_adquisicion',
                'e.comentario',
                'ca.nombre as caracteristica',
                'vc.valor as valor_caracteristica',
            ])
            ->get();

        if ($rows->isEmpty()) {
            return response()->json(['error' => 'Equipo no encontrado'], 404);
        }

        // Tomamos los datos generales del equipo (todos iguales en filas)
        $first = $rows->first();

        // Agrupamos características en un array
        $caracteristicas = $rows->filter(fn($r) => $r->caracteristica !== null)
            ->map(fn($r) => [
                'nombre' => $r->caracteristica,
                'valor' => $r->valor_caracteristica,
            ])
            ->values();

        // Formamos la respuesta final
        $detalleEquipo = [
            'equipo_id' => $first->equipo_id,
            'categoria' => $first->categoria,
            'tipo_equipo' => $first->tipo_equipo,
            'tipo_reserva' => $first->tipo_reserva,
            'estado' => $first->estado,
            'marca' => $first->marca,
            'modelo' => $first->modelo,
            'numero_serie' => $first->numero_serie,
            'vida_util' => $first->vida_util,
            'detalles' => $first->detalles,
            'fecha_adquisicion' => $first->fecha_adquisicion,
            'comentario' => $first->comentario,
            'caracteristicas' => $caracteristicas,
        ];

        return response()->json($detalleEquipo);
    }
}
