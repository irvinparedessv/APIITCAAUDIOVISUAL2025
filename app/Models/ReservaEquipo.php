<?php

namespace App\Models;

use App\Helpers\BitacoraHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservaEquipo extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipo_id',
        'user_id',
        'aula_id',
        'fecha_reserva',
        'fecha_entrega',
        'estado',
        'tipo_reserva_id',
        'documento_evento',
    ];

    public function equipos()
    {
        return $this->belongsToMany(Equipo::class, 'equipo_reserva', 'reserva_equipo_id', 'equipo_id')
            ->using(EquipoReserva::class)
            ->withPivot('cantidad', 'comentario');
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function codigoQr()
    {
        return $this->hasOne(CodigoQrReservaEquipo::class, 'reserva_id');
    }

    public function aula()
    {
        return $this->belongsTo(Aula::class, 'aula_id');
    }

    public function tipoReserva()
    {
        return $this->belongsTo(TipoReserva::class, 'tipo_reserva_id');
    }

    public function getDocumentoEventoUrlAttribute()
    {
        return $this->documento_evento
            ? asset('storage/' . $this->documento_evento)
            : null;
    }
}
