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
        'cantidad',
        'vida_util',
        'detalles',
        'fecha_adquisicion',
        'es_componente',   // nuevo
        'padre_id',        // nuevo
        'is_deleted',      // para borrado lógico
    ];


    protected $dates = ['fecha_adquisicion'];

    // --- Relaciones ---
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

    public function padre()
    {
        return $this->belongsTo(Equipo::class, 'padre_id');
    }

    public function componentes()
    {
        return $this->hasMany(Equipo::class, 'padre_id')->where('es_componente', true);
    }

    public function esInsumo()
    {
        return !is_null($this->cantidad) && is_null($this->numero_serie);
    }

    public function esEquipo()
    {
        return !is_null($this->numero_serie);
    }

    public function valoresCaracteristicas()
    {
        return $this->hasMany(ValoresCaracteristica::class, 'equipo_id');
    }



    // --- Accesor para imagen del modelo ---
    public function getImagenUrlAttribute()
    {
        return $this->modelo && $this->modelo->imagen_normal
            ? asset('storage/modelos/' . $this->modelo->imagen_normal)
            : asset('storage/modelos/default.png');
    }


    // --- Scope para filtrar equipos activos (no eliminados y tipo activo) ---
    public function scopeActivos($query)
    {
        return $query->whereHas('tipoEquipo', function ($q) {
            $q->where('is_deleted', false);
        });
    }

    // --- Método para devolver datos estructurados para frontend ---
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
