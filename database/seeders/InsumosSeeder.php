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
use Illuminate\Support\Str;

class InsumosSeeder extends Seeder
{
    public function run()
    {
        // 1. Configuración inicial - Crear entidades básicas si no existen
        $this->crearEntidadesBasicas();

        // 2. Crear modelos de insumos
        $modelosInsumo = $this->crearModelosInsumos();

        // 3. Asociar insumos a equipos existentes
        $this->asociarInsumosAEquipos($modelosInsumo);

        // 4. Crear stock adicional de insumos
        $this->crearStockAdicional($modelosInsumo);
    }

    protected function crearEntidadesBasicas()
    {
        // Categoría Insumo
        $categoriaInsumo = Categoria::updateOrCreate(
            ['nombre' => 'Insumo'],
            ['is_deleted' => false]
        );

        // Tipos de reserva (asegurarse que existan)
        TipoReserva::updateOrCreate(
            ['nombre' => 'Clase'],
            ['is_deleted' => false]
        );
        TipoReserva::updateOrCreate(
            ['nombre' => 'Evento'],
            ['is_deleted' => false]
        );

        // Tipos de Insumo
        TipoEquipo::updateOrCreate(
            ['nombre' => 'Cable'],
            ['categoria_id' => $categoriaInsumo->id, 'is_deleted' => false]
        );
        TipoEquipo::updateOrCreate(
            ['nombre' => 'Adaptador'],
            ['categoria_id' => $categoriaInsumo->id, 'is_deleted' => false]
        );
        TipoEquipo::updateOrCreate(
            ['nombre' => 'Control'],
            ['categoria_id' => $categoriaInsumo->id, 'is_deleted' => false]
        );

        // Marcas para insumos
        $marcasInsumos = ['Belkin', 'UGreen', 'Amazon Basics', 'Startech', 'Anker', 'Epson', 'Logitech'];
        foreach ($marcasInsumos as $nombre) {
            Marca::updateOrCreate(['nombre' => $nombre], ['is_deleted' => false]);
        }

        // Estado Disponible
        Estado::updateOrCreate(
            ['nombre' => 'Disponible'],
            ['is_deleted' => false]
        );
    }

    protected function crearModelosInsumos()
    {
        $modelosInsumo = [];

        $insumosBase = [
            // Cables HDMI
            [
                'nombre' => 'Cable HDMI Ultra HD 2m',
                'marca' => 'UGreen',
                'tipo' => 'Cable',
                'imagen_glb' => 'models/cables/hdmi_ugreen.glb',
                'asignar_a' => ['Laptop', 'Proyector']
            ],
            [
                'nombre' => 'Cable HDMI Premium 3m',
                'marca' => 'Belkin',
                'tipo' => 'Cable',
                'imagen_glb' => 'models/cables/hdmi_belkin.glb',
                'asignar_a' => ['Proyector']
            ],
            
            // Cables VGA
            [
                'nombre' => 'Cable VGA 3m',
                'marca' => 'Amazon Basics',
                'tipo' => 'Cable',
                'imagen_glb' => 'models/cables/vga_amazon.glb',
                'asignar_a' => ['Proyector']
            ],
            
            // Adaptadores
            [
                'nombre' => 'Adaptador USB-C a HDMI',
                'marca' => 'Anker',
                'tipo' => 'Adaptador',
                'imagen_glb' => 'models/adaptadores/usbc_hdmi_anker.glb',
                'asignar_a' => ['Laptop']
            ],
            [
                'nombre' => 'Extensor HDMI 10m',
                'marca' => 'Startech',
                'tipo' => 'Adaptador',
                'imagen_glb' => 'models/adaptadores/extensor_hdmi.glb',
                'asignar_a' => ['Proyector']
            ],
            
            // Controles
            [
                'nombre' => 'Control Remoto Proyector',
                'marca' => 'Epson',
                'tipo' => 'Control',
                'imagen_glb' => 'models/controles/epsom_remote.glb',
                'asignar_a' => ['Proyector']
            ],
            [
                'nombre' => 'Control Bluetooth Presentador',
                'marca' => 'Logitech',
                'tipo' => 'Control',
                'imagen_glb' => 'models/controles/logitech_presenter.glb',
                'asignar_a' => ['Laptop']
            ],
            
            // Cables de alimentación
            [
                'nombre' => 'Cable de alimentación universal',
                'marca' => 'Belkin',
                'tipo' => 'Cable',
                'imagen_glb' => 'models/cables/power_belkin.glb',
                'asignar_a' => ['Parlante', 'Micrófono']
            ],
            
            // Cables USB
            [
                'nombre' => 'Cable USB 3.0',
                'marca' => 'Startech',
                'tipo' => 'Cable',
                'imagen_glb' => 'models/cables/usb_startech.glb',
                'asignar_a' => ['Mouse', 'Micrófono']
            ]
        ];

        foreach ($insumosBase as $insumo) {
            $marca = Marca::where('nombre', $insumo['marca'])->first();
            $tipoEquipo = TipoEquipo::where('nombre', $insumo['tipo'])->first();
            
            if (!$marca || !$tipoEquipo) {
                continue;
            }
            
            $modelo = Modelo::updateOrCreate(
                ['nombre' => $insumo['nombre'], 'marca_id' => $marca->id],
                [
                    'imagen_glb' => $insumo['imagen_glb'],
                    'is_deleted' => false,
                    'reposo' => 0,
                    'escala' => 1
                ]
            );
            
            $modelosInsumo[$insumo['nombre']] = [
                'modelo' => $modelo,
                'tipo_equipo' => $tipoEquipo,
                'asignar_a' => $insumo['asignar_a']
            ];
        }

        return $modelosInsumo;
    }

