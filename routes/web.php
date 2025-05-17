<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Mail\Markdown;
use App\Models\ReservaEquipo;

Route::get('/', function () {
    return view('welcome');
});


// Ruta para previsualizar el correo de "reserva_encargado"
Route::get('/preview-reserva-encargado', function () {
    // Cargar la última reserva con los datos necesarios (usuario y equipos)
    $reserva = ReservaEquipo::with('user', 'equipos')->latest()->first();

    // Verificar si existe la reserva
    if (!$reserva) {
        abort(404, 'No se encontró ninguna reserva.');
    }

    // Renderiza el Markdown como HTML con el diseño de correo de Laravel
    return app(Markdown::class)->render('emails.reserva_encargado', [
        'reserva' => $reserva
    ]);
});

// Ruta para previsualizar el correo de "reserva_usuario"
Route::get('/preview-reserva-usuario', function () {
    // Cargar la última reserva con los datos necesarios (usuario y equipos)
    $reserva = ReservaEquipo::with('user', 'equipos')->latest()->first();

    // Verificar si existe la reserva
    if (!$reserva) {
        abort(404, 'No se encontró ninguna reserva.');
    }

    // Renderiza el Markdown como HTML con el diseño de correo de Laravel
    return app(Markdown::class)->render('emails.reserva_usuario', [
        'reserva' => $reserva
    ]);
});
