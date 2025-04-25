<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Equipo;


class EquipoSeeder extends Seeder
{
    public function run()
    {

        Equipo::create([
            'nombre' => 'Servidor Dell PowerEdge',
            'descripcion' => 'Servidor de alto rendimiento para centros de datos',
            'estado' => true,
            'cantidad' => 3,
            'is_deleted' => false,
            'tipo_equipo_id' => 1
        ]);

        Equipo::create([
            'nombre' => 'Router Cisco',
            'descripcion' => 'Router empresarial para redes grandes',
            'estado' => false,
            'cantidad' => 2,
            'is_deleted' => false,
            'tipo_equipo_id' => 2
        ]);

        Equipo::create([
            'nombre' => 'Laptop HP EliteBook',
            'descripcion' => 'Laptop liviana ideal para movilidad',
            'estado' => true,
            'cantidad' => 10,
            'is_deleted' => false,
            'tipo_equipo_id' => 3
        ]);
    }
}

