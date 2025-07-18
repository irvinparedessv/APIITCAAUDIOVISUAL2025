<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Marca extends Model
{
    use HasFactory;

    protected $fillable = ['nombre'];

    public function modelos()
    {
        return $this->hasMany(Modelo::class);
    }

    public function equipos()
    {
        return $this->hasMany(Equipo::class);
    }
}
