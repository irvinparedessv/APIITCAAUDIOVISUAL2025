<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    protected $fillable = [
        'equipo_id',
        'fecha_mantenimiento',
        'hora_mantenimiento_inicio',
        'hora_mantenimiento_final',
        'detalles',
        'tipo_id',
        'user_id',
        'futuro_mantenimiento_id',
        'vida_util',
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function tipoMantenimiento()
    {
        return $this->belongsTo(TipoMantenimiento::class, 'tipo_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function futuroMantenimiento()
    {
        return $this->belongsTo(FuturoMantenimiento::class, 'futuro_mantenimiento_id');
    }
}
