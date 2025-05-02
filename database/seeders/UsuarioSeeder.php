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
            'first_name' => 'Admin',
            'last_name' => 'Principal',
            'email' => 'admin@correo.com',
            'email_verified_at' => now(),
            'password' => '123', 
            'estado' => true,
            'is_deleted' => false,
            'remember_token' => null,
            'role_id' => 1, // ID de 'Administrador'
            'phone' => null,
            'address' => null,
            'image' => null,
        ]);

        // Crear un usuario encargado
        User::create([
            'first_name' => 'Encargado',
            'last_name' => 'Ejemplo',
            'email' => 'encargado@correo.com',
            'email_verified_at' => now(),
            'password' => '123',  
            'estado' => true,
            'is_deleted' => false,
            'remember_token' => null,
            'role_id' => 2, // ID de 'Encargado'
            'phone' => null,
            'address' => null,
            'image' => null,
        ]);

        // Crear un usuario prestamista
        User::create([
            'first_name' => 'Prestamista',
            'last_name' => 'Ejemplo',
            'email' => 'prestamista@correo.com',
            'email_verified_at' => now(),
            'password' => '123', 
            'estado' => true,
            'is_deleted' => false,
            'remember_token' => null,
            'role_id' => 3, // ID de 'Prestamista'
            'phone' => null,
            'address' => null,
            'image' => null,
        ]);
    }
}
