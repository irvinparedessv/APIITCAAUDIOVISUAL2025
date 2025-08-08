<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    protected $fillable = [
        'equipo_id',
        'fecha_mantenimiento',
        'fecha_mantenimiento_final',
        'hora_mantenimiento_inicio',
        'hora_mantenimiento_final',
        'detalles',
        'comentario',
        'tipo_id',
        'user_id',
        'futuro_mantenimiento_id',
        'vida_util',
        'estado_equipo_inicial',
        'estado_equipo_final',
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

    public function estadoInicial()
    {
        return $this->belongsTo(Estado::class, 'estado_equipo_inicial');
    }

    public function estadoFinal()
    {
        return $this->belongsTo(Estado::class, 'estado_equipo_final');
    }
}
