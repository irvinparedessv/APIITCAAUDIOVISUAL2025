<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Equipo;
use App\Models\Estado;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\TipoEquipo;
use App\Models\TipoReserva;
use App\Models\Caracteristica;
use App\Models\ValoresCaracteristica;
use Illuminate\Database\Seeder;

class Equipos3DSeeder extends Seeder
{
    public function run()
    {
        // -- Categorías --
        $categoriaEquipo = Categoria::updateOrCreate(
            ['nombre' => 'Equipo'],
            ['is_deleted' => false]
        );
        $categoriaInsumo = Categoria::updateOrCreate(
            ['nombre' => 'Insumo'],
            ['is_deleted' => false]
        );

        // -- Tipos de equipo 3D --
        $tipos = [
            ['nombre' => 'Laptop', 'categoria_id' => $categoriaEquipo->id, 'is_deleted' => false],
            ['nombre' => 'Micrófono', 'categoria_id' => $categoriaEquipo->id, 'is_deleted' => false],
            ['nombre' => 'Parlante', 'categoria_id' => $categoriaEquipo->id, 'is_deleted' => false],
            ['nombre' => 'Proyector', 'categoria_id' => $categoriaEquipo->id, 'is_deleted' => false],
            ['nombre' => 'Mouse', 'categoria_id' => $categoriaEquipo->id, 'is_deleted' => false],
        ];
        foreach ($tipos as $tipo) {
            TipoEquipo::updateOrCreate(['nombre' => $tipo['nombre']], $tipo);
        }

        // -- Marcas (puedes agregar marcas específicas 3D o usar genéricas) --
        $marcas = ['Generic 3D Models'];
        foreach ($marcas as $nombre) {
            Marca::updateOrCreate(['nombre' => $nombre], ['is_deleted' => false]);
        }
        $marcaGen3D = Marca::where('nombre', 'Generic 3D Models')->first();

        // -- Modelos 3D con solo imagen glb --
        $modelos3D = [
            ['nombre' => 'laptop_low-poly', 'marca_id' => $marcaGen3D->id, 'imagen_gbl' => 'laptop_low-poly.glb', 'is_deleted' => false],
            ['nombre' => 'laptop_alienpredator', 'marca_id' => $marcaGen3D->id, 'imagen_gbl' => 'laptop_alienpredator.glb', 'is_deleted' => false],
            ['nombre' => 'laptop_windows_menu', 'marca_id' => $marcaGen3D->id, 'imagen_gbl' => 'laptop_windows_menu.glb', 'is_deleted' => false],
            ['nombre' => 'laptop_dell_g7', 'marca_id' => $marcaGen3D->id, 'imagen_gbl' => 'laptop_dell_g7.glb', 'is_deleted' => false],
            ['nombre' => 'laptop_acer', 'marca_id' => $marcaGen3D->id, 'imagen_gbl' => 'laptop_acer.glb', 'is_deleted' => false],

            ['nombre' => 'microfono', 'marca_id' => $marcaGen3D->id, 'imagen_gbl' => 'microfono.glb', 'is_deleted' => false],
            ['nombre' => 'razer_seiren_x', 'marca_id' => $marcaGen3D->id, 'imagen_gbl' => 'razer_seiren_x.glb', 'is_deleted' => false],
            ['nombre' => 'skp_pro_40_microphone', 'marca_id' => $marcaGen3D->id, 'imagen_gbl' => 'skp_pro_40_microphone.glb', 'is_deleted' => false],

            ['nombre' => 'jbl_charge_3_speaker', 'marca_id' => $marcaGen3D->id, 'imagen_gbl' => 'jbl_charge_3_speaker.glb', 'is_deleted' => false],
            ['nombre' => 'logitech_speaker', 'marca_id' => $marcaGen3D->id, 'imagen_gbl' => 'logitech_speaker.glb', 'is_deleted' => false],
            ['nombre' => 'bluetooth_speaker', 'marca_id' => $marcaGen3D->id, 'imagen_gbl' => 'bluetooth_speaker.glb', 'is_deleted' => false],
            ['nombre' => 'jbl_speaker', 'marca_id' => $marcaGen3D->id, 'imagen_gbl' => 'jbl_speaker.glb', 'is_deleted' => false],

            ['nombre' => 'video_projector', 'marca_id' => $marcaGen3D->id, 'imagen_gbl' => 'video_projector.glb', 'is_deleted' => false],
            ['nombre' => 'generic_white_digital_projector', 'marca_id' => $marcaGen3D->id, 'imagen_gbl' => 'generic_white_digital_projector.glb', 'is_deleted' => false],

            ['nombre' => 'pc_mouse_type-r', 'marca_id' => $marcaGen3D->id, 'imagen_gbl' => 'pc_mouse_type-r.glb', 'is_deleted' => false],
            ['nombre' => 'computer_mouse', 'marca_id' => $marcaGen3D->id, 'imagen_gbl' => 'computer_mouse.glb', 'is_deleted' => false],
            ['nombre' => 'computer_mouse_a4tech_bloody_v7', 'marca_id' => $marcaGen3D->id, 'imagen_gbl' => 'computer_mouse_a4tech_bloody_v7.glb', 'is_deleted' => false],
        ];
        foreach ($modelos3D as $mod) {
            Modelo::updateOrCreate(
                ['nombre' => $mod['nombre'], 'marca_id' => $mod['marca_id']],
                [
                    'imagen_gbl' => $mod['imagen_gbl'],
                    'is_deleted' => $mod['is_deleted']
                ]
            );
        }

        // -- Estados --
        $estados = ['Disponible', 'En reparación', 'En reposo', 'Dañado'];
        foreach ($estados as $nombre) {
            Estado::updateOrCreate(['nombre' => $nombre], ['is_deleted' => false]);
        }
        $estadoDisponible = Estado::where('nombre', 'Disponible')->first();

        // -- Obtener tipos equipo para asignar a equipos 3D --
        $tipoLaptop = TipoEquipo::where('nombre', 'Laptop')->first();
        $tipoMicrofono = TipoEquipo::where('nombre', 'Micrófono')->first();
        $tipoParlante = TipoEquipo::where('nombre', 'Parlante')->first();
        $tipoProyector = TipoEquipo::where('nombre', 'Proyector')->first();
        $tipoMouse = TipoEquipo::where('nombre', 'Mouse')->first();

        // -- Obtener modelos 3D --
        $modelos = [];
        foreach ($modelos3D as $mod) {
            $modelos[$mod['nombre']] = Modelo::where('nombre', $mod['nombre'])->first();
        }

        // -- Características --
        $caracteristicasData = [
            ['nombre' => 'Peso (kg)', 'tipo_dato' => 'decimal'],
            ['nombre' => 'Color', 'tipo_dato' => 'string'],
            ['nombre' => 'Resolución (px)', 'tipo_dato' => 'integer'],
        ];
        $caracteristicas = [];
        foreach ($caracteristicasData as $carac) {
            $caracteristicas[$carac['nombre']] = Caracteristica::updateOrCreate(
                ['nombre' => $carac['nombre']],
                ['tipo_dato' => $carac['tipo_dato'], 'is_deleted' => false]
            );
        }

        // -- Asociar características a tipos de equipo --
        $tipoLaptop->caracteristicas()->syncWithoutDetaching([
            $caracteristicas['Peso (kg)']->id,
            $caracteristicas['Color']->id,
            $caracteristicas['Resolución (px)']->id,
        ]);
        $tipoMicrofono->caracteristicas()->syncWithoutDetaching([
            $caracteristicas['Peso (kg)']->id,
            $caracteristicas['Color']->id,
        ]);
        $tipoParlante->caracteristicas()->syncWithoutDetaching([
            $caracteristicas['Peso (kg)']->id,
            $caracteristicas['Color']->id,
        ]);
        $tipoProyector->caracteristicas()->syncWithoutDetaching([
            $caracteristicas['Peso (kg)']->id,
            $caracteristicas['Color']->id,
        ]);
        $tipoMouse->caracteristicas()->syncWithoutDetaching([
            $caracteristicas['Peso (kg)']->id,
            $caracteristicas['Color']->id,
        ]);

        // -- Tipo reserva --
        $tipoReservaId = TipoReserva::first()?->id ?? null;

        // -- Equipos 3D --
        $equipos3D = [
            [
                'tipo_equipo_id' => $tipoLaptop->id,
                'modelo_id' => $modelos['laptop_low-poly']->id,
                'estado_id' => $estadoDisponible->id,
                'tipo_reserva_id' => $tipoReservaId,
                'numero_serie' => '3D-LAPTOP-001',
                'vida_util' => 3,
                'cantidad' => 1,
                'detalles' => 'Laptop 3D low poly modelo',
                'fecha_adquisicion' => '2023-05-10',
                'is_deleted' => false,
                'caracteristicas' => [
                    'Peso (kg)' => '1.6',
                    'Color' => 'Negro',
                    'Resolución (px)' => 1920,
                ],
            ],
            [
                'tipo_equipo_id' => $tipoMicrofono->id,
                'modelo_id' => $modelos['razer_seiren_x']->id,
                'estado_id' => $estadoDisponible->id,
                'tipo_reserva_id' => $tipoReservaId,
                'numero_serie' => '3D-MIC-002',
                'vida_util' => 4,
                'cantidad' => 1,
                'detalles' => 'Micrófono 3D Razer Seiren X',
                'fecha_adquisicion' => '2023-05-12',
                'is_deleted' => false,
                'caracteristicas' => [
                    'Peso (kg)' => '0.7',
                    'Color' => 'Negro',
                    'Resolución (px)' => 0,
                ],
            ],
            [
                'tipo_equipo_id' => $tipoParlante->id,
                'modelo_id' => $modelos['jbl_charge_3_speaker']->id,
                'estado_id' => $estadoDisponible->id,
                'tipo_reserva_id' => $tipoReservaId,
                'numero_serie' => '3D-SPK-003',
                'vida_util' => 5,
                'cantidad' => 1,
                'detalles' => 'Parlante 3D JBL Charge 3',
                'fecha_adquisicion' => '2023-05-15',
                'is_deleted' => false,
                'caracteristicas' => [
                    'Peso (kg)' => '1.3',
                    'Color' => 'Negro',
                    'Resolución (px)' => 0,
                ],
            ],
            [
                'tipo_equipo_id' => $tipoProyector->id,
                'modelo_id' => $modelos['video_projector']->id,
                'estado_id' => $estadoDisponible->id,
                'tipo_reserva_id' => $tipoReservaId,
                'numero_serie' => '3D-PROJ-004',
                'vida_util' => 6,
                'cantidad' => 1,
                'detalles' => 'Proyector 3D genérico',
                'fecha_adquisicion' => '2023-05-18',
                'is_deleted' => false,
                'caracteristicas' => [
                    'Peso (kg)' => '3.5',
                    'Color' => 'Blanco',
                    'Resolución (px)' => 1080,
                ],
            ],
            [
                'tipo_equipo_id' => $tipoMouse->id,
                'modelo_id' => $modelos['pc_mouse_type-r']->id,
                'estado_id' => $estadoDisponible->id,
                'tipo_reserva_id' => $tipoReservaId,
                'numero_serie' => '3D-MOUSE-005',
                'vida_util' => 2,
                'cantidad' => 1,
                'detalles' => 'Mouse 3D tipo R',
                'fecha_adquisicion' => '2023-05-20',
                'is_deleted' => false,
                'caracteristicas' => [
                    'Peso (kg)' => '0.25',
                    'Color' => 'Negro',
                    'Resolución (px)' => 0,
                ],
            ],
        ];

        foreach ($equipos3D as $data) {
            $caracValues = $data['caracteristicas'];
            unset($data['caracteristicas']);

            $equipo = Equipo::updateOrCreate(
                ['numero_serie' => $data['numero_serie']],
                $data
            );

            foreach ($caracValues as $nombreCarac => $valor) {
                $carac = $caracteristicas[$nombreCarac] ?? null;
                if ($carac) {
                    ValoresCaracteristica::updateOrCreate(
                        ['equipo_id' => $equipo->id, 'caracteristica_id' => $carac->id],
                        ['valor' => $valor]
                    );
                }
            }
        }
    }
}
