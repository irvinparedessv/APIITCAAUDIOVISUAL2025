<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservaAulaBloque extends Model
{
    use HasFactory;

    protected $table = 'reserva_aula_bloques';

    protected $fillable = [
        'reserva_id',
        'fecha_inicio',
        'fecha_fin',
        'hora_inicio',
        'hora_fin',
        'dia',
        'estado',
        'recurrente'
    ];

    public function reserva()
    {
        return $this->belongsTo(ReservaAula::class, 'reserva_id');
    }
}
