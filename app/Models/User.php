<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\ResetPasswordNotification;
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
        'role_id', // Agrega role_id si no estaba
        'phone',
        'address',
        'estado',  // 1 = activo, 0 = inactivo, 3 = pendiente
        'change_password',
        'image',
        'is_deleted',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_deleted' => 'boolean',
            'estado' => 'integer',
        ];
    }

    protected $with = ['role']; // Carga automática de la relación con Role

    // Relación con Role
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
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
}
