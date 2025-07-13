<?php

namespace Database\Seeders;

use App\Models\Ubicacion;
use Illuminate\Database\Seeder;

class UbicacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ubicaciones = [
            ['nombre' => 'F-301', 'descripcion' => 'Tercer Piso'],
            ['nombre' => 'F-102', 'descripcion' => 'Primer Piso'],
            ['nombre' => 'A-101', 'descripcion' => 'Edificio A Primer Piso'],
            ['nombre' => 'B-101', 'descripcion' => 'Primer Piso'],
        ];

        foreach ($ubicaciones as $data) {
            Ubicacion::firstOrCreate(['nombre' => $data['nombre']], [
                'descripcion' => $data['descripcion'],
            ]);
        }
    }
}
