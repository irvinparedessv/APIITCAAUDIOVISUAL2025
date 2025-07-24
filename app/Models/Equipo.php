<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_equipo_id',
        'marca_id',
        'modelo_id',
        'estado_id',
        'tipo_reserva_id',
        'numero_serie',
        'vida_util',
        'detalles',
        'fecha_adquisicion',
        'comentario',
        'es_componente',   
        'is_deleted',
        'cantidad',       
    ];

    protected $dates = ['fecha_adquisicion'];

    // Relaciones existentes ...

    public function tipoEquipo()
    {
        return $this->belongsTo(TipoEquipo::class, 'tipo_equipo_id');
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function modelo()
    {
        return $this->belongsTo(Modelo::class);
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }

    public function tipoReserva()
    {
        return $this->belongsTo(TipoReserva::class, 'tipo_reserva_id');
    }

    public function reservas()
    {
        return $this->belongsToMany(ReservaEquipo::class, 'equipo_reserva', 'equipo_id', 'reserva_equipo_id')
            ->withPivot('cantidad');
    }

    // Método para adjuntar características fácilmente
public function agregarCaracteristicas(array $caracteristicas)
{
    foreach ($caracteristicas as $caract) {
        $this->valoresCaracteristicas()->updateOrCreate(
            ['caracteristica_id' => $caract['caracteristica_id']],
            ['valor' => $caract['valor']]
        );
    }
}

    public function valoresCaracteristicas()
    {
        return $this->hasMany(ValoresCaracteristica::class, 'equipo_id');
    }

    // Nuevas relaciones con tabla pivote equipo_accesorio

    /**
     * Insumos (componentes) asociados a este equipo
     */
    public function insumos()
    {
        return $this->belongsToMany(
            Equipo::class,
            'equipo_accesorio',
            'equipo_id',
            'insumo_id'
        )->withTimestamps();
    }

    /**
     * Equipos donde este equipo (insumo) está asignado
     */
    public function equiposDondeEsInsumo()
    {
        return $this->belongsToMany(
            Equipo::class,
            'equipo_accesorio',
            'insumo_id',
            'equipo_id'
        )->withTimestamps();
    }

      // NUEVAS RELACIONES para mantenimiento y futuro mantenimiento

    /**
     * Mantenimientos realizados a este equipo
     */
    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class);
    }

    /**
     * Mantenimientos futuros programados para este equipo
     */
    public function futurosMantenimientos()
    {
        return $this->hasMany(FuturoMantenimiento::class);
    }


    // Método para filtrar solo insumos (componentes)
    public function scopeComponentes($query)
    {
        return $query->where('es_componente', true);
    }

    // Método para filtrar solo equipos que NO son insumos
    public function scopeEquipos($query)
    {
        return $query->where('es_componente', false);
    }

    // El resto igual...

    public function getImagenUrlAttribute()
    {
        return $this->modelo && $this->modelo->imagen_normal
            ? asset('storage/modelos/' . $this->modelo->imagen_normal)
            : asset('storage/modelos/default.png');
    }

    public function scopeActivos($query)
    {
        return $query->whereHas('tipoEquipo', function ($q) {
            $q->where('is_deleted', false);
        });
    }

    public static function obtenerEquiposActivosConTipoReserva()
    {
        return self::activos()
            ->with(['tipoEquipo', 'tipoReserva', 'modelo.marca'])
            ->get()
            ->map(function ($equipo) {
                return [
                    'nombre' => $equipo->modelo->nombre ?? 'Desconocido',
                    'marca' => $equipo->modelo->marca->nombre ?? 'Sin marca',
                    'tipo_equipo' => $equipo->tipoEquipo->nombre ?? null,
                    'tipo_reserva' => $equipo->tipoReserva->nombre ?? null,
                    'numero_serie' => $equipo->numero_serie,
                    'cantidad' => $equipo->cantidad,
                    'estado' => $equipo->estado->nombre ?? 'N/A',
                ];
            });
    }

    // --- Disponibilidad por rango ---
    public function disponibilidadPorRango($start, $end)
    {
        // Revisar si el equipo está reservado en ese rango
        $reservas = $this->reservas()
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('fecha_reserva', [$start, $end])
                    ->orWhereBetween('fecha_entrega', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('fecha_reserva', '<=', $start)
                            ->where('fecha_entrega', '>=', $end);
                    });
            })->count();

        $disponible = $reservas === 0;

        return [
            'cantidad_total' => 1,
            'cantidad_disponible' => $disponible ? 1 : 0,
            'cantidad_en_reserva' => $reservas,
            'cantidad_entregada' => 0,
        ];
    }
}
