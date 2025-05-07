<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoEquipo;

class TipoEquipoSeeder extends Seeder
{
    public function run()
    {
        TipoEquipo::create([
            'nombre' => 'Servidor',
            'is_deleted' => false
        ]);

        TipoEquipo::create([
            'nombre' => 'Router',
            'is_deleted' => false
        ]);

        TipoEquipo::create([
            'nombre' => 'Laptop',
            'is_deleted' => false
        ]);

        TipoEquipo::create([
            'nombre' => 'Switch',
            'is_deleted' => false
        ]);
    }
}
