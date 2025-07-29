<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aula extends Model
{
    /** @use HasFactory<\Database\Factories\AulaFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'path_modelo',
        'capacidad_maxima',
        'descripcion',
        'escala'
    ];

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
     * Devuelve aulas disponibles con bloques y horarios que coinciden con una fecha dada.
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
            ->with([
                'reservas' => function ($q) {
                    $q->whereIn('estado', ['Pendiente', 'Aprobado']);
                },
                'reservas.bloques' => function ($q) use ($fecha) {
                    $q->whereDate('fecha_inicio', '<=', $fecha)
                        ->whereDate('fecha_fin', '>=', $fecha);
                },
                'horarios' => function ($q) use ($fecha) {
                    $q->whereDate('start_date', '<=', $fecha)
                        ->whereDate('end_date', '>=', $fecha);
                }
            ])
            ->get()
            ->map(function ($aula) {
                return [
                    'nombre' => $aula->name,
                    'bloques' => $aula->reservas->flatMap(function ($reserva) {
                        return $reserva->bloques->map(function ($bloque) use ($reserva) {
                            return [
                                'fecha_inicio' => $bloque->fecha_inicio,
                                'fecha_fin'    => $bloque->fecha_fin,
                                'hora_inicio'  => $bloque->hora_inicio,
                                'hora_fin'     => $bloque->hora_fin,
                                'estado'       => $bloque->estado ?? $reserva->estado,
                            ];
                        });
                    })->values(),
                    'horarios' => $aula->horarios->map(function ($horario) {
                        return [
                            'start_date'  => $horario->start_date,
                            'end_date'    => $horario->end_date,
                            'hora_inicio' => $horario->hora_inicio,
                            'hora_fin'    => $horario->hora_fin,
                        ];
                    })->values(),
                ];
            });
    }
}
