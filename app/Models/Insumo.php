<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Insumo extends Model
{
    use HasFactory;

    protected $table = 'insumos';

    protected $fillable = [
        'nombre',
        'modelo_id',
        'tipo_equipo_id',
        'is_deleted',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
    ];

    // Relación con el modelo
    public function modelo()
    {
        return $this->belongsTo(Modelo::class);
    }

    // Relación con tipo de equipo (ej: USB, cable, etc.)
    public function tipoEquipo()
    {
        return $this->belongsTo(TipoEquipo::class);
    }

    // Relación indirecta con marca (a través de modelo)
    public function marca()
    {
        return $this->hasOneThrough(
            Marca::class,
            Modelo::class,
            'id',           // Clave foránea en modelo (modelo_id)
            'id',           // Clave foránea en marca (marca_id)
            'modelo_id',    // Clave local en insumos
            'marca_id'      // Clave local en modelo
        );
    }

    // Relación indirecta con categoría (a través de tipo de equipo)
    public function categoria()
    {
        return $this->hasOneThrough(
            Categoria::class,
            TipoEquipo::class,
            'id',             // clave en tipo_equipo
            'id',             // clave en categoria
            'tipo_equipo_id', // en insumo
            'categoria_id'    // en tipo_equipo
        );
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

     public function tipoReserva()
    {
        return $this->belongsTo(TipoReserva::class, 'tipo_reserva_id');
    }

    // En app/Models/Equipo.php y app/Models/Insumo.php
    public function scopeActivos($query)
    {
        return $query->where('is_deleted', false);
    }
}
