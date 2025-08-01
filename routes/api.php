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
use App\Http\Controllers\CaracteristicaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ChatGPTController;
use App\Http\Controllers\EquipoAccesorioController;
use App\Http\Controllers\EstadoController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\ModeloController;
use App\Http\Controllers\ModelUploadController;
use App\Http\Controllers\PrediccionAulaController;
use App\Http\Controllers\PrediccionEquipoController;
use App\Http\Controllers\ProfileController;  // ✅ Para Perfil Usuario    
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReservaAulaController;
use App\Http\Controllers\TipoReservaController;
use App\Http\Controllers\ValoresCaracteristicaController;
use App\Http\Controllers\TipoMantenimientoController;
use App\Http\Controllers\FuturoMantenimientoController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\ModeloAccesorioController;
use App\Services\PrediccionEquipoService;

// Rutas públicas
Route::get('/equiposDisponiblesPorTipoYFecha', [EquipoController::class, 'equiposDisponiblesPorTipoYFecha']);

Route::post('/login', [LoginController::class, 'login']);
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [PasswordResetController::class, 'reset']);
Route::get('/enviar-correo', [EmailController::class, 'enviarCorreo']);
Route::post('/confirm-account/{token}', [UserController::class, 'confirmAccount']);
Route::post('/change-password', [UserController::class, 'changePassword']);
Route::post('/chatGPT', [ChatGPTController::class, 'chatWithGpt']);
Route::post('/upload-model', [ModelUploadController::class, 'upload']);
Route::get('/get-model-path/{id}', [ModelUploadController::class, 'getModelPath']);




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
    Route::get('/usersM', [UserController::class, 'getUserM']);
    Route::apiResource('equipos', EquipoController::class);
    Route::apiResource('tipoEquipos', TipoEquipoController::class);
});

Route::middleware(['auth:sanctum', 'checkrole:Prestamista'])->group(function () {
    Route::post('/sugerir-espacios', [ChatGPTController::class, 'sugerirEspacios']);
});

