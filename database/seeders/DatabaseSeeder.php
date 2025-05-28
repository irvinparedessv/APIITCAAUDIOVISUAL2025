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
            TipoReservaSeeder::class,
            UsuarioSeeder::class,
            AulaSeeder::class,
            TipoEquipoSeeder::class,
            EquipoSeeder::class,
            ReservaEquipoSeeder::class,
        ]);

    }
}
