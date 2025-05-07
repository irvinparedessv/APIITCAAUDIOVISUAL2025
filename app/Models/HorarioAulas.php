<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HorarioAulas extends Model
{
    use HasFactory;

    protected $table = 'horarios_aulas';

    protected $fillable = [
        'aula_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'days',
    ];

    protected $casts = [
        'days' => 'array',
    ];

    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }
}
