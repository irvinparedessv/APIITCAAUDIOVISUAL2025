<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion', 'is_deleted'];

    protected $casts = [
        'is_deleted' => 'boolean',
    ];
    protected $table = 'ubicaciones';
}
