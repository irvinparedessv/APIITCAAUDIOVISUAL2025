<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoMantenimiento extends Model
{
    protected $fillable = ['nombre', 'estado'];

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'tipo_id');
    }

    public function futurosMantenimientos()
    {
        return $this->hasMany(FuturoMantenimiento::class, 'tipo_mantenimiento_id');
    }
}
