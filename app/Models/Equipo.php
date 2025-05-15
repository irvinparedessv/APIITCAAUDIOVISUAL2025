<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    /** @use HasFactory<\Database\Factories\EquipoFactory> */
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
        'cantidad',
        'is_deleted',
        'tipo_equipo_id',
        'imagen' 
    ];
    public function getImagenUrlAttribute()
    {
        return $this->imagen
            ? asset('storage/equipos/' . $this->imagen)
            : asset('storage/equipos/default.png');
    }

    // Relación con TipoEquipo
    public function tipoEquipo()
    {
        return $this->belongsTo(TipoEquipo::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('is_deleted', false)
                     ->whereHas('tipoEquipo', function ($q) {
                         $q->where('is_deleted', false);
                     });
    }
}
