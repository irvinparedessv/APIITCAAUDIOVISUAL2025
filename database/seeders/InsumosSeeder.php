<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Equipo;
use App\Models\Estado;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\TipoEquipo;
use App\Models\TipoReserva;
use App\Models\ModeloAccesorio;
use App\Models\EquipoAccesorio;
use Illuminate\Database\Seeder;

class InsumosSeeder extends Seeder
{
    public function run()
    {
        // -- Categorías --
        $categoriaInsumo = Categoria::updateOrCreate(
            ['nombre' => 'Insumo'],
            ['is_deleted' => false]
        );

        // -- Tipos de insumos --
        $tiposInsumo = [
            ['nombre' => 'Cable', 'categoria_id' => $categoriaInsumo->id, 'is_deleted' => false],
            ['nombre' => 'Control', 'categoria_id' => $categoriaInsumo->id, 'is_deleted' => false],
        ];
        foreach ($tiposInsumo as $tipo) {
            TipoEquipo::updateOrCreate(['nombre' => $tipo['nombre']], $tipo);
        }

        // -- Marcas --
        $marcaGen3D = Marca::where('nombre', 'Generic 3D Models')->first();

        // -- Estados --
        $estadoDisponible = Estado::where('nombre', 'Disponible')->first();

        // -- Tipo reserva
        $tipoReservaId = 3;

        // -- Tipos de insumo
        $tipoCable = TipoEquipo::where('nombre', 'Cable')->first();
        $tipoControl = TipoEquipo::where('nombre', 'Control')->first();

        // === Agregar modelos de insumos ===
        $insumoModelsData = [
            ['nombre' => 'cable_hdmi', 'imagen_glb' => 'models/cable_hdmi.glb'],
            ['nombre' => 'control_remoto', 'imagen_glb' => 'models/control_remoto.glb'],
            ['nombre' => 'adaptador_usb_c', 'imagen_glb' => 'models/adaptador_usb_c.glb'],
            ['nombre' => 'cable_vga', 'imagen_glb' => 'models/cable_vga.glb'],
            ['nombre' => 'control_bluetooth', 'imagen_glb' => 'models/control_bluetooth.glb'],
            ['nombre' => 'extensor_hdmi', 'imagen_glb' => 'models/extensor_hdmi.glb'],
        ];

        $modelosInsumo = [];
        foreach ($insumoModelsData as $data) {
            $modelo = Modelo::updateOrCreate(
                ['nombre' => $data['nombre'], 'marca_id' => $marcaGen3D->id],
                ['imagen_glb' => $data['imagen_glb'], 'is_deleted' => false, 'reposo' => 0, 'escala' => 1]
            );
            $modelosInsumo[$data['nombre']] = $modelo;
        }

        // === Obtener modelos de equipos principales ===
        $modelosEquipo = Modelo::whereIn('nombre', [
            'video_projector',
            'generic_white_digital_projector',
            'laptop_low-poly',
            'laptop_alienpredator',
            'microfono',
            'razer_seiren_x',
            'jbl_charge_3_speaker'
        ])->get()->keyBy('nombre');

        // === Asociar modelos accesorios ===
        $modeloAsociaciones = [
            'video_projector' => ['cable_hdmi', 'control_remoto', 'extensor_hdmi'],
            'generic_white_digital_projector' => ['cable_hdmi', 'control_remoto'],
            'laptop_low-poly' => ['cable_hdmi', 'adaptador_usb_c'],
            'laptop_alienpredator' => ['cable_hdmi'],
            'microfono' => ['cable_hdmi'],
            'razer_seiren_x' => ['cable_hdmi'],
            'jbl_charge_3_speaker' => ['cable_hdmi'],
        ];

        foreach ($modeloAsociaciones as $modeloEquipo => $insumos) {
            $modeloEquipoObj = $modelosEquipo[$modeloEquipo] ?? null;
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
        $equipos = Equipo::whereIn('modelo_id', $modelosEquipo->pluck('id'))->get();
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
                        'numero_serie' => $equipo->numero_serie . '-ACC-' . substr($insumoNombre, 0, 3),
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

        // === Crear insumos independientes (no asociados a equipos) ===
        $insumosIndependientes = ['cable_vga', 'control_bluetooth'];

        foreach ($insumosIndependientes as $insumoNombre) {
            $modeloInsumo = $modelosInsumo[$insumoNombre] ?? null;
            if (!$modeloInsumo) continue;

            $tipoEquipoId = str_contains($insumoNombre, 'control') ? $tipoControl->id : $tipoCable->id;

            Equipo::updateOrCreate(
                [
                    'modelo_id' => $modeloInsumo->id,
                    'serie_asociada' => null,
                    'es_componente' => true,
                ],
                [
                    'tipo_equipo_id' => $tipoEquipoId,
                    'estado_id' => $estadoDisponible->id,
                    'tipo_reserva_id' => $tipoReservaId,
                    'numero_serie' => strtoupper($insumoNombre) . '-' . uniqid(),
                    'detalles' => "Insumo independiente",
                    'fecha_adquisicion' => now(),
                    'is_deleted' => false,
                ]
            );
        }
    }
}
