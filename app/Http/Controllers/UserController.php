<?php

namespace App\Http\Controllers;

use App\Mail\ConfirmAccountMail;
use Illuminate\Validation\Rule;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        // Excluir registros marcados como eliminados
        $query = User::with('role')->where('is_deleted', 0);

        // Filtros individuales
        if ($request->has('first_name')) {
            $query->where('first_name', 'like', '%' . $request->first_name . '%');
        }

        if ($request->has('last_name')) {
            $query->where('last_name', 'like', '%' . $request->last_name . '%');
        }

        if ($request->has('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }

        if ($request->has('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('phone')) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }

        // Filtro de búsqueda general
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Ordenar por fecha de creación descendente para mostrar los últimos registros primero
        $query->orderBy('created_at', 'desc');

        $usuarios = $query->paginate($perPage);

        return response()->json($usuarios);
    }



    public function preferences(Request $request)
    {
        $user = $request->user();

        if ($request->isMethod('patch')) {
            $request->validate([
                'dark_mode' => 'required|boolean',
            ]);

            $user->dark_mode = $request->dark_mode;
            $user->save();

            return response()->json(['message' => 'Theme updated']);
        }

        // Método GET: devuelve la preferencia actual
        return response()->json([
            'darkMode' => $user->dark_mode,
        ]);
    }




    public function store(Request $request)
    {
        DB::beginTransaction(); // ⬅️ Iniciar transacción

        try {
            // Verificar primero si el email ya existe
            if (User::where('email', $request->email)->exists()) {
                return response()->json([
                    'error' => 'email_exists',
                    'message' => 'El correo electrónico ya está registrado en el sistema'
                ], 409); // 409 Conflict
            }

            $request->merge([
                'estado' => $request->estado ?? 0,
            ]);

            $validatedData = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'role_id' => 'required|exists:roles,id',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'estado' => 'required|in:0,1,3',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Procesamiento de la imagen
            $imagePath = null;
            if ($request->hasFile('image')) {
                try {
                    $imagePath = $request->file('image')->store('user_images', 'public');
                } catch (\Exception $e) {
                    Log::error("Error al subir la imagen: " . $e->getMessage());
                    return response()->json([
                        'error' => 'image_upload_error',
                        'message' => 'No se pudo subir la imagen del usuario'
                    ], 500);
                }
            }

            // Generar contraseña y token
            $tempPassword = Str::random(10);
            $confirmationToken = Str::uuid();

            // Crear usuario
            $usuario = User::create([
                'first_name' => $validatedData['first_name'],
                'last_name' => $validatedData['last_name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($tempPassword),
                'role_id' => $validatedData['role_id'],
                'phone' => $validatedData['phone'],
                'address' => $validatedData['address'],
                'estado' => $validatedData['estado'],
                'confirmation_token' => $confirmationToken,
                'image' => $imagePath,
                'is_deleted' => false,
                'change_password' => true,
            ]);

            $baseUrl = env('APP_MAIN', 'http://localhost:5173');
            $confirmationUrl = "{$baseUrl}/confirm-account/{$confirmationToken}";

            try {
               Mail::to($usuario->email)->send(new ConfirmAccountMail($usuario, $tempPassword, $confirmationUrl));
            } catch (\Throwable $e) {
                Log::error("Error al enviar el correo a {$usuario->email}: " . $e->getMessage());

                // Si falla el correo, hacer rollback y no guardar el usuario
                DB::rollBack(); // ⬅️ Deshacer transacción

                return response()->json([
                    'error' => 'mail_error',
                    'message' => 'No se pudo enviar el correo de confirmación. El usuario no fue creado.',
                    'mail_error' => $e->getMessage()
                ], 500);
            }

            DB::commit(); // ⬅️ Confirmar transacción solo si todo fue bien

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado y correo enviado correctamente.',
                'usuario' => $usuario
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'validation_error',
                'messages' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack(); // ⬅️ Asegura que se deshaga todo si ocurre cualquier otro error
            Log::error("Error en el proceso de creación de usuario: " . $e->getMessage());
            return response()->json([
                'error' => 'server_error',
                'message' => 'Error interno al crear el usuario: ' . $e->getMessage()
            ], 500);
        }
    }



    // Mostrar un usuario específico
    public function show(string $id)
    {
        $usuario = User::with('role')->findOrFail($id);
        return response()->json($usuario);
    }

    // Actualizar datos de un usuario
    // Actualizar datos de un usuario
    public function update(UpdateUserRequest $request, string $id)
    {
        $usuario = User::findOrFail($id);

        // Validación de los campos permitidos
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $usuario->id,
            'role_id' => 'sometimes|required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'estado' => ['required', Rule::in([0, 1, 3])],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Procesar la imagen solo si se envía
        if ($request->hasFile('image')) {
            // Eliminar imagen anterior si existe
            if ($usuario->image && Storage::exists('public/' . $usuario->image)) {
                Storage::delete('public/' . $usuario->image);
            }

            // Subir la nueva imagen
            try {
                $imagePath = $request->file('image')->store('user_images', 'public');
                $validated['image'] = $imagePath; // Agregar la nueva ruta de imagen a los datos validados
            } catch (\Exception $e) {
                Log::error("Error al subir la imagen: " . $e->getMessage());
                return response()->json([
                    'error' => 'image_upload_error',
                    'message' => 'No se pudo subir la imagen del usuario'
                ], 500);
            }
        } else {
            // Mantener la imagen existente si no se envía una nueva
            $validated['image'] = $usuario->image;
        }

        // Actualizar campos
        $usuario->update($validated);

        return response()->json($usuario);
    }



    // Eliminar usuario (solo si está desactivado)
    public function destroy(string $id)
    {
        $usuario = User::findOrFail($id);
        $usuario->email = 'deleted_' . $usuario->id . '_' . time() . '@xdeleted.com';

        // En lugar de eliminar físicamente, se marca como inactivo y eliminado
        $usuario->estado = 0; // Inactivo
        $usuario->is_deleted = true;
        $usuario->save();

        return response()->json(['message' => 'Usuario desactivado correctamente.']);
    }


    // Modifica el método confirmAccount para cambiar a estado 3 (pendiente)
    public function confirmAccount($token)
    {
        DB::beginTransaction();

        try {
            $user = User::where('confirmation_token', $token)->first();

            if (!$user) {
                return response()->json(['message' => 'Token inválido'], 404);
            }

            $user->update([
                'estado' => 3, // Cambia a pendiente (esperando cambio de contraseña)
                'confirmation_token' => null,
                'email_verified_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cuenta confirmada. Ahora puede iniciar sesión.',
                'email' => $user->email
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al confirmar la cuenta'], 500);
        }
    }

    // Modifica el método changePassword para activar la cuenta
    public function changePassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed'
        ]);

        $user = User::where('email', $request->email)->first();

        // Verificar contraseña actual
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Contraseña actual incorrecta'], 401);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
            'change_password' => false,
            'estado' => 1 // Cambia a activo
        ]);

        return response()->json(['message' => 'Contraseña actualizada y cuenta activada']);
    }

    public function getUsuariosPorRol($nombreRol)
    {
        $usuarios = User::whereHas('role', function ($query) use ($nombreRol) {
            $query->where('nombre', $nombreRol);
        })
            ->where('estado', 1)
            ->select('id', 'first_name', 'last_name', 'email')
            ->get();

        return response()->json($usuarios);
    }
}
