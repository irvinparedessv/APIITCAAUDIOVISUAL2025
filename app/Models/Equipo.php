<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Equipo extends Model
{
    /** @use HasFactory<\Database\Factories\EquipoFactory> */
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
        'cantidad',
        'is_deleted',
        'tipo_equipo_id',
        'tipo_reserva_id',
        'imagen'
    ];
    public function getImagenUrlAttribute()
    {
        return $this->imagen
            ? asset('storage/equipos/' . $this->imagen)
            : asset('storage/equipos/default.png');
    }

    // Relación con TipoEquipo
    public function tipoEquipo()
    {
        return $this->belongsTo(TipoEquipo::class);
    }

    public function tipoReserva()
    {
        return $this->belongsTo(TipoReserva::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('is_deleted', false)
            ->whereHas('tipoEquipo', function ($q) {
                $q->where('is_deleted', false);
            });
    }

    public function reservas()
    {
        return $this->belongsToMany(ReservaEquipo::class, 'equipo_reserva', 'equipo_id', 'reserva_equipo_id')
            ->withPivot('cantidad');
    }
    public static function obtenerEquiposActivosConTipoReserva()
    {
        return self::activos()
            ->with(['tipoReserva'])
            ->get()
            ->map(function ($equipo) {
                return [
                    'nombre' => $equipo->nombre,
                    'descripcion' => $equipo->descripcion,
                    'tipo_evento' => $equipo->tipoReserva ? $equipo->tipoReserva->nombre : null,
                ];
            });
    }

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

        // Excluir una reserva específica si se pasa como parámetro
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
