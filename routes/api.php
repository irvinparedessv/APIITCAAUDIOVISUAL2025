<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\RoleController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\ReservaEquipoController;
use App\Http\Controllers\TipoEquipoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ProfileController;  // ✅ Para Perfil Usuario    



// Rutas públicas
Route::post('/login', [LoginController::class, 'login']);
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [PasswordResetController::class, 'reset']);
Route::get('/enviar-correo', [EmailController::class, 'enviarCorreo']);


// Rutas protegidas
Route::middleware(['auth:sanctum', 'checkrole:Administrador'])->group(function () {
    Route::get('/usuarios', function () {
        return User::with('role')->get();
    });
    Route::resource('roles', RoleController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('equipos', EquipoController::class);
    Route::apiResource('tipoEquipos', TipoEquipoController::class);
});

Route::middleware(['auth:sanctum', 'checkrole:Encargado,Administrador'])->group(function () {
    Route::get('/Obtenerequipos', [EquipoController::class, 'obtenerEquipos']);
    Route::apiResource('equipos', EquipoController::class);
    Route::apiResource('tipoEquipos', TipoEquipoController::class);
    Route::get('/reservas', [ReservaEquipoController::class, 'index']); // Ver todas las reservas
    Route::post('/reservas', [ReservaEquipoController::class, 'store']);
    // 👉 Ahora usamos el nuevo ProfileController
    Route::put('/user/profile', [ProfileController::class, 'update']);
    Route::get('/user/profile', [ProfileController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'checkrole:Prestamista,Administrador'])->group(function () {
    Route::apiResource('Obtenerequipos', EquipoController::class);
    Route::post('/reservas', [ReservaEquipoController::class, 'store']);
    Route::get('/reservas/{id}', [ReservaEquipoController::class, 'getByUser']); // Ver reservas de un usuario
    Route::get('/reservasQR/{idQr}', [ReservaEquipoController::class, 'show']); // Ver reserva por QR
    // 👉 Ahora usamos el nuevo ProfileController
    Route::put('/user/profile', [ProfileController::class, 'update']);
    Route::get('/user/profile', [ProfileController::class, 'show']);
});

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [LoginController::class, 'logout']);
});


//RESERVAS EQUIPO
// Route::get('/Obtenerequipos', [EquipoController::class, 'obtenerEquipos']);
// Route::post('/reservas', [ReservaEquipoController::class, 'store']);
// Route::get('/reservas/{id}', [ReservaEquipoController::class, 'getByUser']);
// Route::get('/reservasQR/{idQr}', [ReservaEquipoController::class, 'show']); 
