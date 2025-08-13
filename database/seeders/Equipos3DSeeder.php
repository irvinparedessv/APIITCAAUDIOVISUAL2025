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

        // -- Marcas reales --
        $marcas = [
            'Acer', 'Dell', 'HP', 'ASUS',         // Laptops
            'Epson', 'BenQ', 'ViewSonic', 'Canon',  // Proyectores
            'Logitech', 'Redragon', 'A4Tech',       // Mouse
            'JBL', 'Behringer', 'Creative',         // Parlantes
            'Samson', 'Audio-Technica', 'Shure', 'Blue' // Micrófonos
        ];
        
        foreach ($marcas as $nombre) {
            Marca::updateOrCreate(['nombre' => $nombre], ['is_deleted' => false]);
        }

        // -- Modelos 3D con asignación directa a tipo_reserva (1=evento, 2=clase) --
        $modelos3D = [
            // Laptops para eventos (tipo_reserva_id = 1)
            ['nombre' => 'Predator Helios 300', 'marca' => 'Acer', 'tipo' => 'laptop', 'imagen_glb' => 'models/laptop_alienpredator.glb', 'is_deleted' => false, 'reposo' => 30, 'tipo_reserva_id' => 1],
            ['nombre' => 'ROG Zephyrus G14', 'marca' => 'ASUS', 'tipo' => 'laptop', 'imagen_glb' => 'models/asus_laptop.glb', 'is_deleted' => false, 'reposo' => 30, 'tipo_reserva_id' => 1],
            
            // Laptops para clases (tipo_reserva_id = 2)
            ['nombre' => 'Latitude 5400', 'marca' => 'Dell', 'tipo' => 'laptop', 'imagen_glb' => 'models/laptop_dell_latitude_5400.glb', 'is_deleted' => false, 'reposo' => 30, 'tipo_reserva_id' => 2],
            ['nombre' => '14s-dq2622TU', 'marca' => 'HP', 'tipo' => 'laptop', 'imagen_glb' => 'models/hp_14s-dq2622tu_laptop.glb', 'is_deleted' => false, 'reposo' => 30, 'tipo_reserva_id' => 2],

            // Proyectores para eventos (tipo_reserva_id = 1)
            ['nombre' => 'PowerLite X49', 'marca' => 'Epson', 'tipo' => 'proyector', 'imagen_glb' => 'models/projector.glb', 'is_deleted' => false, 'reposo' => 30, 'tipo_reserva_id' => 1],
            ['nombre' => 'MS550', 'marca' => 'BenQ', 'tipo' => 'proyector', 'imagen_glb' => 'models/generic_white_digital_projector.glb', 'escala' => 0.12, 'is_deleted' => false, 'reposo' => 30, 'tipo_reserva_id' => 1],
            
            // Proyectores para clases (tipo_reserva_id = 2)
            ['nombre' => 'PA503W', 'marca' => 'ViewSonic', 'tipo' => 'proyector', 'imagen_glb' => 'models/fix_projector.glb', 'is_deleted' => false, 'reposo' => 30, 'tipo_reserva_id' => 2],
            ['nombre' => 'LV-WX300', 'marca' => 'Canon', 'tipo' => 'proyector', 'imagen_glb' => 'models/low_poly_projector_3d_model_created_with_blender.glb', 'is_deleted' => false, 'reposo' => 30, 'tipo_reserva_id' => 2],

            // Mouse para eventos (tipo_reserva_id = 1)
            ['nombre' => 'G502 HERO', 'marca' => 'Logitech', 'tipo' => 'mouse', 'imagen_glb' => 'models/computer_mouse.glb', 'is_deleted' => false, 'reposo' => 30, 'tipo_reserva_id' => 1],
            ['nombre' => 'CENTROPHORUS M601', 'marca' => 'Redragon', 'tipo' => 'mouse', 'imagen_glb' => 'models/pc_mouse_type-r.glb', 'is_deleted' => false, 'reposo' => 30, 'escala' => 0.04, 'tipo_reserva_id' => 1],
            
            // Mouse para clases (tipo_reserva_id = 2)
            ['nombre' => 'M90', 'marca' => 'Logitech', 'tipo' => 'mouse', 'imagen_glb' => 'models/computer_mouse.glb', 'is_deleted' => false, 'reposo' => 30, 'tipo_reserva_id' => 2],
            ['nombre' => 'Bloody V7', 'marca' => 'A4Tech', 'tipo' => 'mouse', 'imagen_glb' => 'models/computer_mouse_a4tech_bloody_v7.glb', 'is_deleted' => false, 'reposo' => 30, 'escala' => 0.04, 'tipo_reserva_id' => 2],

            // Parlantes para eventos (tipo_reserva_id = 1)
            ['nombre' => 'PartyBox 310', 'marca' => 'JBL', 'tipo' => 'parlante', 'imagen_glb' => 'models/parlante2_-_paula_pinzon.glb', 'is_deleted' => false, 'reposo' => 30, 'tipo_reserva_id' => 1],
            ['nombre' => 'Eurolive B205D', 'marca' => 'Behringer', 'tipo' => 'parlante', 'imagen_glb' => 'models/medium_and_high_frequency_speaker.glb', 'is_deleted' => false, 'reposo' => 30, 'tipo_reserva_id' => 1],
            
            // Parlantes para clases (tipo_reserva_id = 2)
            ['nombre' => 'GO 3', 'marca' => 'JBL', 'tipo' => 'parlante', 'imagen_glb' => 'models/jbl_speaker.glb', 'is_deleted' => false, 'reposo' => 30, 'escala' => 0.10, 'tipo_reserva_id' => 2],
            ['nombre' => 'Pebble 2.0', 'marca' => 'Creative', 'tipo' => 'parlante', 'imagen_glb' => 'models/computer_speaker.glb', 'is_deleted' => false, 'reposo' => 30, 'tipo_reserva_id' => 2],

            // Micrófonos (solo para eventos - tipo_reserva_id = 1)
            ['nombre' => 'Q2U', 'marca' => 'Samson', 'tipo' => 'microfono', 'imagen_glb' => 'models/microphone.glb', 'is_deleted' => false, 'reposo' => 30, 'tipo_reserva_id' => 1],
            ['nombre' => 'AT2020', 'marca' => 'Audio-Technica', 'tipo' => 'microfono', 'imagen_glb' => 'models/fredsters_microphone.glb', 'is_deleted' => false, 'reposo' => 30, 'tipo_reserva_id' => 1],
            ['nombre' => 'MX412', 'marca' => 'Shure', 'tipo' => 'microfono', 'imagen_glb' => 'models/gooseneck_microphone_bended_version.glb', 'is_deleted' => false, 'reposo' => 30, 'tipo_reserva_id' => 1],
            ['nombre' => 'Yeti Nano', 'marca' => 'Blue', 'tipo' => 'microfono', 'imagen_glb' => 'models/gart230_-_microphone.glb', 'is_deleted' => false, 'reposo' => 30, 'tipo_reserva_id' => 1],
        ];

        foreach ($modelos3D as $mod) {
            $marca = Marca::where('nombre', $mod['marca'])->first();
            
            Modelo::updateOrCreate(
                ['nombre' => $mod['nombre'], 'marca_id' => $marca->id],
                [
                    'imagen_glb' => $mod['imagen_glb'],
                    'is_deleted' => $mod['is_deleted'],
                    'reposo' => $mod['reposo'],
                    'escala' => $mod['escala'] ?? 1
                ]
            );
        }

        // -- Estados --
        $estados = ['Disponible', 'Mantenimiento', 'En reposo', 'Dañado', 'No disponible', 'Reservado'];
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

        // -- Obtener modelos ya guardados en BD --
        $modelos = [];
        foreach ($modelos3D as $mod) {
            $marca = Marca::where('nombre', $mod['marca'])->first();
            $modelos[$mod['nombre'].$mod['marca']] = Modelo::where('nombre', $mod['nombre'])
                                                        ->where('marca_id', $marca->id)
                                                        ->first();
        }

        // -- Crear equipos 3D --
        foreach ($modelos3D as $mod) {
            $marca = Marca::where('nombre', $mod['marca'])->first();
            $modelo = $modelos[$mod['nombre'].$mod['marca']] ?? null;
            if (!$modelo) continue;

            $tipoStr = $mod['tipo'];
            $tipoReservaId = $mod['tipo_reserva_id'];

            switch ($tipoStr) {
                case 'laptop':
                    $tipoEquipo = $tipoLaptop;
                    break;
                case 'microfono':
                    $tipoEquipo = $tipoMicrofono;
                    break;
                case 'parlante':
                    $tipoEquipo = $tipoParlante;
                    break;
                case 'proyector':
                    $tipoEquipo = $tipoProyector;
                    break;
                case 'mouse':
                    $tipoEquipo = $tipoMouse;
                    break;
                default:
                    $tipoEquipo = $tipoLaptop;
            }

            // Crear 2 equipos por modelo
            for ($i = 1; $i <= 2; $i++) {
                $numeroSerie = strtoupper("3D-{$mod['marca']}-".str_replace(' ', '-', $mod['nombre'])."-{$tipoReservaId}-{$i}");

                $equipoData = [
                    'tipo_equipo_id' => $tipoEquipo->id,
                    'modelo_id' => $modelo->id,
                    'estado_id' => $estadoDisponible->id,
                    'tipo_reserva_id' => $tipoReservaId,
                    'numero_serie' => $numeroSerie,
                    'vida_util' => 300,
                    'es_componente' => 0,
                    'detalles' => "Equipo 3D para ".($tipoReservaId == 1 ? 'eventos' : 'clases'),
                    'fecha_adquisicion' => now()->toDateString(),
                    'is_deleted' => false,
                    'reposo' => $mod['reposo'],
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

                // Características específicas por tipo
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
                            ['valor' => '20Hz - 20kHz']
                        );
                        ValoresCaracteristica::updateOrCreate(
                            ['equipo_id' => $equipo->id, 'caracteristica_id' => $caracteristicas['Tipo de Entrada']->id],
                            ['valor' => 'XLR']
                        );
                        break;

                    case 'parlante':
                        ValoresCaracteristica::updateOrCreate(
                            ['equipo_id' => $equipo->id, 'caracteristica_id' => $caracteristicas['Conectividad']->id],
                            ['valor' => 'Bluetooth']
                        );
                        ValoresCaracteristica::updateOrCreate(
                            ['equipo_id' => $equipo->id, 'caracteristica_id' => $caracteristicas['Frecuencia de Respuesta (Hz)']->id],
                            ['valor' => '50Hz - 20kHz']
                        );
                        break;

                    case 'proyector':
                        ValoresCaracteristica::updateOrCreate(
                            ['equipo_id' => $equipo->id, 'caracteristica_id' => $caracteristicas['Resolución Nativa (px)']->id],
                            ['valor' => 1920*1080]
                        );
                        ValoresCaracteristica::updateOrCreate(
                            ['equipo_id' => $equipo->id, 'caracteristica_id' => $caracteristicas['Conectividad']->id],
                            ['valor' => 'HDMI, VGA']
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

                // Insertar características comunes por defecto
                foreach ($caracteristicasPorDefecto as $nombreCarac => $valor) {
                    ValoresCaracteristica::updateOrCreate(
                        ['equipo_id' => $equipo->id, 'caracteristica_id' => $caracteristicas[$nombreCarac]->id],
                        ['valor' => $valor]
                    );
                }
            }
        }
    }
}