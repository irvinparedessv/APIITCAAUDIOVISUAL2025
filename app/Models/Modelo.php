<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Modelo extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'marca_id',
        'imagen_normal',
        'imagen_gbl',
        'is_deleted'
    ];

    // Marca del modelo
    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    // Equipos físicos asociados a este modelo
    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    /**
     * Relación con TipoEquipo a través de la tabla equipos
     * (Ya que tipo_equipo_id está en equipos, no en modelos)
     */
    public function tipoEquipo(): HasOneThrough
    {
        return $this->hasOneThrough(
            TipoEquipo::class,
            Equipo::class,
            'modelo_id',       // Foreign key on equipos table
            'id',             // Foreign key on tipo_equipos table
            'id',             // Local key on modelos table
            'tipo_equipo_id'  // Local key on equipos table
        );
    }

    // Accesorios que puede tener este modelo (solo si este modelo es un Equipo)
    public function accesorios(): BelongsToMany
    {
        return $this->belongsToMany(
            Modelo::class,
            'modelo_accesorios',
            'modelo_equipo_id',
            'modelo_insumo_id'
        )->withTimestamps();
    }

    // Modelos de equipo que usan este modelo como accesorio
    public function asociadoA(): BelongsToMany
    {
        return $this->belongsToMany(
            Modelo::class,
            'modelo_accesorios',
            'modelo_insumo_id',
            'modelo_equipo_id'
        )->withTimestamps();
    }

    /**
     * Métodos de conveniencia para verificar el tipo
     */
    public function esEquipo(): bool
    {
        return $this->tipoEquipo && 
               $this->tipoEquipo->categoria_id == 1; // Ajusta según tu ID para Equipos
    }

    public function esInsumo(): bool
    {
        return $this->equipos()->where('es_componente', true)->exists();
    }
}