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
        // -- Categoría Insumo --
        $categoriaInsumo = Categoria::updateOrCreate(
            ['nombre' => 'Insumo'],
            ['is_deleted' => false]
        );

        // -- Tipos de Insumo --
        $tiposInsumoData = [
            ['nombre' => 'Cable', 'categoria_id' => $categoriaInsumo->id, 'is_deleted' => false],
            ['nombre' => 'Control', 'categoria_id' => $categoriaInsumo->id, 'is_deleted' => false],
        ];
        foreach ($tiposInsumoData as $tipo) {
            TipoEquipo::updateOrCreate(['nombre' => $tipo['nombre']], $tipo);
        }
        $tipoCable = TipoEquipo::where('nombre', 'Cable')->first();
        $tipoControl = TipoEquipo::where('nombre', 'Control')->first();

        // -- Marca genérica 3D --
        $marcaGen3D = Marca::where('nombre', 'Generic 3D Models')->first();

        // -- Estado Disponible --
        $estadoDisponible = Estado::where('nombre', 'Disponible')->first();

        // -- Tipo Reserva fijo --
        $tipoReservaId = 3;

        // -- Modelos reales (Seeder 1) --
        $modelos3D = [
            ['nombre' => 'Acer Predator Helios 300', 'imagen_glb' => 'models/laptop_alienpredator.glb', 'tipo' => 'laptop'],
            ['nombre' => 'Dell Latitude 5400', 'imagen_glb' => 'models/laptop_dell_latitude_5400.glb', 'tipo' => 'laptop'],
            ['nombre' => 'HP 14s-dq2622TU', 'imagen_glb' => 'models/hp_14s-dq2622tu_laptop.glb', 'tipo' => 'laptop'],
            ['nombre' => 'ASUS VivoBook 15', 'imagen_glb' => 'models/asus_laptop.glb', 'tipo' => 'laptop'],
            ['nombre' => 'Epson PowerLite X49', 'imagen_glb' => 'models/projector.glb', 'tipo' => 'proyector'],
            ['nombre' => 'BenQ MS550', 'imagen_glb' => 'models/generic_white_digital_projector.glb', 'tipo' => 'proyector'],
            ['nombre' => 'ViewSonic PA503W', 'imagen_glb' => 'models/fix_projector.glb', 'tipo' => 'proyector'],
            ['nombre' => 'Canon LV-WX300 (estilo genérico)', 'imagen_glb' => 'models/low_poly_projector_3d_model_created_with_blender.glb', 'tipo' => 'proyector'],
            ['nombre' => 'A4Tech X-710BK', 'imagen_glb' => 'models/mouse_a4tech_x-710bk.glb', 'tipo' => 'mouse'],
            ['nombre' => 'Logitech M90', 'imagen_glb' => 'models/computer_mouse.glb', 'tipo' => 'mouse'],
            ['nombre' => 'Redragon M601 CENTROPHORUS', 'imagen_glb' => 'models/pc_mouse_type-r.glb', 'tipo' => 'mouse'],
            ['nombre' => 'A4Tech Bloody V7', 'imagen_glb' => 'models/computer_mouse_a4tech_bloody_v7.glb', 'tipo' => 'mouse'],
            ['nombre' => 'Sony SRS-XB13', 'imagen_glb' => 'models/fgm-1_parlante.glb', 'tipo' => 'parlante'],
            ['nombre' => 'JBL Flip 5', 'imagen_glb' => 'models/parlante2_-_paula_pinzon.glb', 'tipo' => 'parlante'],
            ['nombre' => 'Behringer Eurolive B205D', 'imagen_glb' => 'models/medium_and_high_frequency_speaker.glb', 'tipo' => 'parlante'],
            ['nombre' => 'JBL GO 3', 'imagen_glb' => 'models/jbl_speaker.glb', 'tipo' => 'parlante'],
            ['nombre' => 'Logitech Z313', 'imagen_glb' => 'models/logitech_speaker.glb', 'tipo' => 'parlante'],
            ['nombre' => 'Creative Pebble 2.0', 'imagen_glb' => 'models/computer_speaker.glb', 'tipo' => 'parlante'],
            ['nombre' => 'Samson Q2U', 'imagen_glb' => 'models/microphone.glb', 'tipo' => 'microfono'],
            ['nombre' => 'Audio-Technica AT2020', 'imagen_glb' => 'models/fredsters_microphone.glb', 'tipo' => 'microfono'],
            ['nombre' => 'Shure MX412 Gooseneck', 'imagen_glb' => 'models/gooseneck_microphone_bended_version.glb', 'tipo' => 'microfono'],
            ['nombre' => 'Blue Yeti Nano', 'imagen_glb' => 'models/gart230_-_microphone.glb', 'tipo' => 'microfono'],
        ];

        // Crear/Actualizar modelos equipo
        $modelosEquipo = [];
        foreach ($modelos3D as $mod) {
            $modelo = Modelo::updateOrCreate(
                ['nombre' => $mod['nombre'], 'marca_id' => $marcaGen3D->id],
                [
                    'imagen_glb' => $mod['imagen_glb'],
                    'is_deleted' => false,
                    'reposo' => 30,
                    'escala' => $mod['escala'] ?? 1,
                ]
            );
            $modelosEquipo[$mod['nombre']] = ['modelo' => $modelo, 'tipo' => $mod['tipo']];
        }

        // Insumos base (nombre, glb, tipo)
        $insumosBase = [
            ['nombre' => 'cable_hdmi', 'imagen_glb' => 'models/cable_hdmi.glb', 'tipo' => 'Cable'],
            ['nombre' => 'cable_vga', 'imagen_glb' => 'models/cable_vga.glb', 'tipo' => 'Cable'],
            ['nombre' => 'adaptador_usb_c', 'imagen_glb' => 'models/adaptador_usb_c.glb', 'tipo' => 'Cable'],
            ['nombre' => 'extensor_hdmi', 'imagen_glb' => 'models/extensor_hdmi.glb', 'tipo' => 'Cable'],
            ['nombre' => 'control_remoto', 'imagen_glb' => 'models/control_remoto.glb', 'tipo' => 'Control'],
            ['nombre' => 'control_bluetooth', 'imagen_glb' => 'models/control_bluetooth.glb', 'tipo' => 'Control'],
            ['nombre' => 'cable_usb', 'imagen_glb' => 'models/cable_usb.glb', 'tipo' => 'Cable'], // agregado para mouse y micrófono
        ];

        // Crear modelos insumos base
        $modelosInsumoBase = [];
        foreach ($insumosBase as $insumo) {
            $modeloInsumo = Modelo::updateOrCreate(
                ['nombre' => $insumo['nombre'], 'marca_id' => $marcaGen3D->id],
                [
                    'imagen_glb' => $insumo['imagen_glb'],
                    'is_deleted' => false,
                    'reposo' => 0,
                    'escala' => 1,
                ]
            );
            $modelosInsumoBase[$insumo['nombre']] = ['modelo' => $modeloInsumo, 'tipo' => $insumo['tipo']];
        }

        // Asignación de insumos lógicos por tipo de equipo
        $insumosPorTipo = [
            'laptop' => ['cable_hdmi', 'adaptador_usb_c'],
            'proyector' => ['cable_hdmi', 'cable_vga', 'control_remoto'],
            'mouse' => ['cable_usb', 'control_bluetooth'],
            'parlante' => ['cable_usb', 'control_bluetooth'],
            'microfono' => ['cable_usb', 'control_bluetooth'],
        ];

        // Función para elegir dos insumos por modelo, evitando repeticiones exactas (roto con índice)
        function elegirInsumosValidos($modeloNombre, $insumosValidos)
        {
            $hash = crc32($modeloNombre);
            srand($hash);
            shuffle($insumosValidos);
            srand();

            return array_slice($insumosValidos, 0, 2);
        }

        // Asociar insumos por modelo equipo
        foreach ($modelosEquipo as $nombreModelo => $data) {
            $modeloEquipo = $data['modelo'];
            $tipoEquipo = $data['tipo'];

            $insumosValidos = $insumosPorTipo[$tipoEquipo] ?? [];

            if (count($insumosValidos) === 0) {
                continue; // no insumos definidos para este tipo
            }

            $insumosSeleccionados = elegirInsumosValidos($nombreModelo, $insumosValidos);

            foreach ($insumosSeleccionados as $insumoNombre) {
                $insumoBase = $modelosInsumoBase[$insumoNombre];
                $modeloInsumo = Modelo::updateOrCreate(
                    ['nombre' => strtolower(str_replace([' ', '(', ')'], ['_', '', ''], $nombreModelo)) . '_' . $insumoNombre, 'marca_id' => $marcaGen3D->id],
                    [
                        'imagen_glb' => $insumoBase['modelo']->imagen_glb,
                        'is_deleted' => false,
                        'reposo' => 0,
                        'escala' => 1,
                    ]
                );

                ModeloAccesorio::updateOrCreate([
                    'modelo_equipo_id' => $modeloEquipo->id,
                    'modelo_insumo_id' => $modeloInsumo->id,
                ]);

                // Equipos físicos
                $equipos = Equipo::where('modelo_id', $modeloEquipo->id)->get();

                foreach ($equipos as $equipo) {
                    $tipoEquipoId = $insumoBase['tipo'] === 'Control' ? $tipoControl->id : $tipoCable->id;

                    $numeroSerieIns = $equipo->numero_serie . '-ACC-' . strtoupper(substr($insumoNombre, 0, 3));

                    $insumoEquipo = Equipo::updateOrCreate(
                        ['numero_serie' => $numeroSerieIns],
                        [
                            'modelo_id' => $modeloInsumo->id,
                            'serie_asociada' => $equipo->numero_serie,
                            'es_componente' => true,
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
                        'insumo_id' => $insumoEquipo->id,
                    ]);
                }
            }
        }

        // Insumos independientes
        $insumosIndependientes = ['cable_vga', 'extensor_hdmi', 'control_bluetooth'];

        foreach ($insumosIndependientes as $insumoNombre) {
            if (!isset($modelosInsumoBase[$insumoNombre])) continue;

            $modeloInsumo = $modelosInsumoBase[$insumoNombre]['modelo'];
            $tipoEquipoId = $modelosInsumoBase[$insumoNombre]['tipo'] === 'Control' ? $tipoControl->id : $tipoCable->id;

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
