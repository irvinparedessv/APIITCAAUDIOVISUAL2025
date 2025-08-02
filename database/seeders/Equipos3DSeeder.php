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
            ['nombre' => 'laptop_alienpredator', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/laptop_alienpredator.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'laptop_dell_latitude_5400', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/laptop_dell_latitude_5400.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'hp_14s-dq2622tu_laptop', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/hp_14s-dq2622tu_laptop.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'asus_laptop', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/asus_laptop.glb', 'is_deleted' => false, 'reposo' => 30],

            ['nombre' => 'projector', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/projector.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'generic_white_digital_projector', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/generic_white_digital_projector.glb', 'escala' => 0.12, 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'fix_projector', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/fix_projector.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'low_poly_projector_3d_model_created_with_blender', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/low_poly_projector_3d_model_created_with_blender.glb', 'is_deleted' => false, 'reposo' => 30],

            ['nombre' => 'mouse_a4tech_x-710bk', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/mouse_a4tech_x-710bk.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'computer_mouse', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/computer_mouse.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'pc_mouse_type-r', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/pc_mouse_type-r.glb', 'is_deleted' => false, 'reposo' => 30, 'escala' => 0.04],
            ['nombre' => 'computer_mouse_a4tech_bloody_v7', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/computer_mouse_a4tech_bloody_v7.glb', 'is_deleted' => false, 'reposo' => 30, 'escala' => 0.04],

            ['nombre' => 'fgm-1_parlante', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/fgm-1_parlante.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'parlante2_-_paula_pinzon', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/parlante2_-_paula_pinzon.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'medium_and_high_frequency_speaker', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/medium_and_high_frequency_speaker.glb', 'is_deleted' => false, 'reposo' => 30],

            ['nombre' => 'jbl_speaker', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/jbl_speaker.glb', 'is_deleted' => false, 'reposo' => 30, 'escala' => 0.10],
            ['nombre' => 'logitech_speaker', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/logitech_speaker.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'computer_speaker', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/computer_speaker.glb', 'is_deleted' => false, 'reposo' => 30],

            // Micrófonos agregados
            ['nombre' => 'microphone', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/microphone.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'fredsters_microphone', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/fredsters_microphone.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'gooseneck_microphone_bended_version', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/gooseneck_microphone_bended_version.glb', 'is_deleted' => false, 'reposo' => 30],
            ['nombre' => 'gart230_-_microphone', 'marca_id' => $marcaGen3D->id, 'imagen_glb' => 'models/gart230_-_microphone.glb', 'is_deleted' => false, 'reposo' => 30],
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

        // -- Obtener todos los modelos ya guardados en BD --
        $modelos = [];
        foreach ($modelos3D as $mod) {
            $modelos[$mod['nombre']] = Modelo::where('nombre', $mod['nombre'])->first();
        }

        // -- Crear un equipo 3D para cada modelo 3D --
        // Para Laptop, Proyector y Mouse crear equipos con tipos de reserva 1, 2 y 3
        $tiposReservaEspeciales = [1, 2, 3]; // Eventos, Reunión, Clase

        foreach ($modelos as $nombreModelo => $modelo) {

            // Detectar tipo equipo según nombre modelo (ejemplo rápido)
            if (str_contains($nombreModelo, 'laptop')) {
                $tipoEquipo = $tipoLaptop;
                $tiposReserva = $tiposReservaEspeciales;
            } elseif (str_contains($nombreModelo, 'projector')) {
                $tipoEquipo = $tipoProyector;
                $tiposReserva = $tiposReservaEspeciales;
            } elseif (str_contains($nombreModelo, 'mouse')) {
                $tipoEquipo = $tipoMouse;
                $tiposReserva = $tiposReservaEspeciales;
            } elseif (
                str_contains($nombreModelo, 'microphone') ||
                str_contains($nombreModelo, 'microfono') ||
                str_contains($nombreModelo, 'fredsters_microphone') ||
                str_contains($nombreModelo, 'gooseneck_microphone_bended_version') ||
                str_contains($nombreModelo, 'gart230_-_microphone')
            ) {
                $tipoEquipo = $tipoMicrofono;
                $tiposReserva = [1, 2]; // Solo Eventos y Reunión
            } elseif (
                in_array($nombreModelo, [
                    'fgm-1_parlante',
                    'parlante2_-_paula_pinzon',
                    'medium_and_high_frequency_speaker',
                ])
            ) {
                $tipoEquipo = $tipoParlante;
                $tiposReserva = [1, 2]; // Eventos y Reunión
            } elseif (
                in_array($nombreModelo, [
                    'jbl_speaker',
                    'logitech_speaker',
                    'computer_speaker',
                ])
            ) {
                $tipoEquipo = $tipoParlante;
                $tiposReserva = [3]; // Solo Clase
            } else {
                $tipoEquipo = $tipoLaptop;  // Default tipo equipo
                $tiposReserva = [3];
            }

            // Crear equipos para cada tipo de reserva asignado
            foreach ($tiposReserva as $tipoReservaId) {
                for ($i = 1; $i <= 2; $i++) {
                    $numeroSerie = strtoupper("3D-" . str_replace('_', '-', $nombreModelo) . "-R{$tipoReservaId}-{$i}");

                    $equipoData = [
                        'tipo_equipo_id' => $tipoEquipo->id,
                        'modelo_id' => $modelo->id,
                        'estado_id' => $estadoDisponible->id,
                        'tipo_reserva_id' => $tipoReservaId,
                        'numero_serie' => $numeroSerie,
                        'vida_util' => 300,
                        'es_componente' => 0,
                        'detalles' => 'Equipo 3D generado automáticamente para modelo ' . $nombreModelo,
                        'fecha_adquisicion' => now()->toDateString(),
                        'is_deleted' => false,
                        'reposo' => 30,
                    ];

                    $equipo = Equipo::updateOrCreate(
                        ['numero_serie' => $numeroSerie],
                        $equipoData
                    );

                    // Solo estos logs:
                    echo "Equipo creado o actualizado: {$numeroSerie}\n";
                    echo "  Escala del modelo: {$modelo->escala}\n";

                    $tipoReserva = TipoReserva::find($equipo->tipo_reserva_id);
                    echo "  Tipo de reserva asignado: " . ($tipoReserva ? $tipoReserva->nombre : 'No encontrado') . "\n";

                    // Características por defecto
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
}
