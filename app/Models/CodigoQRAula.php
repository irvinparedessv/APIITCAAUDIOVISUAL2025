<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigoQrAula extends Model
{
    /** @use HasFactory<\Database\Factories\CodigoQRAulaFactory> */
    use HasFactory;

    protected $fillable = [
        'id',
        'reserva_id',
    ];

    public $incrementing = false; // Muy importante para UUID manual
    protected $keyType = 'string'; // UUID es string


    public function reserva()
    {
        return $this->belongsTo(ReservaAula::class, 'reserva_id');
    }
}
