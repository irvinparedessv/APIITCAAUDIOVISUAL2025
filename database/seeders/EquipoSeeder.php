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

class EquipoSeeder extends Seeder
{
    public function run()
    {
        // -- Categorías --
        $categoriaEquipo = Categoria::updateOrCreate(['nombre' => 'Equipo'], ['is_deleted' => false]);
        $categoriaInsumo = Categoria::updateOrCreate(['nombre' => 'Insumo'], ['is_deleted' => false]);

        // -- Tipos de equipo --
        $tipos = [
            ['nombre' => 'Laptop', 'categoria_id' => $categoriaEquipo->id, 'is_deleted' => false],
            ['nombre' => 'Proyector', 'categoria_id' => $categoriaEquipo->id, 'is_deleted' => false],
            ['nombre' => 'Cable', 'categoria_id' => $categoriaInsumo->id, 'is_deleted' => false],
        ];
        foreach ($tipos as $tipo) {
            TipoEquipo::updateOrCreate(['nombre' => $tipo['nombre']], $tipo);
        }

        // -- Marcas --
        $marcas = ['Dell', 'HP', 'Epson', 'Generic Cable'];
        foreach ($marcas as $nombre) {
            Marca::updateOrCreate(['nombre' => $nombre], ['is_deleted' => false]);
        }

        // -- Modelos --
        $modelos = [
            ['nombre' => 'Latitude 7490', 'marca' => 'Dell', 'imagen_normal' => null, 'imagen_glb' => null, 'is_deleted' => false],
            ['nombre' => 'EliteBook 840', 'marca' => 'HP', 'imagen_normal' => null, 'imagen_glb' => null, 'is_deleted' => false],
            ['nombre' => 'PowerLite X39', 'marca' => 'Epson', 'imagen_normal' => null, 'imagen_glb' => null, 'is_deleted' => false],
            ['nombre' => 'Cable HDMI 2m', 'marca' => 'Generic Cable', 'imagen_normal' => null, 'imagen_glb' => null, 'is_deleted' => false],
        ];
        foreach ($modelos as $mod) {
            $marca = Marca::where('nombre', $mod['marca'])->first();
            Modelo::updateOrCreate(
                ['nombre' => $mod['nombre'], 'marca_id' => $marca->id],
                [
                    'imagen_normal' => $mod['imagen_normal'],
                    'imagen_glb' => $mod['imagen_glb'],
                    'is_deleted' => false,
                    'marca_id' => $marca->id,
                ]
            );
        }

        // -- Estados --
        $estados = ['Disponible', 'En reparación', 'En reposo', 'Dañado'];
        foreach ($estados as $nombre) {
            Estado::updateOrCreate(['nombre' => $nombre], ['is_deleted' => false]);
        }

        // -- Obtener IDs para asignaciones --
        $tipoLaptop = TipoEquipo::where('nombre', 'Laptop')->first();
        $tipoProyector = TipoEquipo::where('nombre', 'Proyector')->first();
        $tipoCable = TipoEquipo::where('nombre', 'Cable')->first();

        $marcaDell = Marca::where('nombre', 'Dell')->first();
        $marcaHP = Marca::where('nombre', 'HP')->first();
        $marcaEpson = Marca::where('nombre', 'Epson')->first();
        $marcaCable = Marca::where('nombre', 'Generic Cable')->first();

        $modeloDell = Modelo::where('nombre', 'Latitude 7490')->where('marca_id', $marcaDell->id)->first();
        $modeloHP = Modelo::where('nombre', 'EliteBook 840')->where('marca_id', $marcaHP->id)->first();
        $modeloEpson = Modelo::where('nombre', 'PowerLite X39')->where('marca_id', $marcaEpson->id)->first();
        $modeloCable = Modelo::where('nombre', 'Cable HDMI 2m')->where('marca_id', $marcaCable->id)->first();

        $estadoDisponible = Estado::where('nombre', 'Disponible')->first();

        $tipoReservaId = TipoReserva::first()?->id ?? null;

        // -- Características --
        $caracteristicasData = [
            ['nombre' => 'Color', 'tipo_dato' => 'string'],
            ['nombre' => 'Largo (metros)', 'tipo_dato' => 'decimal'],
            ['nombre' => 'Voltaje', 'tipo_dato' => 'integer'],
            ['nombre' => 'Peso (kg)', 'tipo_dato' => 'decimal'],
            ['nombre' => 'Tamaño pantalla (pulgadas)', 'tipo_dato' => 'decimal'],
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
            $caracteristicas['Color']->id,
            $caracteristicas['Peso (kg)']->id,
            $caracteristicas['Tamaño pantalla (pulgadas)']->id,
        ]);
        $tipoProyector->caracteristicas()->syncWithoutDetaching([
            $caracteristicas['Peso (kg)']->id,
            $caracteristicas['Tamaño pantalla (pulgadas)']->id,
        ]);
        $tipoCable->caracteristicas()->syncWithoutDetaching([
            $caracteristicas['Largo (metros)']->id,
            $caracteristicas['Voltaje']->id,
        ]);

