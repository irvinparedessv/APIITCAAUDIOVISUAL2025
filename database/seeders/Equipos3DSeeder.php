<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class Equipos3DSeeder extends Seeder
{
    public function run()
    {
        // Simulación de tipos de equipos 3D y sus modelos descargados con repeticiones y nuevos mouses
        $tiposEquipo3D = [
            'Laptop' => [
                'modelos' => [
                    'laptop_low-poly',
                    'laptop_low-poly',  //repetido
                    'laptop_alienpredator',
                    'laptop_-_windows_menu',
                    'laptop_-_windows_menu',  //repetido
                    'laptop_dell_g7',
                    'laptop_acer'
                ]
            ],
            'Micrófono' => [
                'modelos' => [
                    'microfono',
                    'razer_seiren_x',
                    'razer_seiren_x',          // repetido para simular más unidades
                    'skp_pro_40_microphone',
                    'microfono'               // repetido
                ]
            ],
            'Parlante' => [
                'modelos' => [
                    'jbl_charge_3_speaker',
                    'logitech_speaker',
                    'bluetooth_speaker',
                    'jbl_speaker',
                    'jbl_speaker'             // repetido
                ]
            ],
            'Proyector' => [
                'modelos' => [
                    'video_projector',
                    'generic_white_digital_projector'
                ]
            ],
            'Mouse' => [
                'modelos' => [
                    'pc_mouse_type-r',
                    'computer_mouse.',
                    'computer_mouse_a4tech_bloody_v7'
                ]
            ],
        ];

        // Iterar sobre cada tipo y mostrar los modelos simulados
        foreach ($tiposEquipo3D as $tipo => $data) {
            foreach ($data['modelos'] as $modelo) {
                echo "Registrando modelo 3D: [$tipo] => $modelo.glb\n";

                // Inserción futura si es necesaria:
                /*
                Equipo::create([
                    'nombre' => $modelo,
                    'descripcion' => "$tipo 3D modelo $modelo.glb",
                    'estado' => true,
                    'cantidad' => 1,
                    'is_deleted' => false,
                    'tipo_equipo_id' => null,
                    'tipo_reserva_id' => null,
                    'imagen' => "$modelo.glb"
                ]);
                */
            }
        }
    }
}
