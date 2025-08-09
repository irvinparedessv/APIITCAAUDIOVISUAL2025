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
        'user_id',
        'fecha_mantenimiento_final',
        'hora_mantenimiento_final',
        'detalles',
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }
    // Relación con el usuario responsable del mantenimiento
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
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
