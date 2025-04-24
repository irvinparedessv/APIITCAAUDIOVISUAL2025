<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Crear un usuario administrador
        User::create([
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => 'adminpassword',  // Contraseña sin hash para prueba
            'estado' => true,
            'is_deleted' => false,
            'remember_token' => null,
            'role_id' => 1, // ID de 'Administrador'
        ]);

        // Crear un usuario encargado
        User::create([
            'name' => 'Encargado Ejemplo',
            'email' => 'encargado@example.com',
            'email_verified_at' => now(),
            'password' => 'encargadopassword',  // Contraseña sin hash para prueba
            'estado' => true,
            'is_deleted' => false,
            'remember_token' => null,
            'role_id' => 2, // ID de 'Encargado'
        ]);

        // Crear un usuario prestamista
        User::create([
            'name' => 'Prestamista Ejemplo',
            'email' => 'prestamista@example.com',
            'email_verified_at' => now(),
            'password' => 'prestamistapassword',  // Contraseña sin hash para prueba
            'estado' => true,
            'is_deleted' => false,
            'remember_token' => null,
            'role_id' => 3, // ID de 'Prestamista'
        ]);
    }
}
