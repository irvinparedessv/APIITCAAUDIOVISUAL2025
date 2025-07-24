<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuturoMantenimiento extends Model
{
    protected $fillable = [
        'equipo_id',
        'tipo_mantenimiento_id',
        'fecha_mantenimiento',
        'hora_mantenimiento_inicio',
        'hora_mantenimiento_final',
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function tipoMantenimiento()
    {
        return $this->belongsTo(TipoMantenimiento::class);
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'futuro_mantenimiento_id');
    }
}
