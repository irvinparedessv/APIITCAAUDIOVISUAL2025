<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caracteristica extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'tipo_dato', 'is_deleted'];

    // Relación con TipoEquipo (muchos a muchos)
    public function tiposEquipos()
    {
        return $this->belongsToMany(TipoEquipo::class, 'caracteristicas_tipo_equipo');
    }

    // Relación con ValoresCaracteristica
    public function valores()
    {
        return $this->hasMany(ValoresCaracteristica::class);
    }

    // Scope para filtrar solo activas
    public function scopeActivas($query)
    {
        return $query->where('is_deleted', false);
    }
}
