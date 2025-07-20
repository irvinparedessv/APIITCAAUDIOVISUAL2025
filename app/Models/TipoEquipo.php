<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoEquipo extends Model
{
    use HasFactory;

    protected $table = 'tipo_equipos';

    protected $fillable = ['nombre', 'categoria_id', 'is_deleted'];


    protected $casts = [
        'is_deleted' => 'boolean',
    ];

    public function equipos()
    {
        return $this->hasMany(Equipo::class, 'tipo_equipo_id');
    }

    public function categoria()
{
    return $this->belongsTo(Categoria::class, 'categoria_id');
}


    public function caracteristicas()
    {
        return $this->belongsToMany(Caracteristica::class, 'caracteristicas_tipo_equipo');
    }
}
