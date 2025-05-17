<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Mail\Markdown;
use App\Models\ReservaEquipo;


Route::get('/', function () {
    return view('welcome');
});


