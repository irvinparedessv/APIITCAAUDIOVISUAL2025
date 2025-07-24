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

        // -- Marcas --
        $marcas = ['Generic 3D Models'];
        foreach ($marcas as $nombre) {
            Marca::updateOrCreate(['nombre' => $nombre], ['is_deleted' => false]);
        }
        $marcaGen3D = Marca::where('nombre', 'Generic 3D Models')->first();

        // -- Modelos 3D con solo imagen glb --
        $modelos3D = [
            ['nombre' => 'laptop_low-poly', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => '/models/laptop_low-poly.glb', 'is_deleted' => false],
            ['nombre' => 'laptop_alienpredator', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => '/models/laptop_alienpredator.glb', 'is_deleted' => false],
            ['nombre' => 'laptop_windows_menu', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => '/models/laptop_windows_menu.glb', 'is_deleted' => false],
            ['nombre' => 'laptop_dell_g7', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => '/models/laptop_dell_g7.glb', 'is_deleted' => false],
            ['nombre' => 'laptop_acer', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => '/models/laptop_acer.glb', 'is_deleted' => false],

            ['nombre' => 'microfono', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => '/models/microfono.glb', 'is_deleted' => false],
            ['nombre' => 'razer_seiren_x', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => '/models/razer_seiren_x.glb', 'is_deleted' => false],
            ['nombre' => 'skp_pro_40_microphone', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => '/models/skp_pro_40_microphone.glb', 'is_deleted' => false],

            ['nombre' => 'jbl_charge_3_speaker', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => '/models/jbl_charge_3_speaker.glb', 'is_deleted' => false],
            ['nombre' => 'logitech_speaker', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => '/models/logitech_speaker.glb', 'is_deleted' => false],
            ['nombre' => 'bluetooth_speaker', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => '/models/bluetooth_speaker.glb', 'is_deleted' => false],
            ['nombre' => 'jbl_speaker', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => '/models/jbl_speaker.glb', 'is_deleted' => false],

            ['nombre' => 'video_projector', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => '/models/video_projector.glb', 'is_deleted' => false],
            ['nombre' => 'generic_white_digital_projector', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => '/models/generic_white_digital_projector.glb', 'is_deleted' => false],

            ['nombre' => 'pc_mouse_type-r', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => '/models/pc_mouse_type-r.glb', 'is_deleted' => false],
            ['nombre' => 'computer_mouse', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => '/models/computer_mouse.glb', 'is_deleted' => false],
            ['nombre' => 'computer_mouse_a4tech_bloody_v7', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => '/models/computer_mouse_a4tech_bloody_v7.glb', 'is_deleted' => false],
        ];
        foreach ($modelos3D as $mod) {
            Modelo::updateOrCreate(
                ['nombre' => $mod['nombre'], 'marca_id' => $mod['marca_id']],
                [
                    'imagen_glb' => $mod['imagen_glb'],
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
        $tipoReservaId = 3;

        // -- Obtener todos los modelos ya guardados en BD --
        $modelos = [];
        foreach ($modelos3D as $mod) {
            $modelos[$mod['nombre']] = Modelo::where('nombre', $mod['nombre'])->first();
        }

        // -- Crear un equipo 3D para cada modelo 3D --
        foreach ($modelos as $nombreModelo => $modelo) {

            // Detectar tipo equipo según nombre modelo (ejemplo rápido)
            if (str_contains($nombreModelo, 'laptop')) {
                $tipoEquipo = $tipoLaptop;
            } elseif (str_contains($nombreModelo, 'microfono') || str_contains($nombreModelo, 'microphone') || str_contains($nombreModelo, 'razer')) {
                $tipoEquipo = $tipoMicrofono;
            } elseif (str_contains($nombreModelo, 'speaker')) {
                $tipoEquipo = $tipoParlante;
            } elseif (str_contains($nombreModelo, 'projector')) {
                $tipoEquipo = $tipoProyector;
            } elseif (str_contains($nombreModelo, 'mouse')) {
                $tipoEquipo = $tipoMouse;
            } else {
                // Default si no se reconoce
                $tipoEquipo = $tipoLaptop;
            }

            for ($i = 1; $i <= 2; $i++) {
                $numeroSerie = strtoupper("3D-" . str_replace('_', '-', $nombreModelo) . "-{$i}");


                $equipoData = [
                    'tipo_equipo_id' => $tipoEquipo->id,
                    'modelo_id' => $modelo->id,
                    'estado_id' => $estadoDisponible->id,
                    'tipo_reserva_id' => $tipoReservaId,
                    'numero_serie' => $numeroSerie,
                    'vida_util' => 3,  // Puedes ajustar valor por defecto
                    'es_componente' => 0,
                    'detalles' => 'Equipo 3D generado automáticamente para modelo ' . $nombreModelo,
                    'fecha_adquisicion' => now()->toDateString(),
                    'is_deleted' => false,
                    'imagen_glb' => $modelo->imagen_glb,
                ];

                $equipo = Equipo::updateOrCreate(
                    ['numero_serie' => $numeroSerie],
                    $equipoData
                );

                // Características por defecto (puedes ajustar si quieres valores distintos)
                $caracteristicasPorDefecto = [
                    'Peso (kg)' => 1.0,
                    'Color' => 'Negro',
                    'Resolución (px)' => 1080,
                ];

                foreach ($caracteristicasPorDefecto as $nombreCarac => $valor) {
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
}
