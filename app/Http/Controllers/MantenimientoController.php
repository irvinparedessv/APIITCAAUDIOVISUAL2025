<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Equipo;
use App\Models\Estado;
use App\Models\Mantenimiento;
use App\Models\TipoMantenimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MantenimientoController extends Controller
{
    /**
     * Listar todos los mantenimientos con paginación y relaciones.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);

        $query = Mantenimiento::with([
            'equipo.modelo.marca',
            'equipo.estado',
            'tipoMantenimiento',
            'usuario',
            'futuroMantenimiento'
        ]);


        // Filtro por equipo_id (opcional)
        if ($request->filled('equipo_id')) {
            $query->where('equipo_id', $request->equipo_id);
        }

        // Filtro por tipo_id (opcional)
        if ($request->filled('tipo_id')) {
            $query->where('tipo_id', $request->tipo_id);
        }

        // Filtro por búsqueda general en equipo, tipo y usuario
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                // Buscar en el número de serie del equipo
                $q->whereHas('equipo', function ($q2) use ($search) {
                    $q2->where('numero_serie', 'like', "%{$search}%");
                })
                    // Buscar en nombre del tipo de mantenimiento
                    ->orWhereHas('tipoMantenimiento', function ($q3) use ($search) {
                        $q3->where('nombre', 'like', "%{$search}%");
                    })
                    // Buscar en nombre o apellido del usuario
                    ->orWhereHas('usuario', function ($q4) use ($search) {
                        $q4->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $mantenimientos = $query->orderBy('fecha_mantenimiento', 'desc')
            ->paginate($perPage);

        return response()->json($mantenimientos);
    }


    /**
     * Mostrar un mantenimiento específico.
     */
    public function show($id)
    {
        $mantenimiento = Mantenimiento::with([
            'equipo.modelo.marca',
            'tipoMantenimiento',
            'usuario',
            'futuroMantenimiento'
        ])->findOrFail($id);

        return response()->json($mantenimiento);
    }

    /**
     * Crear un nuevo mantenimiento.
     */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'equipo_id' => ['required', 'exists:equipos,id'],
            'fecha_mantenimiento' => ['required', 'date', 'after_or_equal:today'],
            'hora_mantenimiento_inicio' => ['required', 'date_format:H:i'],
            'detalles' => ['nullable', 'string'],
            'tipo_id' => ['required', 'exists:tipo_mantenimientos,id'],
            'user_id' => ['required', 'exists:users,id'],
            'futuro_mantenimiento_id' => ['nullable', 'exists:futuro_mantenimientos,id'],
            'vida_util' => ['nullable', 'integer', 'min:0'],
        ]);

        DB::beginTransaction();

        try {
            // Obtener información para bitácora antes de cambios
            $equipo = Equipo::with('modelo.marca', 'estado')->find($validated['equipo_id']);
            $tipoMantenimiento = TipoMantenimiento::find($validated['tipo_id']);
            $estadoAnterior = $equipo->estado->nombre ?? 'Desconocido';

            // 1. Crear el mantenimiento
            $mantenimiento = Mantenimiento::create($validated);

            // 2. Actualizar estado del equipo
            $equipo->estado_id = 2; // Asumiendo que 2 es "En Mantenimiento"
            $equipo->save();
            $estadoNuevo = Estado::find(2)->nombre;

            // Registrar en bitácora
            $user = Auth::user();
            $descripcion = ($user ? "{$user->first_name} {$user->last_name}" : 'Sistema') .
                " creó un mantenimiento:\n" .
                "Equipo: {$equipo->modelo->marca->nombre} {$equipo->modelo->nombre} (S/N: {$equipo->numero_serie})\n" .
                "Tipo: {$tipoMantenimiento->nombre}\n" .
                "Fecha: {$validated['fecha_mantenimiento']} a las {$validated['hora_mantenimiento_inicio']}\n" .
                "Estado: {$estadoAnterior} → {$estadoNuevo}\n" .
                "Detalles: " . ($validated['detalles'] ?? 'Ninguno');

            Bitacora::create([
                'user_id' => $user?->id,
                'nombre_usuario' => $user ? "{$user->first_name} {$user->last_name}" : 'Sistema',
                'accion' => 'Creación de mantenimiento',
                'modulo' => 'Mantenimiento',
                'descripcion' => $descripcion,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento creado correctamente',
                'data' => [
                    'mantenimiento' => $mantenimiento,
                    'equipo' => $equipo->fresh()
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear mantenimiento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Actualizar un mantenimiento existente.
     */
    public function update(Request $request, $id)
    {
        $mantenimiento = Mantenimiento::findOrFail($id);

        $validated = $request->validate([
            'equipo_id' => ['sometimes', 'required', 'exists:equipos,id'],
            'fecha_mantenimiento' => ['sometimes', 'required', 'date'],
            'hora_mantenimiento_inicio' => ['sometimes', 'required', 'date_format:H:i'],
            //'hora_mantenimiento_final' => ['sometimes', 'required', 'date_format:H:i:s', 'after_or_equal:hora_mantenimiento_inicio'],
            'detalles' => ['nullable', 'string'],
            'tipo_id' => ['sometimes', 'required', 'exists:tipo_mantenimientos,id'],
            'user_id' => ['sometimes', 'required', 'exists:users,id'],
            'futuro_mantenimiento_id' => ['nullable', 'exists:futuro_mantenimientos,id'],
            'vida_util' => ['nullable', 'integer', 'min:0'],
        ]);

        $mantenimiento->update($validated);

        return response()->json([
            'message' => 'Mantenimiento actualizado correctamente',
            'data' => $mantenimiento->load([
                'equipo.modelo.marca',
                'tipoMantenimiento',
                'usuario',
                'futuroMantenimiento'
            ]),
        ]);
    }

    /**
     * Eliminar un mantenimiento.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $mantenimiento = Mantenimiento::findOrFail($id);
            $equipo = Equipo::find($mantenimiento->equipo_id);

            // Guardar información para la bitácora antes de hacer cambios
            $user = Auth::user();
            $tipoMantenimiento = $mantenimiento->tipoMantenimiento;
            $estadoAnterior = $equipo->estado->nombre ?? 'Desconocido';

            // 1. Actualizar el estado del equipo a "Disponible" (ID 1)
            $equipo->estado_id = 1; // Asumiendo que 1 es "Disponible"
            $equipo->save();
            $estadoNuevo = Estado::find(1)->nombre;

            // 2. Eliminar el mantenimiento
            $mantenimiento->delete();

            // Registrar en bitácora
            $descripcion = ($user ? "{$user->first_name} {$user->last_name}" : 'Sistema') .
                " eliminó un mantenimiento:\n" .
                "Equipo: {$equipo->modelo->marca->nombre} {$equipo->modelo->nombre} (S/N: {$equipo->numero_serie})\n" .
                "Tipo: {$tipoMantenimiento->nombre}\n" .
                "Estado: {$estadoAnterior} → {$estadoNuevo}";

            Bitacora::create([
                'user_id' => $user?->id,
                'nombre_usuario' => $user ? "{$user->first_name} {$user->last_name}" : 'Sistema',
                'accion' => 'Eliminación de mantenimiento',
                'modulo' => 'Mantenimiento',
                'descripcion' => $descripcion,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento eliminado correctamente y equipo marcado como disponible',
                'data' => [
                    'equipo' => $equipo->fresh()
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar mantenimiento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
