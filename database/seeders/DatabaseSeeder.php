<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Llamar al seeder de Roles primero
        $this->call([
            RoleSeeder::class,
        ]);

        // Luego, llamar al seeder de Usuarios
        $this->call([
            UsuarioSeeder::class,
        ]);

        $this->call(TipoEquipoSeeder::class);
        $this->call(EquipoSeeder::class);
        
    }
}
