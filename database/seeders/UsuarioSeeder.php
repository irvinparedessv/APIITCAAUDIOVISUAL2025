<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Usuario administrador
        User::create([
            'first_name'        => 'Admin',
            'last_name'         => 'Principal',
            'email'             => 'admin@correo.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('123'), // Siempre encriptar contraseñas
            'estado'            => 1, // Activo
            'is_deleted'        => false,
            'remember_token'    => null,
            'role_id'           => 1, // Administrador
            'phone'             => null,
            'address'           => null,
            'image'             => null,
            'dark_mode'         => false, // 👈 Nuevo campo
        ]);

        // Usuario encargado
        User::create([
            'first_name'        => 'Encargado',
            'last_name'         => 'Ejemplo',
            'email'             => 'encargado@correo.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('123'),
            'estado'            => 1,
            'is_deleted'        => false,
            'remember_token'    => null,
            'role_id'           => 2, // Encargado
            'phone'             => null,
            'address'           => null,
            'image'             => null,
            'dark_mode'         => false,
        ]);

        // Usuario prestamista
        User::create([
            'first_name'        => 'Prestamista',
            'last_name'         => 'Ejemplo',
            'email'             => 'prestamista@correo.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('123'),
            'estado'            => 1,
            'is_deleted'        => false,
            'remember_token'    => null,
            'role_id'           => 3, // Prestamista
            'phone'             => null,
            'address'           => null,
            'image'             => null,
            'dark_mode'         => false,
        ]);

        User::create([
            'first_name'        => 'Prestamista2',
            'last_name'         => 'Ejemplo2',
            'email'             => 'prestamista2@correo.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('123'),
            'estado'            => 1,
            'is_deleted'        => false,
            'remember_token'    => null,
            'role_id'           => 3, // Prestamista
            'phone'             => null,
            'address'           => null,
            'image'             => null,
            'dark_mode'         => false,
        ]);

        User::create([
            'first_name'        => 'Prestamista3',
            'last_name'         => 'Ejemplo3',
            'email'             => 'prestamista3@correo.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('123'),
            'estado'            => 1,
            'is_deleted'        => false,
            'remember_token'    => null,
            'role_id'           => 3, // Prestamista
            'phone'             => null,
            'address'           => null,
            'image'             => null,
            'dark_mode'         => false,
        ]);
    }
}
