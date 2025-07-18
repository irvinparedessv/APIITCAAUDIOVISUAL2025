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
        'tipo_reserva_id',   // agregado aquí
        'numero_serie',
        'cantidad',
        'vida_util',
        'detalles',
        'fecha_adquisicion',
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

    // --- Accesor para imagen del modelo ---
    public function getImagenUrlAttribute()
    {
        return $this->modelo && $this->modelo->imagen_local
            ? asset('storage/modelos/' . $this->modelo->imagen_local)
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
    public function disponibilidadPorRango(\Carbon\CarbonInterface $inicio, \Carbon\CarbonInterface $fin, $reservaExcluidaId = null)
    {
        $reservas = $this->reservas()
            ->where(function ($query) use ($inicio, $fin) {
                $query->where(function ($q) use ($inicio, $fin) {
                    $q->where('fecha_reserva', '<', $fin)
                        ->where('fecha_entrega', '>', $inicio);
                });
            })
            ->whereIn('estado', ['Pendiente', 'Aprobado']);

        if ($reservaExcluidaId) {
            $reservas->where('reserva_equipos.id', '!=', $reservaExcluidaId);
        }

        $reservas = $reservas->get();

        $cantidadEnReserva = $reservas->where('estado', 'Pendiente')->sum('pivot.cantidad');
        $cantidadEntregada = $reservas->where('estado', 'Aprobado')->sum('pivot.cantidad');

        $cantidadDisponible = max(0, $this->cantidad - ($cantidadEnReserva + $cantidadEntregada));

        return [
            'cantidad_total' => $this->cantidad,
            'cantidad_disponible' => $cantidadDisponible,
            'cantidad_en_reserva' => $cantidadEnReserva,
            'cantidad_entregada' => $cantidadEntregada,
        ];
    }

}
