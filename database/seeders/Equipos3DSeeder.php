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
use App\Models\ModeloAccesorio;
use App\Models\EquipoAccesorio;
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
            ['nombre' => 'Cable', 'categoria_id' => $categoriaInsumo->id, 'is_deleted' => false],
            ['nombre' => 'Control', 'categoria_id' => $categoriaInsumo->id, 'is_deleted' => false],
        ];
        foreach ($tipos as $tipo) {
            TipoEquipo::updateOrCreate(['nombre' => $tipo['nombre']], $tipo);
        }

        // -- Marcas --
        $marcaGen3D = Marca::updateOrCreate(['nombre' => 'Generic 3D Models'], ['is_deleted' => false]);

        // -- Modelos 3D --
        $modelos3D = [
            ['nombre' => 'laptop_low-poly', 'imagen_glb' => 'models/laptop_low-poly.glb', 'reposo' => 30, 'escala' => 1.90],
            ['nombre' => 'laptop_alienpredator', 'imagen_glb' => 'models/laptop_alienpredator.glb', 'reposo' => 30, 'escala' => 0.40],
            ['nombre' => 'laptop_windows_menu', 'imagen_glb' => 'models/laptop_windows_menu.glb', 'reposo' => 30, 'escala' => 1.6],
            ['nombre' => 'laptop_dell_g7', 'imagen_glb' => 'models/laptop_dell_g7.glb', 'reposo' => 30, 'escala' => 0.08],
            ['nombre' => 'laptop_acer', 'imagen_glb' => 'models/laptop_acer.glb', 'reposo' => 30, 'escala' => 0.30],
            ['nombre' => 'microfono', 'imagen_glb' => 'models/microfono.glb', 'reposo' => 30],
            ['nombre' => 'razer_seiren_x', 'imagen_glb' => 'models/razer_seiren_x.glb', 'reposo' => 30, 'escala' => 0.06],
            ['nombre' => 'skp_pro_40_microphone', 'imagen_glb' => 'models/skp_pro_40_microphone.glb', 'reposo' => 30, 'escala' => 0.05],
            ['nombre' => 'jbl_charge_3_speaker', 'imagen_glb' => 'models/jbl_charge_3_speaker.glb', 'reposo' => 30],
            ['nombre' => 'logitech_speaker', 'imagen_glb' => 'models/logitech_speaker.glb', 'reposo' => 30],
            ['nombre' => 'bluetooth_speaker', 'imagen_glb' => 'models/bluetooth_speaker.glb', 'reposo' => 30],
            ['nombre' => 'jbl_speaker', 'imagen_glb' => 'models/jbl_speaker.glb', 'reposo' => 30, 'escala' => 0.10],
            ['nombre' => 'video_projector', 'imagen_glb' => 'models/video_projector.glb', 'reposo' => 30],
            ['nombre' => 'generic_white_digital_projector', 'imagen_glb' => 'models/generic_white_digital_projector.glb', 'reposo' => 30],
            ['nombre' => 'pc_mouse_type-r', 'imagen_glb' => 'models/pc_mouse_type-r.glb', 'reposo' => 30, 'escala' => 0.04],
            ['nombre' => 'computer_mouse', 'imagen_glb' => 'models/computer_mouse.glb', 'reposo' => 30],
            ['nombre' => 'computer_mouse_a4tech_bloody_v7', 'imagen_glb' => 'models/computer_mouse_a4tech_bloody_v7.glb', 'reposo' => 30, 'escala' => 0.04],
        ];

        foreach ($modelos3D as $mod) {
            Modelo::updateOrCreate(
                ['nombre' => $mod['nombre'], 'marca_id' => $marcaGen3D->id],
                [
                    'imagen_glb' => $mod['imagen_glb'],
                    'is_deleted' => false,
                    'reposo' => $mod['reposo'],
                    'escala' => $mod['escala'] ?? 1,
                ]
            );
        }

        // -- Estados --
        $estadoDisponible = Estado::updateOrCreate(['nombre' => 'Disponible'], ['is_deleted' => false]);

        // -- Tipos de equipo
        $tipoLaptop = TipoEquipo::where('nombre', 'Laptop')->first();
        $tipoMicrofono = TipoEquipo::where('nombre', 'Micrófono')->first();
        $tipoParlante = TipoEquipo::where('nombre', 'Parlante')->first();
        $tipoProyector = TipoEquipo::where('nombre', 'Proyector')->first();
        $tipoMouse = TipoEquipo::where('nombre', 'Mouse')->first();
        $tipoCable = TipoEquipo::where('nombre', 'Cable')->first();
        $tipoControl = TipoEquipo::where('nombre', 'Control')->first();

        // -- Características --
        $caracteristicas = [
            'Peso (kg)' => Caracteristica::updateOrCreate(['nombre' => 'Peso (kg)'], ['tipo_dato' => 'decimal', 'is_deleted' => false]),
            'Color' => Caracteristica::updateOrCreate(['nombre' => 'Color'], ['tipo_dato' => 'string', 'is_deleted' => false]),
            'Resolución (px)' => Caracteristica::updateOrCreate(['nombre' => 'Resolución (px)'], ['tipo_dato' => 'integer', 'is_deleted' => false]),
        ];

        $tipoLaptop->caracteristicas()->syncWithoutDetaching(array_column($caracteristicas, 'id'));
        $tipoMicrofono->caracteristicas()->syncWithoutDetaching(array_column($caracteristicas, 'id'));
        $tipoParlante->caracteristicas()->syncWithoutDetaching(array_column($caracteristicas, 'id'));
        $tipoProyector->caracteristicas()->syncWithoutDetaching(array_column($caracteristicas, 'id'));
        $tipoMouse->caracteristicas()->syncWithoutDetaching(array_column($caracteristicas, 'id'));

        // -- Tipo reserva
        $tipoReservaId = 3;

        // -- Obtener modelos
        $modelos = [];
        foreach ($modelos3D as $mod) {
            $modelos[$mod['nombre']] = Modelo::where('nombre', $mod['nombre'])->first();
        }

        // -- Crear equipos físicos
        foreach ($modelos as $nombreModelo => $modelo) {
            if (str_contains($nombreModelo, 'laptop')) {
                $tipoEquipo = $tipoLaptop;
            } elseif (str_contains($nombreModelo, 'microfono') || str_contains($nombreModelo, 'microphone')) {
                $tipoEquipo = $tipoMicrofono;
            } elseif (str_contains($nombreModelo, 'speaker')) {
                $tipoEquipo = $tipoParlante;
            } elseif (str_contains($nombreModelo, 'projector')) {
                $tipoEquipo = $tipoProyector;
            } elseif (str_contains($nombreModelo, 'mouse')) {
                $tipoEquipo = $tipoMouse;
            } else {
                $tipoEquipo = $tipoLaptop;
            }

            for ($i = 1; $i <= 2; $i++) {
                $numeroSerie = strtoupper("3D-" . str_replace('_', '-', $nombreModelo) . "-{$i}");

                $equipo = Equipo::updateOrCreate(
                    ['numero_serie' => $numeroSerie],
                    [
                        'tipo_equipo_id' => $tipoEquipo->id,
                        'modelo_id' => $modelo->id,
                        'estado_id' => $estadoDisponible->id,
                        'tipo_reserva_id' => $tipoReservaId,
                        'numero_serie' => $numeroSerie,
                        'vida_util' => 3,
                        'es_componente' => false,
                        'detalles' => 'Equipo 3D generado automáticamente para modelo ' . $nombreModelo,
                        'fecha_adquisicion' => now()->toDateString(),
                        'is_deleted' => false,
                        'reposo' => 30,
                    ]
                );

                $valores = [
                    'Peso (kg)' => 1.0,
                    'Color' => 'Negro',
                    'Resolución (px)' => 1080,
                ];

                foreach ($valores as $nombreCarac => $valor) {
                    if (isset($caracteristicas[$nombreCarac])) {
                        ValoresCaracteristica::updateOrCreate(
                            ['equipo_id' => $equipo->id, 'caracteristica_id' => $caracteristicas[$nombreCarac]->id],
                            ['valor' => $valor]
                        );
                    }
                }
            }
        }

        // === Agregar modelos de insumos ===
        $insumoModelsData = [
            ['nombre' => 'cable_hdmi', 'imagen_glb' => 'models/cable_hdmi.glb'],
            ['nombre' => 'control_remoto', 'imagen_glb' => 'models/control_remoto.glb'],
        ];
        $modelosInsumo = [];
        foreach ($insumoModelsData as $data) {
            $modelo = Modelo::updateOrCreate(
                ['nombre' => $data['nombre'], 'marca_id' => $marcaGen3D->id],
                ['imagen_glb' => $data['imagen_glb'], 'is_deleted' => false, 'reposo' => 0, 'escala' => 1]
            );
            $modelosInsumo[$data['nombre']] = $modelo;
        }

        // === Asociar modelos accesorios ===
        $modeloAsociaciones = [
            'video_projector' => ['cable_hdmi', 'control_remoto'],
            'generic_white_digital_projector' => ['cable_hdmi', 'control_remoto'],
            'laptop_low-poly' => ['mouse'],
            'laptop_alienpredator' => ['mouse'],
            'microfono' => ['cable_hdmi'],
            'razer_seiren_x' => ['cable_hdmi'],
            'jbl_charge_3_speaker' => ['cable_hdmi'],
        ];

        foreach ($modeloAsociaciones as $modeloEquipo => $insumos) {
            $modeloEquipoObj = $modelos[$modeloEquipo] ?? null;
            if (!$modeloEquipoObj) continue;

            foreach ($insumos as $insumoNombre) {
                $modeloInsumo = $modelosInsumo[$insumoNombre] ?? null;
                if ($modeloInsumo) {
                    ModeloAccesorio::updateOrCreate([
                        'modelo_equipo_id' => $modeloEquipoObj->id,
                        'modelo_insumo_id' => $modeloInsumo->id,
                    ]);
                }
            }
        }

        // === Crear equipos insumos asociados ===
        $equipos = Equipo::whereIn('modelo_id', array_column($modelos, 'id'))->get();
        foreach ($equipos as $equipo) {
            $modeloNombre = $equipo->modelo->nombre;
            if (!isset($modeloAsociaciones[$modeloNombre])) continue;

            foreach ($modeloAsociaciones[$modeloNombre] as $insumoNombre) {
                $modeloInsumo = $modelosInsumo[$insumoNombre] ?? null;
                if (!$modeloInsumo) continue;

                $tipoEquipoId = str_contains($insumoNombre, 'control') ? $tipoControl->id : $tipoCable->id;

                $insumo = Equipo::updateOrCreate(
                    [
                        'modelo_id' => $modeloInsumo->id,
                        'serie_asociada' => $equipo->numero_serie,
                        'es_componente' => true,
                    ],
                    [
                        'tipo_equipo_id' => $tipoEquipoId,
                        'estado_id' => $estadoDisponible->id,
                        'tipo_reserva_id' => $tipoReservaId,
                        'detalles' => "Insumo asociado a {$equipo->numero_serie}",
                        'fecha_adquisicion' => now(),
                        'is_deleted' => false,
                    ]
                );

                EquipoAccesorio::updateOrCreate([
                    'equipo_id' => $equipo->id,
                    'insumo_id' => $insumo->id,
                ]);
            }
        }
    }
}
