<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ModeloAccesorio extends Model
{
    use HasFactory;

    protected $table = 'modelo_accesorios'; // nombre explícito de la tabla

    protected $fillable = [
        'modelo_equipo_id',
        'modelo_insumo_id',
    ];

    public $timestamps = true;

    // Si deseas agregar relaciones inversas, puedes hacer algo como:

    public function modeloEquipo()
    {
        return $this->belongsTo(Modelo::class, 'modelo_equipo_id');
    }

    public function modeloInsumo()
    {
        return $this->belongsTo(Modelo::class, 'modelo_insumo_id');
    }
}
