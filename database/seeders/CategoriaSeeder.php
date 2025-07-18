<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;

class CategoriaSeeder extends Seeder
{
    public function run()
    {
        Categoria::updateOrCreate(['nombre' => 'Equipo'], ['descripcion' => 'Equipos tecnológicos']);
        Categoria::updateOrCreate(['nombre' => 'Insumo'], ['descripcion' => 'Materiales consumibles']);
    }
}
