<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservaAula extends Model
{
    use HasFactory;

    protected $table = 'reserva_aulas';

    protected $fillable = [
        'aula_id',
        'fecha',
        'fecha_fin',
        'dias',
        'tipo',
        'horario',
        'user_id',
        'estado',
        'titulo',
        'comentario',
        'path_model'
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_fin' => 'date',
        'dias' => 'array',
    ];

    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function reservaEquipos()
    {
        return $this->belongsToMany(ReservaEquipo::class, 'reserva_equipo_aula', 'reserva_aula_id', 'reserva_equipo_id');
    }
    public function codigoQr()
    {
        return $this->hasOne(CodigoQrAula::class, 'reserva_id');
    }
    public function bloques()
    {
        return $this->hasMany(ReservaAulaBloque::class, 'reserva_id');
    }
}
