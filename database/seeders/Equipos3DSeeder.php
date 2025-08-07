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

        // -- Modelos 3D --
        $modelos3D = [
            ['nombre' => 'Acer Predator Helios 300', 'tipo' => 'laptop', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/laptop_alienpredator.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'Dell Latitude 5400', 'tipo' => 'laptop', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/laptop_dell_latitude_5400.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'HP 14s-dq2622TU', 'tipo' => 'laptop', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/hp_14s-dq2622tu_laptop.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'ASUS VivoBook 15', 'tipo' => 'laptop', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/asus_laptop.glb', 'is_deleted' => false, 'reposo' => 30],

            ['nombre' => 'Epson PowerLite X49', 'tipo' => 'proyector', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/projector.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'BenQ MS550', 'tipo' => 'proyector', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/generic_white_digital_projector.glb', 'escala' => 0.12, 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'ViewSonic PA503W', 'tipo' => 'proyector', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/fix_projector.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'Canon LV-WX300 (estilo genérico)', 'tipo' => 'proyector', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/low_poly_projector_3d_model_created_with_blender.glb', 'is_deleted' => false, 'reposo' => 30],

            ['nombre' => 'A4Tech X-710BK', 'tipo' => 'mouse', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/mouse_a4tech_x-710bk.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'Logitech M90', 'tipo' => 'mouse', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/computer_mouse.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'Redragon M601 CENTROPHORUS', 'tipo' => 'mouse', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/pc_mouse_type-r.glb', 'is_deleted' => false, 'reposo' => 30, 'escala' => 0.04],
            ['nombre' => 'A4Tech Bloody V7', 'tipo' => 'mouse', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/computer_mouse_a4tech_bloody_v7.glb', 'is_deleted' => false, 'reposo' => 30, 'escala' => 0.04],

            ['nombre' => 'Sony SRS-XB13', 'tipo' => 'parlante', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/fgm-1_parlante.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'JBL Flip 5', 'tipo' => 'parlante', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/parlante2_-_paula_pinzon.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'Behringer Eurolive B205D', 'tipo' => 'parlante', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/medium_and_high_frequency_speaker.glb', 'is_deleted' => false, 'reposo' => 30],

            ['nombre' => 'JBL GO 3', 'tipo' => 'parlante_clase', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/jbl_speaker.glb', 'is_deleted' => false, 'reposo' => 30, 'escala' => 0.10],
            ['nombre' => 'Logitech Z313', 'tipo' => 'parlante_clase', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/logitech_speaker.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'Creative Pebble 2.0', 'tipo' => 'parlante_clase', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/computer_speaker.glb', 'is_deleted' => false, 'reposo' => 30],

            ['nombre' => 'Samson Q2U', 'tipo' => 'microfono', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/microphone.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'Audio-Technica AT2020', 'tipo' => 'microfono', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/fredsters_microphone.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'Shure MX412 Gooseneck', 'tipo' => 'microfono', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/gooseneck_microphone_bended_version.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'Blue Yeti Nano', 'tipo' => 'microfono', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/gart230_-_microphone.glb', 'is_deleted' => false, 'reposo' => 30],
        ];

        foreach ($modelos3D as $mod) {
            Modelo::updateOrCreate(
                ['nombre' => $mod['nombre'], 'marca_id' => $mod['marca_id']],
                [
                    'imagen_glb' => $mod['imagen_glb'],
                    'is_deleted' => $mod['is_deleted'],
                    'reposo' => $mod['reposo'],
                    'escala' => $mod['escala'] ?? 1
                ]
            );
        }

        // -- Estados --
        $estados = ['Disponible', 'Mantenimiento', 'En reposo', 'Dañado', 'No disponible'];
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
        // -- Características comunes + específicas --
        $caracteristicasData = [
            ['nombre' => 'Peso (kg)', 'tipo_dato' => 'decimal'],
            ['nombre' => 'Color', 'tipo_dato' => 'string'],
            ['nombre' => 'Resolución (px)', 'tipo_dato' => 'integer'],

            // --- Características individuales por tipo (NUEVO) ---
            ['nombre' => 'Tamaño Pantalla (pulgadas)', 'tipo_dato' => 'decimal'],
            ['nombre' => 'Duración Batería (horas)', 'tipo_dato' => 'decimal'],
            ['nombre' => 'Conectividad', 'tipo_dato' => 'string'],
            ['nombre' => 'Tipo de Entrada', 'tipo_dato' => 'string'],
            ['nombre' => 'Frecuencia de Respuesta (Hz)', 'tipo_dato' => 'string'],
            ['nombre' => 'Capacidad Memoria (GB)', 'tipo_dato' => 'integer'],
            ['nombre' => 'Tipo Sensor', 'tipo_dato' => 'string'],
            ['nombre' => 'Resolución Nativa (px)', 'tipo_dato' => 'integer'],
        ];

        $caracteristicas = [];
        foreach ($caracteristicasData as $carac) {
            $caracteristicas[$carac['nombre']] = Caracteristica::updateOrCreate(
                ['nombre' => $carac['nombre']],
                ['tipo_dato' => $carac['tipo_dato'], 'is_deleted' => false]
            );
        }

        // -- Asociar características comunes y específicas --
        $tipoLaptop->caracteristicas()->syncWithoutDetaching([
            $caracteristicas['Peso (kg)']->id,
            $caracteristicas['Color']->id,
            $caracteristicas['Resolución (px)']->id,
            $caracteristicas['Tamaño Pantalla (pulgadas)']->id,
            $caracteristicas['Duración Batería (horas)']->id,
            $caracteristicas['Capacidad Memoria (GB)']->id,
        ]);

        $tipoMicrofono->caracteristicas()->syncWithoutDetaching([
            $caracteristicas['Peso (kg)']->id,
            $caracteristicas['Color']->id,
            $caracteristicas['Frecuencia de Respuesta (Hz)']->id,
            $caracteristicas['Tipo de Entrada']->id,
        ]);

        $tipoParlante->caracteristicas()->syncWithoutDetaching([
            $caracteristicas['Peso (kg)']->id,
            $caracteristicas['Color']->id,
            $caracteristicas['Conectividad']->id,
            $caracteristicas['Frecuencia de Respuesta (Hz)']->id,
        ]);

        $tipoProyector->caracteristicas()->syncWithoutDetaching([
            $caracteristicas['Peso (kg)']->id,
            $caracteristicas['Color']->id,
            $caracteristicas['Resolución Nativa (px)']->id,
            $caracteristicas['Conectividad']->id,
        ]);

        $tipoMouse->caracteristicas()->syncWithoutDetaching([
            $caracteristicas['Peso (kg)']->id,
            $caracteristicas['Color']->id,
            $caracteristicas['Tipo Sensor']->id,
            $caracteristicas['Tipo de Entrada']->id,
        ]);

        // -- Obtener todos los modelos ya guardados en BD --
        $modelos = [];
        foreach ($modelos3D as $mod) {
            $modelos[$mod['nombre']] = Modelo::where('nombre', $mod['nombre'])->first();
        }

        // -- Crear un equipo 3D para cada modelo 3D basado en el tipo --
        foreach ($modelos3D as $mod) {
            $modelo = $modelos[$mod['nombre']] ?? null;
            if (!$modelo) continue;

            $tipoStr = $mod['tipo'];

            switch ($tipoStr) {
                case 'laptop':
                case 'proyector':
                case 'mouse':
                    $tipoEquipo = TipoEquipo::where('nombre', ucfirst($tipoStr))->first();
                    $tiposReserva = [1, 2, 3]; // Eventos, Reunión, Clase
                    break;

                case 'microfono':
                    $tipoEquipo = $tipoMicrofono;
                    $tiposReserva = [1, 2]; // Solo Eventos y Reunión
                    break;

                case 'parlante':
                    $tipoEquipo = $tipoParlante;
                    $tiposReserva = [1, 2]; // Eventos y Reunión
                    break;

                case 'parlante_clase':
                    $tipoEquipo = $tipoParlante;
                    $tiposReserva = [3]; // Solo Clase
                    break;

                default:
                    $tipoEquipo = $tipoLaptop;
                    $tiposReserva = [3];
            }

            foreach ($tiposReserva as $tipoReservaId) {
                for ($i = 1; $i <= 2; $i++) {
                    $numeroSerie = strtoupper("3D-" . str_replace(' ', '-', strtolower($mod['nombre'])) . "-R{$tipoReservaId}-{$i}");

                    $equipoData = [
                        'tipo_equipo_id' => $tipoEquipo->id,
                        'modelo_id' => $modelo->id,
                        'estado_id' => $estadoDisponible->id,
                        'tipo_reserva_id' => $tipoReservaId,
                        'numero_serie' => $numeroSerie,
                        'vida_util' => 300,
                        'es_componente' => 0,
                        'detalles' => 'Equipo 3D generado automáticamente para modelo ' . $mod['nombre'],
                        'fecha_adquisicion' => now()->toDateString(),
                        'is_deleted' => false,
                        'reposo' => 30,
                    ];

                    $equipo = Equipo::updateOrCreate(
                        ['numero_serie' => $numeroSerie],
                        $equipoData
                    );

                    echo "Equipo creado o actualizado: {$numeroSerie}\n";

                    // Características por defecto
                    $caracteristicasPorDefecto = [
                        'Peso (kg)' => 1.0,
                        'Color' => 'Negro',
                        'Resolución (px)' => 1080,
                    ];

                    // --- Características específicas por tipo (NUEVO) ---
switch ($tipoStr) {
    case 'laptop':
        ValoresCaracteristica::updateOrCreate(
            ['equipo_id' => $equipo->id, 'caracteristica_id' => $caracteristicas['Tamaño Pantalla (pulgadas)']->id],
            ['valor' => 15.6]
        );
        ValoresCaracteristica::updateOrCreate(
            ['equipo_id' => $equipo->id, 'caracteristica_id' => $caracteristicas['Duración Batería (horas)']->id],
            ['valor' => 6]
        );
        ValoresCaracteristica::updateOrCreate(
            ['equipo_id' => $equipo->id, 'caracteristica_id' => $caracteristicas['Capacidad Memoria (GB)']->id],
            ['valor' => 8]
        );
        break;

    case 'microfono':
        ValoresCaracteristica::updateOrCreate(
            ['equipo_id' => $equipo->id, 'caracteristica_id' => $caracteristicas['Frecuencia de Respuesta (Hz)']->id],
            ['valor' => '50-15000']
        );
        ValoresCaracteristica::updateOrCreate(
            ['equipo_id' => $equipo->id, 'caracteristica_id' => $caracteristicas['Tipo de Entrada']->id],
            ['valor' => 'USB']
        );
        break;

    case 'parlante':
    case 'parlante_clase':
        ValoresCaracteristica::updateOrCreate(
            ['equipo_id' => $equipo->id, 'caracteristica_id' => $caracteristicas['Conectividad']->id],
            ['valor' => 'Bluetooth']
        );
        ValoresCaracteristica::updateOrCreate(
            ['equipo_id' => $equipo->id, 'caracteristica_id' => $caracteristicas['Frecuencia de Respuesta (Hz)']->id],
            ['valor' => '80-18000']
        );
        break;

    case 'proyector':
        ValoresCaracteristica::updateOrCreate(
            ['equipo_id' => $equipo->id, 'caracteristica_id' => $caracteristicas['Resolución Nativa (px)']->id],
            ['valor' => 1024]
        );
        ValoresCaracteristica::updateOrCreate(
            ['equipo_id' => $equipo->id, 'caracteristica_id' => $caracteristicas['Conectividad']->id],
            ['valor' => 'HDMI/VGA']
        );
        break;

    case 'mouse':
        ValoresCaracteristica::updateOrCreate(
            ['equipo_id' => $equipo->id, 'caracteristica_id' => $caracteristicas['Tipo Sensor']->id],
            ['valor' => 'Óptico']
        );
        ValoresCaracteristica::updateOrCreate(
            ['equipo_id' => $equipo->id, 'caracteristica_id' => $caracteristicas['Tipo de Entrada']->id],
            ['valor' => 'USB']
        );
        break;
}


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
}
