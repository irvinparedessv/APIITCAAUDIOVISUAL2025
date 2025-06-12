<?php

use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\AulaController;
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
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\ChatGPTController;
use App\Http\Controllers\PrediccionEquipoController;
use App\Http\Controllers\ProfileController;  // ✅ Para Perfil Usuario    
use App\Http\Controllers\ReservaAulaController;
use App\Http\Controllers\TipoReservaController;


// Rutas públicas
Route::post('/login', [LoginController::class, 'login']);
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [PasswordResetController::class, 'reset']);
Route::get('/enviar-correo', [EmailController::class, 'enviarCorreo']);
Route::post('/confirm-account/{token}', [UserController::class, 'confirmAccount']);
Route::post('/change-password', [UserController::class, 'changePassword']);
Route::post('/chatGPT', [ChatGPTController::class, 'chatWithGpt']);



// Ruta para validar el token (puede ir en el grupo público o protegido)
Route::middleware('auth:sanctum')->get('/validate-token', function (Request $request) {
    return response()->json([
        'valid' => true,
        'user' => $request->user()->load('role') // Carga la relación 'role' si existe
    ]);
});


// Rutas protegidas
Route::middleware(['auth:sanctum', 'checkrole:Administrador'])->group(function () {
    Route::get('/usuarios', [UserController::class, 'index']);

    Route::resource('roles', RoleController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('equipos', EquipoController::class);

    Route::apiResource('tipoEquipos', TipoEquipoController::class);
});

Route::middleware(['auth:sanctum', 'checkrole:Encargado'])->group(function () {
    Route::put('/reservas-equipo/{id}/estado', [ReservaEquipoController::class, 'actualizarEstado']);
    Route::put('/reservas-aula/{id}/estado', [ReservaAulaController::class, 'actualizarEstado']);
});


Route::middleware(['auth:sanctum', 'checkrole:Encargado,Administrador'])->group(function () {
    //Route::apiResource('equipos', EquipoController::class);
    Route::apiResource('tipoEquipos', TipoEquipoController::class);
    Route::get('/reservas', [ReservaEquipoController::class, 'index']); // Ver todas las reservas
    Route::post('/reservas', [ReservaEquipoController::class, 'store']);
    Route::post('/aulas', [AulaController::class, 'store']);
    Route::get('/reservasQR/{idQr}', [ReservaEquipoController::class, 'show']); // Ver reserva por QR
    Route::put('/reservas-equipo/{id}/estado', [ReservaEquipoController::class, 'actualizarEstado']);
    Route::put('/reservas-aula/{id}/estado', [ReservaAulaController::class, 'actualizarEstado']);
    Route::get('/bitacora', [BitacoraController::class, 'index']);
    Route::get('/prediccion/reservas', [PrediccionEquipoController::class, 'predecirReservas']);
    Route::get('/prediccion/reservas/por-tipo', [PrediccionEquipoController::class, 'tiposEquipoConPrediccion']);
});

Route::middleware(['auth:sanctum', 'checkrole:Prestamista,Administrador'])->group(function () {
    Route::apiResource('Obtenerequipos', EquipoController::class);
    Route::get('equiposReserva', [ReservaEquipoController::class, 'equiposReserva']);
    Route::post('/reservas', [ReservaEquipoController::class, 'store']);
    Route::get('/reservas/{id}', [ReservaEquipoController::class, 'getByUser']); // Ver reservas de un usuario
    Route::get('/reservasQR/{idQr}', [ReservaEquipoController::class, 'show']); // Ver reserva por QR
    Route::post('/reservasAula', [ReservaAulaController::class, 'store']);
    Route::get('/aulasEquipos', [AulaController::class, 'index']);
    //Route::post('/reservas', [ReservaAulaController::class, 'store']);
    Route::get('/aulas', [ReservaAulaController::class, 'aulas']);
});

//👉Aqui podemos ver el perfil de los usuarios de acuerdo a roles
Route::middleware(['auth:sanctum', 'checkrole:Administrador,Encargado,Prestamista'])->group(function () {
    Route::put('/user/profile', [ProfileController::class, 'update']);
    Route::get('/user/profile', [ProfileController::class, 'show']);
    Route::get('/equiposPorTipo/{tipoReservaId}', [EquipoController::class, 'getEquiposPorTipoReserva']);
    Route::get('/tipo-reservas', [TipoReservaController::class, 'index']);
    Route::get('/bitacoras/reserva/{reservaId}', [BitacoraController::class, 'historialReserva']);
    Route::get('/notifications', [NotificationController::class, 'index']); // todas
    Route::get('/notifications/{id}', [NotificationController::class, 'show']); // detalle
    Route::get('/notificaciones', [ReservaEquipoController::class, 'getNotificaciones']);
    Route::post('/notificaciones/marcar-leidas', [NotificationController::class, 'marcarComoLeidas']);
    Route::post('/notificaciones/{id}/marcar-leida', [NotificationController::class, 'marcarComoLeida']);
    Route::delete('/notificaciones/{id}', [NotificationController::class, 'destroy']);
    Route::put('/notificaciones/{id}/archivar', [NotificationController::class, 'archive']);
    Route::put('/notificaciones/archivar-todas', [NotificationController::class, 'archiveAll']);
    Route::get('/notificaciones/historial', [NotificationController::class, 'history']);
    Route::delete('/notifications', [NotificationController::class, 'destroyAll']);
    Route::apiResource('equipos', EquipoController::class);
    Route::get('/reservas-aula/{id}', [ReservaAulaController::class, 'show']);
    Route::get('/reservas-aula', [ReservaAulaController::class, 'reservas']);
    Route::get('/equipos/{id}/disponibilidad', [ReservaEquipoController::class, 'verificarDisponibilidad']);
    Route::get('/obtenerEquipos', [EquipoController::class, 'obtenerEquipos']);
});


Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [LoginController::class, 'logout']);
});
Route::get('/verificar-rol', function () {
    return Auth::user();
});

//RESERVAS EQUIPO
// Route::get('/Obtenerequipos', [EquipoController::class, 'obtenerEquipos']);
// Route::post('/reservas', [ReservaEquipoController::class, 'store']);
// Route::get('/reservas/{id}', [ReservaEquipoController::class, 'getByUser']);
// Route::get('/reservasQR/{idQr}', [ReservaEquipoController::class, 'show']); 
