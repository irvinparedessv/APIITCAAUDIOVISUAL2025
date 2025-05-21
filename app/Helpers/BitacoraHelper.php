<?php

namespace App\Helpers;

use App\Models\Bitacora;
use Illuminate\Support\Facades\Auth;

class BitacoraHelper
{
    public static function registrar(string $accion, string $modulo, ?string $descripcion = null)
    {
        $user = Auth::user();

        Bitacora::create([
            'user_id' => $user?->id,
            'nombre_usuario' => $user ? "{$user->first_name} {$user->last_name}" : 'Sistema',
            'accion' => $accion,
            'modulo' => $modulo,
            'descripcion' => $descripcion,
        ]);
    }
}
