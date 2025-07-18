<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Estado extends Model
{
    use HasFactory;

    protected $fillable = ['nombre'];

    public function equipos()
    {
        return $this->hasMany(Equipo::class);
    }
}
