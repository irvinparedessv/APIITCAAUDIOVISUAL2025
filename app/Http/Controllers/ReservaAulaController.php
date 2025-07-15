<?php

namespace App\Http\Controllers;

use App\Helpers\BitacoraHelper;
use App\Models\Aula;
use App\Models\CodigoQrAula;
use App\Models\ReservaAula;
use App\Models\ReservaAulaBloque;
use App\Models\Role;
use App\Models\User;
use App\Notifications\CancelarReservaAulaPrestamista;
use App\Notifications\ConfirmarReservaAulaUsuario;
use App\Notifications\EmailEdicionReservaAula;
use App\Notifications\EmailEstadoAulaNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Notifications\EstadoReservaAulaNotification;
use App\Notifications\NotificarResponsableReservaAula;
use App\Notifications\NuevaReservaAulaNotification;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class ReservaAulaController extends Controller
{
    public function aulas()
    {
        $usuario = Auth::user();

        $query = Aula::with(['primeraImagen', 'horarios'])->where('deleted', false);

        // Filtrar si es espacio encargado
        if ($usuario->role->nombre === 'EspacioEncargado') {
            $query->whereHas('encargados', function ($q) use ($usuario) {
                $q->where('user_id', $usuario->id);
            });
        }

        $aulas = $query->get()->map(function ($aula) {
            return [
                'id' => $aula->id,
                'name' => $aula->name,
                'imagenes' => $aula->imagenes->map(function ($img) {
                    return [
                        'url' => url($img->image_path),
                        'is_360' => (bool) $img->is360,
                    ];
                }),
                'horarios' => $aula->horarios->map(function ($horario) {
                    return [
                        'start_date' => $horario->start_date,
                        'end_date' => $horario->end_date,
                        'days' => json_decode($horario->days),
                    ];
                }),
            ];
        });

        return response()->json($aulas);
    }


    public function horariosDisponibles($id)
    {
        $aula = Aula::with([
            'horarios',
            'reservas' => function ($q) {
                $q->whereIn('estado', ['Aprobado', 'Pendiente']);
            }
        ])->findOrFail($id);

        $result = $aula->horarios->map(function ($horario) use ($aula) {
            return [
                'start_date' => $horario->start_date,
                'end_date' => $horario->end_date,
                'start_time' => $horario->start_time,
                'end_time' => $horario->end_time,
                'days' => json_decode($horario->days),
            ];
        });

        $reservas = $aula->reservas->map(function ($r) {
            return [
                'fecha' => $r->fecha,
                'horario' => $r->horario,
            ];
        });

        return response()->json([
            'horarios' => $result,
            'reservas' => $reservas,
        ]);
    }

    public function store(Request $request)
    {
        Log::info('===== INICIANDO STORE =====');
        Log::info('Request data:', $request->all());

        $validator = Validator::make($request->all(), [
            'aula_id' => 'required|exists:aulas,id',
            'fecha' => 'required|date',
            'horario' => 'required|string',
            'dias' => 'nullable|string',
            'tipo' => 'required|in:evento,clase_recurrente,clase',
            'user_id' => 'required|exists:users,id',
            'estado' => 'nullable|string|in:pendiente,aprobado,cancelado,rechazado',
            'comentario' => 'nullable|string|max:500',
            'fecha_fin' => ['required_if:tipo,clase_recurrente', 'nullable', 'date', 'after_or_equal:fecha'],
            'dias' => ['required_if:tipo,clase_recurrente', 'array'],
            'dias.*' => ['string'],
        ]);

        if ($validator->fails()) {
            Log::info('Validación fallida:', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        [$hora_inicio, $hora_fin] = explode('-', $request->horario);
        Log::info("Hora inicio: {$hora_inicio} | Hora fin: {$hora_fin}");

        if ($request->tipo === 'evento' || $request->tipo === 'clase') {
            Log::info("Verificando conflictos para evento/clase");
            $conflicto = ReservaAula::where('aula_id', $request->aula_id)
                ->whereDate('fecha', $request->fecha)
                ->where(function ($q) use ($hora_inicio, $hora_fin) {
                    $q->where('horario', 'like', "%$hora_inicio%")
                        ->orWhere('horario', 'like', "%$hora_fin%");
                })
                ->whereIn('estado', ['pendiente', 'aprobado'])
                ->exists();

            if ($conflicto) {
                Log::info("Conflicto detectado para evento/clase");
                return response()->json([
                    'message' => 'Conflicto: Ya existe una reserva para esta aula, fecha y horario.'
                ], 409);
            }
        }

        if ($request->tipo === 'clase_recurrente') {
            Log::info("Verificando conflictos para clase_recurrente");
            $dias = array_map('trim', $request->dias);
            $fechaInicio = Carbon::parse($request->fecha);
            $fechaFin = Carbon::parse($request->fecha_fin);

            Log::info("Rango de fechas: {$fechaInicio->toDateString()} - {$fechaFin->toDateString()}");
            Log::info("Días: " . implode(', ', $dias));

            for ($fecha = $fechaInicio->copy(); $fecha->lte($fechaFin); $fecha->addDay()) {
                $diaCarbon = ucfirst($fecha->locale('es')->dayName);
                Log::info("Día actual: {$diaCarbon}");

                if (in_array($diaCarbon, $dias)) {
                    Log::info("Día {$diaCarbon} está en días seleccionados");
                    $conflicto = ReservaAulaBloque::whereHas('reserva', function ($q) use ($request) {
                        $q->where('aula_id', $request->aula_id)
                            ->whereIn('estado', ['pendiente', 'aprobado']);
                    })
                        ->whereDate('fecha_inicio', $fecha->toDateString())
                        ->where(function ($q) use ($hora_inicio, $hora_fin) {
                            $q->where(function ($q2) use ($hora_inicio, $hora_fin) {
                                $q2->where('hora_inicio', '<', $hora_fin)
                                    ->where('hora_fin', '>', $hora_inicio);
                            });
                        })
                        ->exists();

                    if ($conflicto) {
                        Log::info("Conflicto detectado en bloque para fecha {$fecha->toDateString()}");
                        return response()->json([
                            'message' => 'Conflicto: Ya existe una reserva para el aula en fecha '
                                . $fecha->format('d-m-Y') . ' y horario ' . $hora_inicio . '-' . $hora_fin
                        ], 409);
                    }
                }
            }
        }

        Log::info("Creando reserva");
        $reserva = ReservaAula::create([
            'aula_id' => $request->aula_id,
            'fecha' => $request->fecha,
            'fecha_fin' => $request->fecha_fin,
            'dias' => $request->dias,
            'tipo' => $request->tipo,
            'horario' => $request->horario,
            'user_id' => $request->user_id,
            'estado' => $request->estado ?? 'pendiente',
            'titulo' => $request->filled('comentario') ? $request->comentario : '-',
        ]);

        Log::info("Reserva creada ID: {$reserva->id}");
        // Crear código QR
        CodigoQrAula::create([
            'id' => (string) Str::uuid(),
            'reserva_id' => $reserva->id,
        ]);

        if ($request->tipo === 'clase_recurrente') {
            Log::info("Creando bloques para clase_recurrente");
            $dias = array_map('trim', $request->dias);
            [$hora_inicio, $hora_fin] = explode('-', $request->horario);

            $fechaInicio = Carbon::parse($request->fecha);
            $fechaFin = Carbon::parse($request->fecha_fin);

            for ($fecha = $fechaInicio->copy(); $fecha->lte($fechaFin); $fecha->addDay()) {
                $diaCarbon = ucfirst($fecha->locale('es')->dayName);
                if (in_array($diaCarbon, $dias)) {
                    Log::info("Creando bloque para fecha {$fecha->toDateString()} y día {$diaCarbon}");
                    $bloque = new ReservaAulaBloque([
                        'fecha_inicio' => $fecha->toDateString(),
                        'fecha_fin' => $fecha->toDateString(),
                        'hora_inicio' => trim($hora_inicio),
                        'hora_fin' => trim($hora_fin),
                        'dia' => $diaCarbon,
                        'estado' => 'pendiente',
                        'recurrente' => true
                    ]);
                    $reserva->bloques()->save($bloque);
                    Log::info("Bloque guardado");
                } else {
                    Log::info("Día {$diaCarbon} NO está en días seleccionados");
                }
            }
        }

        if ($request->tipo === 'clase' || $request->tipo === 'evento') {
            Log::info("Creando bloque para clase/evento única");
            [$hora_inicio, $hora_fin] = explode('-', $request->horario);
            $bloque = new ReservaAulaBloque([
                'fecha_inicio' => $request->fecha,
                'fecha_fin' => $request->fecha,
                'hora_inicio' => trim($hora_inicio),
                'hora_fin' => trim($hora_fin),
                'dia' => Carbon::parse($request->fecha)->locale('es')->dayName,
                'estado' => 'pendiente',
                'recurrente' => false
            ]);
            $reserva->bloques()->save($bloque);
            Log::info("Bloque guardado para clase/evento");
        }

        Log::info("===== FIN STORE =====");
        return response()->json([
            'message' => 'Reserva creada con bloques',
            'reserva' => $reserva->load('bloques'),
        ]);
    }

    public function update(Request $request, $id)
    {
        Log::info('===== INICIANDO UPDATE =====');
        Log::info('Request data:', $request->all());
        Log::info("ID reserva: {$id}");

        $validator = Validator::make($request->all(), [
            'aula_id' => 'required|exists:aulas,id',
            'fecha' => 'required|date',
            'horario' => 'required|string',
            'dias' => 'nullable|string',
            'tipo' => 'required|in:evento,clase_recurrente,clase',
            'user_id' => 'required|exists:users,id',
            'estado' => 'nullable|string|in:pendiente,aprobado,cancelado,rechazado',
            'comentario' => 'nullable|string|max:500',
            'fecha_fin' => ['required_if:tipo,clase_recurrente', 'nullable', 'date', 'after_or_equal:fecha'],
            'dias' => ['required_if:tipo,clase_recurrente', 'array'],
            'dias.*' => ['string'],
        ]);

        if ($validator->fails()) {
            Log::info('Validación fallida:', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $reserva = ReservaAula::findOrFail($id);
        Log::info("Reserva encontrada: {$reserva->id}");

        [$hora_inicio, $hora_fin] = explode('-', $request->horario);
        Log::info("Hora inicio: {$hora_inicio} | Hora fin: {$hora_fin}");

        if ($request->tipo === 'evento' || $request->tipo === 'clase') {
            Log::info("Verificando conflictos para evento/clase");
            $conflicto = ReservaAula::where('aula_id', $request->aula_id)
                ->whereDate('fecha', $request->fecha)
                ->where('id', '<>', $reserva->id)
                ->where(function ($q) use ($hora_inicio, $hora_fin) {
                    $q->where('horario', 'like', "%$hora_inicio%")
                        ->orWhere('horario', 'like', "%$hora_fin%");
                })
                ->whereIn('estado', ['pendiente', 'aprobado'])
                ->exists();

            if ($conflicto) {
                Log::info("Conflicto detectado para evento/clase");
                return response()->json([
                    'message' => 'Conflicto: Ya existe una reserva para esta aula, fecha y horario.'
                ], 409);
            }
        }

        if ($request->tipo === 'clase_recurrente') {
            Log::info("Verificando conflictos para clase_recurrente");
            $dias = array_map('trim', $request->dias);
            $fechaInicio = Carbon::parse($request->fecha);
            $fechaFin = Carbon::parse($request->fecha_fin);

            Log::info("Rango de fechas: {$fechaInicio->toDateString()} - {$fechaFin->toDateString()}");
            Log::info("Días: " . implode(', ', $dias));

            for ($fecha = $fechaInicio->copy(); $fecha->lte($fechaFin); $fecha->addDay()) {
                $diaCarbon = ucfirst($fecha->locale('es')->dayName);
                Log::info("Día actual: {$diaCarbon}");

                if (in_array($diaCarbon, $dias)) {
                    Log::info("Día {$diaCarbon} está en días seleccionados");
                    $conflicto = ReservaAulaBloque::whereHas('reserva', function ($q) use ($request, $reserva) {
                        $q->where('aula_id', $request->aula_id)
                            ->where('id', '<>', $reserva->id)
                            ->whereIn('estado', ['pendiente', 'aprobado']);
                    })
                        ->whereDate('fecha_inicio', $fecha->toDateString())
                        ->where(function ($q) use ($hora_inicio, $hora_fin) {
                            $q->where(function ($q2) use ($hora_inicio, $hora_fin) {
                                $q2->where('hora_inicio', '<', $hora_fin)
                                    ->where('hora_fin', '>', $hora_inicio);
                            });
                        })
                        ->exists();

                    if ($conflicto) {
                        Log::info("Conflicto detectado en bloque para fecha {$fecha->toDateString()}");
                        return response()->json([
                            'message' => 'Conflicto: Ya existe una reserva para el aula en fecha '
                                . $fecha->format('d-m-Y') . ' y horario ' . $hora_inicio . '-' . $hora_fin
                        ], 409);
                    }
                }
            }
        }

        Log::info("Actualizando reserva");
        $reserva->update([
            'aula_id' => $request->aula_id,
            'fecha' => $request->fecha,
            'fecha_fin' => $request->fecha_fin,
            'dias' => $request->dias,
            'tipo' => $request->tipo,
            'horario' => $request->horario,
            'user_id' => $request->user_id,
            'estado' => $request->estado ?? $reserva->estado,
            'titulo' => $request->filled('comentario') ? $request->comentario : $reserva->comentario,
        ]);

        Log::info("Reserva actualizada ID: {$reserva->id}");

        // Opcional: Eliminar bloques previos si los vas a regenerar
        $reserva->bloques()->delete();
        Log::info("Bloques antiguos eliminados");

        if ($request->tipo === 'clase_recurrente') {
            Log::info("Creando bloques para clase_recurrente (update)");
            $dias = array_map('trim', $request->dias);
            [$hora_inicio, $hora_fin] = explode('-', $request->horario);

            $fechaInicio = Carbon::parse($request->fecha);
            $fechaFin = Carbon::parse($request->fecha_fin);

            for ($fecha = $fechaInicio->copy(); $fecha->lte($fechaFin); $fecha->addDay()) {
                $diaCarbon = ucfirst($fecha->locale('es')->dayName);
                if (in_array($diaCarbon, $dias)) {
                    Log::info("Creando bloque para fecha {$fecha->toDateString()} y día {$diaCarbon}");
                    $bloque = new ReservaAulaBloque([
                        'fecha_inicio' => $fecha->toDateString(),
                        'fecha_fin' => $fecha->toDateString(),
                        'hora_inicio' => trim($hora_inicio),
                        'hora_fin' => trim($hora_fin),
                        'dia' => $diaCarbon,
                        'estado' => 'pendiente',
                        'recurrente' => true
                    ]);
                    $reserva->bloques()->save($bloque);
                    Log::info("Bloque guardado");
                } else {
                    Log::info("Día {$diaCarbon} NO está en días seleccionados");
                }
            }
        }

        if ($request->tipo === 'clase' || $request->tipo === 'evento') {
            Log::info("Creando bloque para clase/evento única (update)");
            [$hora_inicio, $hora_fin] = explode('-', $request->horario);
            $bloque = new ReservaAulaBloque([
                'fecha_inicio' => $request->fecha,
                'fecha_fin' => $request->fecha,
                'hora_inicio' => trim($hora_inicio),
                'hora_fin' => trim($hora_fin),
                'dia' => Carbon::parse($request->fecha)->locale('es')->dayName,
                'estado' => 'pendiente',
                'recurrente' => false
            ]);
            $reserva->bloques()->save($bloque);
            Log::info("Bloque guardado para clase/evento");
        }

        Log::info("===== FIN UPDATE =====");
        return response()->json([
            'message' => 'Reserva actualizada con bloques',
            'reserva' => $reserva->load('bloques'),
        ]);
    }

    public function reservas(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $from = $request->query('from');
        $to = $request->query('to');
        $status = $request->query('status');
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);

        $query = ReservaAula::with(['aula', 'user', 'codigoQr',]);

        if ($from && $to) {
            $query->whereBetween('fecha', [$from, $to]);
        }

        if ($status && strtolower($status) !== 'todos') {
            $query->where('estado', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('aula', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%$search%");
                })->orWhereHas('user', function ($q3) use ($search) {
                    $q3->where('first_name', 'like', "%$search%")
                        ->orWhere('last_name', 'like', "%$search%");
                });
            });
        }

        // ✅ Filtrar según rol
        if (strtolower($user->role->nombre) === 'espacioencargado') {
            $aulasIds = $user->aulasEncargadas()->pluck('aulas.id');

            if ($aulasIds->isEmpty()) {
                return response()->json([
                    'message' => 'No tienes aulas asignadas para mostrar reservas.'
                ], 403);
            }

            $query->whereIn('aula_id', $aulasIds);
        } elseif (strtolower($user->role->nombre) === 'prestamista') {
            $query->where('user_id', $user->id);
        }

        $reservas = $query->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($reservas);
    }



    public function actualizarEstado(Request $request, $id)
    {
        $user = Auth::user();

        $request->validate([
            'estado' => 'required|in:Aprobado,Cancelado,Rechazado',
            'comentario' => 'nullable|string',
        ]);

        $reserva = ReservaAula::with('user')->findOrFail($id);

        if (strtolower($user->role->nombre) === 'prestamista') {
            if ($reserva->user_id !== $user->id) {
                return response()->json(['error' => 'No autorizado.'], 403);
            }

            if (strtolower($request->estado) !== 'cancelado') {
                return response()->json(['error' => 'Solo puedes cancelar tu reserva.'], 403);
            }

            if (strtolower($reserva->estado) !== 'pendiente') {
                return response()->json(['error' => 'Solo puedes cancelar reservas pendientes.'], 400);
            }
        }

        $estadoAnterior = $reserva->estado;
        $reserva->estado = $request->estado;
        $reserva->comentario = $request->comentario;
        $reserva->save();
        ReservaAulaBloque::where('reserva_id', $id)
            ->update(['estado' => $reserva->estado]);
        $pagina = $this->calcularPaginaReserva($id);

        // 🔥 Notificar dependiendo de quién realiza la acción
        if (strtolower($user->role->nombre) === 'prestamista' && strtolower($request->estado) === 'cancelado') {
            // No se notifica al prestamista (ya lo hizo él), sino a los encargados y admins
            $this->notificarResponsablesPorCancelacion($user, $reserva, $pagina);
        } else {
            // Encargado o admin cambia estado → notificar al prestamista
            if ($reserva->user) {
                $reserva->user->notify(new EstadoReservaAulaNotification($reserva, $pagina));
                //$reserva->user->notify(new EmailEstadoAulaNotification($reserva));
            }
        }

        BitacoraHelper::registrarCambioEstadoReservaAula(
            $id,
            $estadoAnterior,
            $request->estado,
            $user->first_name . ' ' . $user->last_name
        );

        $reserva->load(['aula', 'user']); // 👈 Asegura que vengan las relaciones

        return response()->json([
            'message' => 'Estado actualizado correctamente.',
            'reserva' => $reserva,
        ]);
        return response()->json(['message' => 'Estado actualizado correctamente.']);
    }

    public function show($id)
    {
        $reserva = ReservaAula::with(['aula', 'user'])->find($id);

        if (!$reserva) {
            return response()->json(['message' => 'Reserva no encontrada.'], 404);
        }

        return response()->json($reserva);
    }

    private function calcularPaginaReserva(int $reservaId, int $porPagina = 10): int
    {
        $ids = ReservaAula::orderBy('created_at', 'desc')->pluck('id')->toArray();
        $index = array_search($reservaId, $ids);

        return $index === false ? 1 : (int) ceil(($index + 1) / $porPagina);
    }

    private function notificarResponsablesPorCancelacion($usuario, ReservaAula $reserva, int $pagina)
    {
        // 🔍 Espacio encargados del aula
        $encargados = User::whereHas('aulas', function ($query) use ($reserva) {
            $query->where('aula_id', $reserva->aula_id);
        })
            ->whereHas('role', function ($query) {
                $query->where('nombre', 'espacioencargado');
            })
            ->where('id', '!=', $usuario->id) // No notificar al mismo usuario que canceló
            ->get();

        // 🔍 Administradores
        $administradores = User::whereHas('role', function ($query) {
            $query->where('nombre', 'administrador');
        })
            ->where('id', '!=', $usuario->id)
            ->get();

        // 🔁 Unificamos encargados y administradores sin duplicados
        $responsables = $encargados->merge($administradores)->unique('id');

        foreach ($responsables as $responsable) {
            Log::info("Enviando notificación de cancelación al responsable ID: {$responsable->id}");
            $responsable->notify(new CancelarReservaAulaPrestamista($reserva, $responsable->id, $pagina));
        }
    }
    public function getReservasPorMes(Request $request)
    {
        $user = Auth::user();

        $mes = $request->input('mes'); // Ejemplo: "2025-07"
        $aulaId = $request->input('aula_id');

        // Parsear año y mes
        $year = substr($mes, 0, 4);
        $month = substr($mes, 5, 2);

        $bloques = ReservaAulaBloque::whereHas('reserva', function ($q) use ($year, $month, $aulaId, $user) {
            $q->whereYear('fecha', $year)
                ->whereMonth('fecha', $month)
                ->where('aula_id', $aulaId)
                ->whereHas('aula.users', function ($q2) use ($user) {
                    $q2->where('user_id', $user->id);
                });
        })->with('reserva') // cargar la reserva principal
            ->get()
            ->map(function ($bloque) {
                $bloque->title = $bloque->reservaAula->comentario ?? '';
                return $bloque;
            });

        return response()->json($bloques);
    }

    // Opcional: obtener aulas donde el usuario es encargado
    public function getAulasEncargado()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $aulas = $user->aulasEncargadas()->get();

        return response()->json($aulas);
    }
}
