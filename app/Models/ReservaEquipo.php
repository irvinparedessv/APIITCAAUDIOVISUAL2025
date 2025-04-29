<?php

namespace App\Models;

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
}
