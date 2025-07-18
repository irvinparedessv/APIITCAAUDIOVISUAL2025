<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $fillable = ['nombre', 'is_deleted'];

    public function tipoEquipos()
    {
        return $this->hasMany(TipoEquipo::class);
    }
}
