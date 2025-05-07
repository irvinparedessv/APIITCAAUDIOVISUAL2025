<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImagenesAula extends Model
{
    use HasFactory;

    protected $table = 'imagenes_aula';

    protected $fillable = ['aula_id', 'image_path'];

    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }
}