Route::middleware(['auth:sanctum', 'checkrole:Encargado,Administrador,EspacioEncargado'])->group(function () {
    //Route::apiResource('equipos', EquipoController::class);
    Route::get('/reservas', [ReservaEquipoController::class, 'index']); // Ver todas las reservas
    Route::post('/reservas', [ReservaEquipoController::class, 'store']);
    Route::post('/aulas', [AulaController::class, 'store']);
    Route::get('/reservasQR/{idQr}', [ReservaEquipoController::class, 'show']); // Ver reserva por QR
    Route::get('/bitacora', [BitacoraController::class, 'index']);
    Route::get('/Getaulas', [AulaController::class, 'list']);
    Route::delete('/aulas/{id}', [AulaController::class, 'destroy']);
    Route::post('/aulas/{id}/update', [AulaController::class, 'update']);
    Route::get('/aulas/{id}', [AulaController::class, 'show']);
    Route::get('/prediccion/reservas', [PrediccionEquipoController::class, 'predecirReservas']);
    Route::get('/prediccion/reservas/por-tipo', [PrediccionEquipoController::class, 'tiposEquipoConPrediccion']);
    Route::get('/prediccion/equipos/buscar', [PrediccionEquipoController::class, 'buscarEquipos']);
    Route::get('/prediccion/equipos/{id}', [PrediccionEquipoController::class, 'prediccionPorEquipo']);
    Route::get('/prediccion/reservas/top5', [PrediccionEquipoController::class, 'top5EquiposConPrediccion']);

    Route::get('/prediccion/aula/{aulaId}', [PrediccionAulaController::class, 'predecir']);
    Route::get('/prediccion/aulas/general', [PrediccionAulaController::class, 'prediccionGeneralAulas']);

    Route::get('/prediccion/equipos/buscarVidaUtil', [PrediccionEquipoController::class, 'buscarEquiposVidaUtil']);
    Route::get('/prediccionVidaUtil/equipos/{id}', [PrediccionEquipoService::class, 'predecirVidaUtil']);
    Route::get('/prediccion/vida-util/{id}', [PrediccionEquipoController::class, 'vidaUtilPorEquipo']);



    Route::post('/equipo-reserva/observacion', [EquipoController::class, 'guardarObservacion']);


    Route::get('/reservas/dia', [ReservaEquipoController::class, 'reservasDelDia']);
    Route::get('/reportes/reservas-rango', [ReporteController::class, 'reporteReservasPorRango']);
    Route::get('/reportes/reservas-por-usuario', [ReporteController::class, 'reporteReservasPorUsuario']);
    Route::get('/reportes/uso-aulas', [ReporteController::class, 'reporteUsoAulas']);
    Route::get('/reportes/reservas-por-aula', [ReporteController::class, 'reporteUsoPorAula']);
    Route::get('/reportes/uso-equipos', [ReporteController::class, 'reporteUsoEquipos']);
    Route::get('/reportes/horarios-solicitados', [ReporteController::class, 'reporteHorariosSolicitados']);
    Route::get('/reportes/inventario-equipos', [ReporteController::class, 'reporteInventarioEquipos']);


    Route::get('/marcas', [MarcaController::class, 'index']);
    Route::post('/marcas', [MarcaController::class, 'store']);
    Route::get('/marcas/obtener', [MarcaController::class, 'obtenerMarcas']);
    Route::get('/marcas/search-select', [MarcaController::class, 'searchForSelect']);
    Route::get('/modelos', [ModeloController::class, 'index']);
    //Route::get('/modelos/por-marca/{marcaId}', [ModeloController::class, 'porMarca']);
    Route::get('/modelos/por-marca-tipo/{marcaId}', [ModeloController::class, 'porMarcaYTipo']);
    Route::get('/modelos/por-marca-y-tipo', [ModeloController::class, 'getModelosPorMarcaYTipo']);

    Route::post('/modelos', [ModeloController::class, 'store']);
    Route::get('/estados', [EstadoController::class, 'index']);
    Route::get('/categorias', [CategoriaController::class, 'index']);
    Route::get('/caracteristicas', [CaracteristicaController::class, 'index']);
    Route::post('/nuevaCaracteristica', [CaracteristicaController::class, 'store']);
    Route::get('/tipo-equipos/{id}/caracteristicas', [TipoEquipoController::class, 'getCaracteristicas']);

    Route::get('/equipos/{equipoId}/insumos', [EquipoAccesorioController::class, 'index']);
    Route::post('/equipos/{equipoId}/insumos', [EquipoAccesorioController::class, 'store']);
    Route::delete('/equipos/{equipoId}/insumos/{insumoId}', [EquipoAccesorioController::class, 'destroy']);
    Route::get('/equipos/{equipoId}/insumos/no-asignados', [EquipoAccesorioController::class, 'insumosNoAsignados']);

    Route::get('/tipo-equipo/{id}/check-equipos', [TipoEquipoController::class, 'checkEquipos']);
    Route::post('/tipo-equipo/check-equipos-masivo', [TipoEquipoController::class, 'checkEquiposMasivo']);


    Route::get('/valores-caracteristica/{equipo}', [ValoresCaracteristicaController::class, 'index']);
    Route::get('/valores-caracteristica/equipo/{equipoId}', [ValoresCaracteristicaController::class, 'caracteristicasConValoresPorEquipo']);
    Route::post('/valores-caracteristica/equipo/{equipoId}/actualizar', [ValoresCaracteristicaController::class, 'actualizarValoresPorEquipo']);

    Route::get('/inventario/modelo/{modeloId}', [EquipoController::class, 'equiposPorModelo']);
    Route::get('/resumen-inventario', [EquipoController::class, 'getResumenInventario']);

    Route::post('/modelo-accesorios', [ModeloAccesorioController::class, 'store']);
    Route::get('/modelos/{id}/accesorios', [ModeloAccesorioController::class, 'index']);
    Route::get('/modelos/insumos/listar', [ModeloAccesorioController::class, 'listarInsumos']);
    Route::delete('/equipos/{equipo}/asignaciones/{insumo}', [EquipoAccesorioController::class, 'destroy']);
    Route::get('/equipoInsumo/{equipo}', [EquipoAccesorioController::class, 'show']);

    Route::get('/detalleEquipo/{id}', [EquipoController::class, 'detalleEquipo']);


    Route::get('/obtenerTipo', [TipoEquipoController::class, 'obtenerTipo']);
    Route::apiResource('tipoEquipos', TipoEquipoController::class);

    Route::prefix('mod')->group(function () {
        Route::get('/modelos', [ModeloController::class, 'mod_index']);
        Route::get('/marcas', [ModeloController::class, 'mod_marcas']);
        Route::post('/modelos', [ModeloController::class, 'mod_store']);
        Route::put('/modelos/{id}', [ModeloController::class, 'mod_update']);
        Route::delete('/modelos/{id}', [ModeloController::class, 'mod_destroy']);
        Route::post('/modUpload', [ModeloController::class, 'mod_Upload']);
        Route::get('/modelos/{id}', [ModeloController::class, 'mod_show']);
    });

    Route::prefix('mar')->group(function () {
        Route::get('/marcas', [MarcaController::class, 'index']);
        Route::post('/marcas', [MarcaController::class, 'store']);
        Route::put('/marcas/{id}', [MarcaController::class, 'update']);
        Route::delete('/marcas/{id}', [MarcaController::class, 'destroy']);
        Route::get('/marcas/{id}', [MarcaController::class, 'show']); // opcional
    });

    // Obtener todos los mantenimientos
    Route::get('/mantenimientos', [MantenimientoController::class, 'index']);

    // Crear un nuevo mantenimiento
    Route::post('/mantenimientos', [MantenimientoController::class, 'store']);

    // Obtener un mantenimiento específico
    Route::get('/mantenimientos/{id}', [MantenimientoController::class, 'show']);

    // Actualizar un mantenimiento existente
    Route::put('/mantenimientos/{id}', [MantenimientoController::class, 'update']);

    // Eliminar un mantenimiento
    Route::delete('/mantenimientos/{id}', [MantenimientoController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'checkrole:Prestamista,Administrador,Encargado'])->group(function () {
    Route::apiResource('Obtenerequipos', EquipoController::class);
    Route::get('equiposReserva', [ReservaEquipoController::class, 'equiposReserva']);
    Route::post('/reservas', [ReservaEquipoController::class, 'store']);

    Route::post('/BOTreservas', [ReservaEquipoController::class, 'store']);
    //Route::post('/reservas', [ReservaAulaController::class, 'store']);

    // RUTAS DE MANTENIMIENTO
    Route::apiResource('tipoMantenimiento', TipoMantenimientoController::class);
    Route::apiResource('futuroMantenimiento', FuturoMantenimientoController::class);
    //Route::apiResource('mantenimientos', MantenimientoController::class);
});

Route::middleware(['auth:sanctum', 'checkrole:Administrador,Encargado,Prestamista,EspacioEncargado'])->group(function () {
    Route::put('/user/profile', [ProfileController::class, 'update']);
    Route::get('/user/profile', [ProfileController::class, 'show']);
    Route::get('/equiposPorTipo/{tipoReservaId}', [EquipoController::class, 'getEquiposPorTipoReserva']);
    Route::get('/tipo-reservas', [TipoReservaController::class, 'index']);
    Route::get('/bitacoras/reserva/{reservaId}', [BitacoraController::class, 'historialReserva']);
    Route::get('/bitacoras/reserva-aula/{reservaId}', [BitacoraController::class, 'historialReservaAula']);
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
    Route::get('/getaulas', [AulaController::class, 'getaulas']);
    Route::apiResource('equipos', EquipoController::class);
    Route::get('/modelosEquiposDisponibles', [ModeloController::class, 'modelosEquiposDisponibles']);
    Route::get('/detail/{id}', [ReservaEquipoController::class, 'detail']); // edit reserva

    Route::get('/reservas/{id}', [ReservaEquipoController::class, 'getByUser']); // Ver reservas de un usuario
    Route::get('/reservas-aula/{id}', [ReservaAulaController::class, 'show']);
    Route::get('/reservas-aula', [ReservaAulaController::class, 'reservas']);
    Route::get('/equipos/{id}/disponibilidad', [ReservaEquipoController::class, 'verificarDisponibilidad']);
    Route::get('/obtenerEquipos', [EquipoController::class, 'obtenerEquiposDisponibilidad']);
    Route::get('/aulasEquipos', [AulaController::class, 'index']);
    Route::get('/aulas/{id}/horarios', [ReservaAulaController::class, 'horariosDisponibles']);
    Route::get('/aulas', [ReservaAulaController::class, 'aulas']);
    Route::apiResource('tipoEquipos', TipoEquipoController::class);
    Route::put('/reservas-aula/{id}/estado', [ReservaAulaController::class, 'actualizarEstado']);
    Route::put('/reservas-equipo/{id}/estado', [ReservaEquipoController::class, 'actualizarEstado']);
    Route::get('/usuarios/rol/{nombreRol}', [UserController::class, 'getUsuariosPorRol']);
    Route::post('/reservasAula', [ReservaAulaController::class, 'store']);
    Route::post('/reservas-equipo/{id}', [ReservaEquipoController::class, 'update']);
    Route::post('/buscar-sugerencias-modelo', [ReservaEquipoController::class, 'buscarSugerenciasModelo']);
    Route::get('/reserva-id/{id}', [ReservaEquipoController::class, 'showById']);  // Obtener reserva individual por id
    Route::put('/reservas-aula/{id}', [ReservaAulaController::class, 'update']);
    Route::post('/aulas/{aula}/encargados', [AulaController::class, 'asignarEncargados']);
    Route::get('/aulas/{id}/encargados', [AulaController::class, 'encargados']);
    Route::get('/aulas/{aula}/disponibilidad', [AulaController::class, 'disponibilidad']);
    Route::get('/encargados', function () {
        return \App\Models\User::encargadosAula()->get();
    });

    Route::post('/user/update-password', [PasswordResetController::class, 'updatePassword']);
    Route::get('/getreservasmonth', [ReservaAulaController::class, 'getReservasPorMes']);
    Route::get('/aulas-encargados', [ReservaAulaController::class, 'getAulasEncargado']);
    Route::match(['get', 'patch'], '/user/preferences', [UserController::class, 'preferences']);

    Route::post('/aula/aulaUpload', [AulaController::class, 'aulaUpload']);
    Route::post('/aulas-disponibles', [AulaController::class, 'aulasDisponiblesPorFechas']);
});


Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [LoginController::class, 'logout']);
});

Route::middleware('auth:sanctum')->get('/verificar-rol', function () {
    return response()->json([
        'role' => Auth::user()->role->nombre,
        'user' => Auth::user(),
    ]);
});
