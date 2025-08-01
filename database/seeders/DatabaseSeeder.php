<?php

namespace Database\Seeders;

use App\Models\ReservaAulaBloque;
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
            Equipos3DSeeder::class,
            InsumosSeeder::class,
            //EquipoSeeder::class,
            ReservaEquipoSeeder::class,
            HorarioAulasSeeder::class,
            //ImagenesAulaSeeder::class,
            UsuariosEncargadosSeeder::class,
            //ReservaAulaSeeder::class,
            //ReservaAulaBloqueSeeder::class,

            // Seeders de mantenimiento
            TipoMantenimientoSeeder::class,
            MantenimientoSeeder::class,
            FuturoMantenimientoSeeder::class,
        ]);
    }
}
