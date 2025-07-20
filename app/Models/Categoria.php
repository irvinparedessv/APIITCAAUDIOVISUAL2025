<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    protected $fillable = ['nombre', 'is_deleted'];

    protected $casts = [
        'is_deleted' => 'boolean',
    ];

    // Scope para categorías activas
    public function scopeActivas($query)
    {
        return $query->where('is_deleted', false);
    }
    public function tiposEquipo()
{
    return $this->hasMany(TipoEquipo::class, 'categoria_id');
}

}
