<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Notification;

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
    'estado',
    'change_password',
    'image',
    'is_deleted',
    'confirmation_token',
    'email_verified_at',
    'dark_mode', // 👈 Agregar esto
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
            'dark_mode' => 'boolean', // 👈 Agregar esto
        ];
    }

    protected $with = ['role']; // Carga automática de la relación con Role

    // Agregar image_url como campo adicional en la respuesta JSON
    protected $appends = ['image_url'];

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
            default => 'inactivo',
        };
    }

    // Accesor para obtener la URL pública de la imagen
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }
    public function aulasEncargadas()
    {
        return $this->belongsToMany(Aula::class, 'aula_user');
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable')->whereNull('deleted_at');
    }
    public function scopeEncargadosAula($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->where('nombre', 'EspacioEncargado');
        });
    }
}
