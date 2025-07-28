<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipoAccesorio extends Model
{
    use HasFactory;

    protected $table = 'equipo_accesorio';

    protected $fillable = [
        'equipo_id',
        'insumo_id',
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    public function insumo()
    {
        return $this->belongsTo(Equipo::class, 'insumo_id');
    }
}
