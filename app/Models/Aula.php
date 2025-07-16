<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aula extends Model
{
    /** @use HasFactory<\Database\Factories\AulaFactory> */
    use HasFactory;
    protected $fillable = ['name'];

    public function imagenes()
    {
        return $this->hasMany(ImagenesAula::class);
    }
    public function users()
    {
        return $this->belongsToMany(User::class, 'aula_user');
    }
    public function horarios()
    {
        return $this->hasMany(HorarioAulas::class);
    }

    public function primeraImagen()
    {
        return $this->hasOne(ImagenesAula::class, 'aula_id')->orderBy('id');
    }
    public function encargados()
    {
        return $this->belongsToMany(User::class, 'aula_user');
    }
    public function reservas()
    {
        return $this->hasMany(ReservaAula::class, 'aula_id');
    }

    /**
     * Devuelve aulas disponibles con bloques que coinciden con una fecha dada.
     *
     * @param string $fecha Formato 'Y-m-d'
     * @return \Illuminate\Support\Collection
     */
    public static function obtenerAulasConBloquesPorFecha($fecha)
    {
        return self::whereHas('horarios', function ($q) use ($fecha) {
            $q->whereDate('start_date', '<=', $fecha)
                ->whereDate('end_date', '>=', $fecha);
        })
            ->with(['reservas.bloques' => function ($q) use ($fecha) {
                $q->whereDate('fecha_inicio', '<=', $fecha)
                    ->whereDate('fecha_fin', '>=', $fecha);
            }])
            ->get()
            ->map(function ($aula) {
                return [
                    'nombre' => $aula->name,
                    'bloques' => $aula->reservas->flatMap(function ($reserva) {
                        return $reserva->bloques->map(function ($bloque) {
                            return [
                                'fecha_inicio' => $bloque->fecha_inicio,
                                'fecha_fin' => $bloque->fecha_fin,
                            ];
                        });
                    })->values(),
                ];
            });
    }
}
