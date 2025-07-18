<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;
use App\Models\Insumo;
use App\Models\Estado;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\TipoEquipo;
use App\Models\TipoReserva;

class InsumoSeeder extends Seeder
{
    public function run()
    {
        // Obtener ID de categoría Insumo
        $insumoCategoriaId = Categoria::where('nombre', 'Insumo')->first()->id;
        
        // Insertar tipos de insumo con categoría
        $tipos = [
            ['nombre' => 'USB', 'categoria_id' => $insumoCategoriaId, 'is_deleted' => false],
            ['nombre' => 'Cable HDMI', 'categoria_id' => $insumoCategoriaId, 'is_deleted' => false],
            ['nombre' => 'Mouse', 'categoria_id' => $insumoCategoriaId, 'is_deleted' => false],
        ];

        foreach ($tipos as $tipo) {
            TipoEquipo::updateOrCreate(['nombre' => $tipo['nombre'], 'categoria_id' => $tipo['categoria_id']], $tipo);
        }

        // Insertar marcas
        $marcas = ['Kingston', 'Logitech', 'Sony'];
        foreach ($marcas as $marca) {
            Marca::updateOrCreate(['nombre' => $marca], ['is_deleted' => false]);
        }

        // Obtener IDs de marcas para modelos
        $kingstonId = Marca::where('nombre', 'Kingston')->first()->id;
        $logitechId = Marca::where('nombre', 'Logitech')->first()->id;
        $sonyId = Marca::where('nombre', 'Sony')->first()->id;

        // Insertar modelos
        $modelos = [
            ['nombre' => 'USB 32GB', 'marca_id' => $kingstonId, 'imagen_normal' => null, 'imagen_gbl' => null, 'is_deleted' => false],
            ['nombre' => 'Cable HDMI 2m', 'marca_id' => $sonyId, 'imagen_normal' => null, 'imagen_gbl' => null, 'is_deleted' => false],
            ['nombre' => 'Mouse Inalámbrico', 'marca_id' => $logitechId, 'imagen_normal' => null, 'imagen_gbl' => null, 'is_deleted' => false],
        ];
        foreach ($modelos as $modelo) {
            Modelo::updateOrCreate(
                ['nombre' => $modelo['nombre'], 'marca_id' => $modelo['marca_id']],
                $modelo
            );
        }

        // Insertar estados
        $estados = ['Disponible', 'En uso', 'Dañado'];
        foreach ($estados as $estado) {
            Estado::updateOrCreate(['nombre' => $estado], ['is_deleted' => false]);
        }

        // Obtener IDs de tipos específicos
        $usbTipoId = TipoEquipo::where('nombre', 'USB')->where('categoria_id', $insumoCategoriaId)->first()->id;
        $cableHdmiTipoId = TipoEquipo::where('nombre', 'Cable HDMI')->where('categoria_id', $insumoCategoriaId)->first()->id;
        $mouseTipoId = TipoEquipo::where('nombre', 'Mouse')->where('categoria_id', $insumoCategoriaId)->first()->id;

        // Obtener modelos
        $usbModeloId = Modelo::where('nombre', 'USB 32GB')->where('marca_id', $kingstonId)->first()->id;
        $hdmiModeloId = Modelo::where('nombre', 'Cable HDMI 2m')->where('marca_id', $sonyId)->first()->id;
        $mouseModeloId = Modelo::where('nombre', 'Mouse Inalámbrico')->where('marca_id', $logitechId)->first()->id;

        // Obtener estados y tipo reserva
        $disponibleEstadoId = Estado::where('nombre', 'Disponible')->first()->id;
        $tipoReservaId = TipoReserva::first()?->id ?? null;

        // Insertar insumos
        $insumos = [
            [
                'tipo_equipo_id' => $usbTipoId,
                'marca_id' => $kingstonId,
                'modelo_id' => $usbModeloId,
                'estado_id' => $disponibleEstadoId,
                'tipo_reserva_id' => $tipoReservaId,
                'cantidad' => 50,
                'detalles' => 'USB 32GB para préstamo',
                'fecha_adquisicion' => '2024-01-10',
                'is_deleted' => false,
            ],
            [
                'tipo_equipo_id' => $cableHdmiTipoId,
                'marca_id' => $sonyId,
                'modelo_id' => $hdmiModeloId,
                'estado_id' => $disponibleEstadoId,
                'tipo_reserva_id' => $tipoReservaId,
                'cantidad' => 20,
                'detalles' => 'Cable HDMI de 2 metros',
                'fecha_adquisicion' => '2023-11-05',
                'is_deleted' => false,
            ],
            [
                'tipo_equipo_id' => $mouseTipoId,
                'marca_id' => $logitechId,
                'modelo_id' => $mouseModeloId,
                'estado_id' => $disponibleEstadoId,
                'tipo_reserva_id' => $tipoReservaId,
                'cantidad' => 15,
                'detalles' => 'Mouse inalámbrico Logitech',
                'fecha_adquisicion' => '2023-12-20',
                'is_deleted' => false,
            ],
        ];

        foreach ($insumos as $insumo) {
            Insumo::updateOrCreate(
                [
                    'tipo_equipo_id' => $insumo['tipo_equipo_id'],
                    'marca_id' => $insumo['marca_id'],
                    'modelo_id' => $insumo['modelo_id'],
                    'estado_id' => $insumo['estado_id'],
                ],
                $insumo
            );
        }
    }
}
