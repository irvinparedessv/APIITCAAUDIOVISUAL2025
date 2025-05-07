<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoEquipo extends Model
{
    use HasFactory;

    protected $fillable = ['nombre','is_deleted'];

    // Relación con equipos
    public function equipos()
    {
        return $this->hasMany(Equipo::class);
    }
    
}
