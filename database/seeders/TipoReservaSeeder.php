<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoReservaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tipo_reservas')->insert([
            ['nombre' => 'Evento'],
            //['nombre' => 'Reunión'],
            ['nombre' => 'Clase'],
        ]);
    }
}
