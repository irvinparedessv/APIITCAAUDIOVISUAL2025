<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Crear roles de ejemplo
        Role::create(['nombre' => 'Administrador']);
        Role::create(['nombre' => 'Encargado']);
        Role::create(['nombre' => 'Prestamista']);
    }
}
