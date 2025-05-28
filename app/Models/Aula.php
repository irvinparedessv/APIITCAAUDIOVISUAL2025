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

    public function horarios()
    {
        return $this->hasMany(HorarioAulas::class);
    }

    public function primeraImagen()
    {
        return $this->hasOne(ImagenesAula::class, 'aula_id')->orderBy('id');
    }
}
