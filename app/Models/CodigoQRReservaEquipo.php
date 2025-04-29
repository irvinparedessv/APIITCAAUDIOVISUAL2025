<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigoQrReservaEquipo extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'reserva_id'
    ];

    public function reserva()
    {
        return $this->belongsTo(ReservaEquipo::class, 'reserva_id');
    }
}
