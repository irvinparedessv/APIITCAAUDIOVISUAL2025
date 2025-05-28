<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
   protected $fillable = [
        'user_id',
        'nombre_usuario',
        'accion',
        'modulo',
        'descripcion',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
