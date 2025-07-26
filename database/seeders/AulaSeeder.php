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
            ['name' => 'Aula 101', 'capacidad_maxima' => 30, 'descripcion' => 'Aula estándar para clases teóricas.'],
            ['name' => 'Aula 202', 'capacidad_maxima' => 25, 'descripcion' => 'Aula con equipo multimedia.'],
            ['name' => 'Auditorio', 'capacidad_maxima' => 100, 'descripcion' => 'Auditorio con tarima y sonido.'],
            ['name' => 'Sala de grabación', 'capacidad_maxima' => 10, 'descripcion' => 'Sala acondicionada para grabaciones.'],
        ];

        foreach ($aulas as $data) {
            Aula::firstOrCreate(
                ['name' => $data['name']],
                [
                    'path_modelo' => 'models/room.glb',
                    'capacidad_maxima' => $data['capacidad_maxima'],
                    'descripcion' => $data['descripcion']
                ]
            );
        }
    }
}
