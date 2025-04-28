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
        ]);

        TipoEquipo::create([
            'nombre' => 'Router',
        ]);

        TipoEquipo::create([
            'nombre' => 'Laptop',
        ]);

        TipoEquipo::create([
            'nombre' => 'Switch',
        ]);
    }
}
