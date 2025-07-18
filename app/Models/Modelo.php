<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Modelo extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'marca_id',
        'imagen_local',
        'imagen_gbl',
    ];

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function equipos()
    {
        return $this->hasMany(Equipo::class);
    }
}
