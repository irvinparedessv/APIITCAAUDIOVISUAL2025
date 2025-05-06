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
        'estado',     // 1 = activo, 0 = inactivo, 3 = pendiente
        'image',
        'is_deleted',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_deleted' => 'boolean',
        'estado' => 'integer',
    ];

    protected $with = ['role']; // Carga automática de la relación con Role

    // Relación con Role
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Accesor para mostrar el estado como texto legible
    public function getEstadoTextoAttribute()
    {
        return match ($this->attributes['estado']) {
            1 => 'activo',
            0 => 'inactivo',
            3 => 'pendiente',
            default => 'pendiente',
        };
    }

    // Mutador para establecer el estado a partir de texto
    public function setEstadoAttribute($value)
    {
        if (is_string($value)) {
            $this->attributes['estado'] = match (strtolower($value)) {
                'activo' => 1,
                'inactivo' => 0,
                'pendiente' => 3,
                default => 3,
            };
        } elseif (in_array($value, [0, 1, 3])) {
            $this->attributes['estado'] = $value;
        } else {
            $this->attributes['estado'] = 3; // Por defecto a pendiente
        }
    }
}
