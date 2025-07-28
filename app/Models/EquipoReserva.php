<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EquipoReserva extends Pivot
{
    protected $table = 'equipo_reserva';

    protected $fillable = [
        'reserva_equipo_id',
        'equipo_id',
        'comentario',
        'cantidad',
        'fecha_inicio_reposo',
        'fecha_fin_reposo'
    ];

    public $timestamps = false;

    // Relación con el modelo ReservaEquipo
    public function reserva()
    {
        return $this->belongsTo(ReservaEquipo::class, 'reserva_equipo_id');
    }

    // Relación con el modelo Equipo
    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }
}
