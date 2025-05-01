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
use App\Models\ReservaEquipo;


// Rutas públicas
Route::post('/login', [LoginController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
   
    Route::resource('roles', RoleController::class);
    Route::apiResource('users', UserController::class);
    Route::get('/obtener-equipos', [EquipoController::class, 'obtenerEquipos']);
    Route::get('/usuarios', function () {
        return User::with('role')->get();
    });
    Route::apiResource('equipos', EquipoController::class);
    Route::apiResource('tipoEquipos', TipoEquipoController::class);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [LoginController::class, 'logout']);

    
});


Route::put('/equipos/{id}', [EquipoController::class, 'destroy']);
Route::get('/enviar-correo', [EmailController::class, 'enviarCorreo']);




Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [PasswordResetController::class, 'reset']);


//RESERVAS EQUIPO
Route::get('/Obtenerequipos', [EquipoController::class, 'obtenerEquipos']);
Route::post('/reservas', [ReservaEquipoController::class, 'store']);
Route::get('/reservas/{id}', [ReservaEquipoController::class, 'getByUser']);
Route::get('/reservasQR/{idQr}', [ReservaEquipoController::class, 'show']);
