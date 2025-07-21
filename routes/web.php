<?php

use Illuminate\Support\Facades\Route;



Route::get('/', function () {
    return view('welcome');
});


Route::get('/api/models/{filename}', function ($filename) {
    $path = storage_path("app/public/models/{$filename}");

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Access-Control-Allow-Origin' => 'http://localhost:5173',
        'Access-Control-Allow-Credentials' => 'true',
    ]);
});
