<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role_id',
        'phone',
        'address',
        'estado',      // Campo para el estado (activo/inactivo)
        'image', // Campo para la foto
        'is_deleted',  // Campo para eliminar lógicamente
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $with = ['role']; // Cargar 'role' automáticamente

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
