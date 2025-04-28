<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    /** @use HasFactory<\Database\Factories\EquipoFactory> */
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion', 'estado', 'cantidad','is_deleted', 'tipo_equipo_id'];

    // Relación con TipoEquipo
    public function tipoEquipo()
    {
        return $this->belongsTo(TipoEquipo::class);
    }
}
