<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\RoleController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;



// Rutas públicas
Route::post('/login', [LoginController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::resource('roles', RoleController::class);
    
    Route::get('/usuarios', function () {
        return User::with('role')->get();
    });

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [LoginController::class, 'logout']);
});


// // CSRF cookie
// Route::get('/csrf-token', function () {
//     return response()->json(['token' => csrf_token()]);
// });

// // Login
// Route::post('/login', function (Request $request) {
//     $credentials = $request->only('email', 'password');

//     if (!Auth::attempt($credentials)) {
//         return response()->json(['message' => 'Invalid credentials'], 401);
//     }

//     return response()->json(['message' => 'Login successful']);
// });

// // Logout
// Route::post('/logout', function () {
//     Auth::logout();
//     return response()->json(['message' => 'Logged out']);
// });

// // Obtener usuario autenticado
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