    protected function asociarInsumosAEquipos($modelosInsumo)
    {
        $estadoDisponible = Estado::where('nombre', 'Disponible')->first();
        $equipos = Equipo::with('modelo', 'tipoEquipo')->get();

        foreach ($equipos as $equipo) {
            if (!$equipo->tipoEquipo) {
                continue;
            }

            $insumosParaEsteTipo = array_filter($modelosInsumo, function($insumo) use ($equipo) {
                return in_array($equipo->tipoEquipo->nombre, $insumo['asignar_a']);
            });

            foreach (array_slice($insumosParaEsteTipo, 0, 2) as $insumoData) {
                ModeloAccesorio::updateOrCreate([
                    'modelo_equipo_id' => $equipo->modelo_id,
                    'modelo_insumo_id' => $insumoData['modelo']->id,
                ]);

                $numeroSerieIns = $equipo->numero_serie . '-ACC-' . Str::substr($insumoData['modelo']->nombre, 0, 3);

                $insumoEquipo = Equipo::updateOrCreate(
                    [
                        'modelo_id' => $insumoData['modelo']->id,
                        'tipo_equipo_id' => $insumoData['tipo_equipo']->id,
                        'serie_asociada' => $equipo->numero_serie,
                        'es_componente' => true,
                        'estado_id' => $estadoDisponible->id,
                        'tipo_reserva_id' => $equipo->tipo_reserva_id,
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

    protected function crearStockAdicional($modelosInsumo)
    {
        $estadoDisponible = Estado::where('nombre', 'Disponible')->first();
        $tipoReservaClase = TipoReserva::where('nombre', 'Clase')->first();
        $tipoReservaEvento = TipoReserva::where('nombre', 'Evento')->first();

        foreach ($modelosInsumo as $nombre => $insumoData) {
            // Stock para clases
            for ($i = 1; $i <= 3; $i++) {
                Equipo::updateOrCreate(
                    [
                        'modelo_id' => $insumoData['modelo']->id,
                        'tipo_equipo_id' => $insumoData['tipo_equipo']->id,
                        'estado_id' => $estadoDisponible->id,
                        'tipo_reserva_id' => $tipoReservaClase->id,
                        'es_componente' => true,
                        'fecha_adquisicion' => now(),
                        'is_deleted' => false,
                    ]
                );
            }
            
            // Stock para eventos (solo cables y controles)
            if (in_array($insumoData['tipo_equipo']->nombre, ['Cable', 'Control'])) {
                for ($i = 1; $i <= 2; $i++) {
                    Equipo::updateOrCreate(
                        [
                            'modelo_id' => $insumoData['modelo']->id,
                            'tipo_equipo_id' => $insumoData['tipo_equipo']->id,
                            'estado_id' => $estadoDisponible->id,
                            'tipo_reserva_id' => $tipoReservaEvento->id,
                            'es_componente' => true,
                            'fecha_adquisicion' => now(),
                            'is_deleted' => false,
                        ]
                    );
                }
            }
        }
    }
}