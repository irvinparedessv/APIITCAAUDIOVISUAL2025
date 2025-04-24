<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Equipo;

class EquipoSeeder extends Seeder
{
    public function run(): void
    {
        Equipo::create([
            'nombre' => 'Servidor Dell PowerEdge',
            'descripcion' => 'Servidor de alto rendimiento para centros de datos',
            'estado' => true,  // Cambiar 'activo' por true
            'cantidad' => 3
        ]);

        Equipo::create([
            'nombre' => 'Router Cisco',
            'descripcion' => 'Router empresarial para redes grandes',
            'estado' => false, // Cambiar 'mantenimiento' por false
            'cantidad' => 2
        ]);

        Equipo::create([
            'nombre' => 'Laptop HP EliteBook',
            'descripcion' => 'Laptop liviana ideal para movilidad',
            'estado' => true,  // Cambiar 'activo' por true
            'cantidad' => 10
        ]);
    }
}