        // -- Insertar equipos con número de serie (o únicos) --
        $equipos = [
            [
                'tipo_equipo_id' => $tipoLaptop->id,
                'modelo_id' => $modeloDell->id,
                'estado_id' => $estadoDisponible->id,
                'tipo_reserva_id' => $tipoReservaId,
                'numero_serie' => 'SN1234567890',
                'vida_util' => 5,
                'comentario' => null,
                'detalles' => 'Laptop para desarrollo',
                'fecha_adquisicion' => '2022-01-15',
                'is_deleted' => false,
                'caracteristicas' => [
                    'Color' => 'Negro',
                    'Peso (kg)' => '1.8',
                    'Tamaño pantalla (pulgadas)' => '14',
                ],
                'es_componente' => false,
            ],
            [
                'tipo_equipo_id' => $tipoLaptop->id,
                'modelo_id' => $modeloHP->id,
                'estado_id' => $estadoDisponible->id,
                'tipo_reserva_id' => $tipoReservaId,
                'numero_serie' => 'SN0987654321',
                'vida_util' => 4,
                'comentario' => null,
                'detalles' => 'Laptop de oficina',
                'fecha_adquisicion' => '2021-06-30',
                'is_deleted' => false,
                'caracteristicas' => [
                    'Color' => 'Gris',
                    'Peso (kg)' => '2.0',
                    'Tamaño pantalla (pulgadas)' => '15.6',
                ],
                'es_componente' => false,
            ],
            [
                'tipo_equipo_id' => $tipoProyector->id,
                'modelo_id' => $modeloEpson->id,
                'estado_id' => $estadoDisponible->id,
                'tipo_reserva_id' => $tipoReservaId,
                'numero_serie' => 'SN1122334455',
                'vida_util' => 6,
                'comentario' => null,
                'detalles' => 'Proyector para salas',
                'fecha_adquisicion' => '2020-09-10',
                'is_deleted' => false,
                'caracteristicas' => [
                    'Peso (kg)' => '3.5',
                    'Tamaño pantalla (pulgadas)' => '100',
                ],
                'es_componente' => false,
            ],
        ];

        // -- Guardar equipos con número de serie --
        foreach ($equipos as $data) {
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

        // -- Insertar 3 cables HDMI sin número de serie --
        for ($i = 0; $i < 3; $i++) {
            $equipo = Equipo::create([
                'tipo_equipo_id' => $tipoCable->id,
                'modelo_id' => $modeloCable->id,
                'estado_id' => $estadoDisponible->id,
                'tipo_reserva_id' => $tipoReservaId,
                'numero_serie' => null,
                'vida_util' => null,
                'comentario' => null,
                'detalles' => 'Cable HDMI 2 metros',
                'fecha_adquisicion' => '2023-03-20',
                'is_deleted' => false,
                'es_componente' => true,
            ]);

            $caracteristicasCable = [
                'Largo (metros)' => '2.0',
                'Voltaje' => '220',
            ];

            foreach ($caracteristicasCable as $nombreCarac => $valor) {
                $carac = $caracteristicas[$nombreCarac] ?? null;
                if ($carac) {
                    ValoresCaracteristica::create([
                        'equipo_id' => $equipo->id,
                        'caracteristica_id' => $carac->id,
                        'valor' => $valor,
                    ]);
                }
            }
        }
    }
}
