<?php

namespace Database\Seeders;

use App\Models\Aula;
use Illuminate\Database\Seeder;

class AulaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $aulas = [
            ['name' => 'Aula 101', 'capacidad_maxima' => 40, 'descripcion' => 'Aula estándar para clases teóricas.'],
            ['name' => 'Aula 202', 'capacidad_maxima' => 40, 'descripcion' => 'Aula con pupitres y pizarra.'],
            ['name' => 'Auditorio', 'capacidad_maxima' => 200, 'descripcion' => 'Auditorio para eventos deportivos y educativos.'],
            ['name' => 'Sala de grabación', 'capacidad_maxima' => 10, 'descripcion' => 'Sala acondicionada para grabaciones.'],
        ];

        foreach ($aulas as $data) {
            Aula::firstOrCreate(
                ['name' => $data['name']],
                [
                    'path_modelo' => 'models/room.glb',
                    'capacidad_maxima' => $data['capacidad_maxima'],
                    'descripcion' => $data['descripcion'],
                    'escala' => 0.01
                ]
            );
        }
    }
}
