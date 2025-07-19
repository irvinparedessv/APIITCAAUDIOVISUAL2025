<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValoresCaracteristica extends Model
{
    use HasFactory;

    protected $table = 'valores_caracteristicas';

    protected $fillable = [
        'equipo_id',
        'caracteristica_id',
        'valor',
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function caracteristica()
    {
        return $this->belongsTo(Caracteristica::class);
    }
}
