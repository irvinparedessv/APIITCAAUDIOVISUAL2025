<?php

namespace App\Http\Controllers;

use App\Mail\ConfirmAccountMail;
use Illuminate\Validation\Rule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    // Listar todos los usuarios con su rol
    public function index()
    {
        $usuarios = User::with('role')->get();
        return response()->json($usuarios);
    }


    public function store(Request $request)
{
    try {
        // Si no se envía estado, se asigna 'inactivo' (0) por defecto
        $request->merge([
            'estado' => $request->estado ?? 0,
        ]);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'estado' => 'required|in:0,1,3',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('user_images', 'public');
        }

        $tempPassword = Str::random(10);
        $confirmationToken = Str::uuid();
        Log::info("Token generado para {$request->email}: {$confirmationToken}");

        $usuario = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($tempPassword),
            'role_id' => $request->role_id,
            'phone' => $request->phone,
            'address' => $request->address,
            'estado' => $request->estado,
            'confirmation_token' => $confirmationToken,
            'image' => $imagePath,
            'is_deleted' => false,
            'change_password' => true,
        ]);

        // Generar URL de confirmación
        $confirmationUrl = "http://localhost:5173/confirm-account/{$confirmationToken}";
        Log::info("URL de confirmación: {$confirmationUrl}");

        // Intentar enviar el correo
        try {
            Mail::to($usuario->email)->send(new ConfirmAccountMail($usuario, $tempPassword, $confirmationUrl));
        } catch (\Throwable $e) {
            Log::error("Error al enviar el correo a {$usuario->email}: " . $e->getMessage());
            // O puedes notificar que se creó el usuario, pero no se envió el correo
            return response()->json([
                'message' => 'Usuario creado, pero no se pudo enviar el correo.',
                'usuario' => $usuario,
                'error_correo' => true,
            ], 201);
        }

        return response()->json([
            'message' => 'Usuario creado y correo enviado correctamente.',
            'usuario' => $usuario
        ], 201);

    } catch (\Throwable $e) {
        Log::error("Error en el proceso de creación de usuario: " . $e->getMessage());
        return response()->json(['error' => 'Error interno al crear el usuario.'], 500);
    }
}



    // Mostrar un usuario específico
    public function show(string $id)
    {
        $usuario = User::with('role')->findOrFail($id);
        return response()->json($usuario);
    }

    // Actualizar datos de un usuario
    public function update(Request $request, string $id)
    {
        $usuario = User::findOrFail($id);

        // Validación de los campos permitidos
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $usuario->id,
            'password' => 'nullable|string|min:6',
            'role_id' => 'sometimes|required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'estado' => ['required', Rule::in([0, 1, 3])], // <-- VALIDACIÓN CORRECTA
        ]);

        // Si se incluye el campo 'estado', actualizar también 'is_deleted'
        if (isset($validated['estado'])) {
            $usuario->estado = $validated['estado'];
            $usuario->is_deleted = $validated['estado'] == 0;
        }

        // Actualizar contraseña si viene en la solicitud
        if (!empty($validated['password'])) {
            $usuario->password = Hash::make($validated['password']);
            unset($validated['password']); // Evita que se vuelva a asignar sin encriptar abajo
        }

        // Actualizar otros campos
        $usuario->fill($validated);
        $usuario->save();

        return response()->json($usuario);
    }


    // Eliminar usuario (solo si está desactivado)
    public function destroy(string $id)
    {
        $usuario = User::findOrFail($id);

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

}
