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
        'aula',
        'fecha_reserva',
        'fecha_entrega',
        'estado',
    ];



    public function equipos()
    {
        return $this->belongsToMany(Equipo::class, 'equipo_reserva', 'reserva_equipo_id', 'equipo_id');
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
        return $this->belongsTo(Aula::class, 'aula_id'); // o el nombre correcto del campo
    }

    protected static function booted()
    {
        static::updating(function ($reserva) {
            if ($reserva->isDirty('estado')) {
                $original = $reserva->getOriginal('estado');
                $nuevo = $reserva->estado;

                BitacoraHelper::registrar(
                    'Cambio de estado',
                    'Reserva Equipo',
                    "Estado cambiado de '{$original}' a '{$nuevo}' en la reserva con ID {$reserva->id}"
                );
            }
        });
    }


}
