<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoReserva extends Model
{
    use HasFactory;

    protected $table = 'tipo_reservas';

    protected $fillable = ['nombre', 'is_deleted'];
}
