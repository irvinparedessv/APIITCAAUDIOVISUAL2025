<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;
use App\Models\Equipo;
use App\Models\Estado;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\TipoEquipo;
use App\Models\TipoReserva;

class EquipoSeeder extends Seeder
{
    public function run()
    {
        // Obtener IDs de categorías
        $equipoCategoriaId = Categoria::where('nombre', 'Equipo')->first()->id;
        
        // Insertar tipos de equipo con categoría
        $tipos = [
            ['nombre' => 'Laptop', 'categoria_id' => $equipoCategoriaId, 'is_deleted' => false],
            ['nombre' => 'Proyector', 'categoria_id' => $equipoCategoriaId, 'is_deleted' => false],
        ];

        foreach ($tipos as $tipo) {
            TipoEquipo::updateOrCreate(['nombre' => $tipo['nombre']], $tipo);
        }

        // Insertar marcas
        $marcas = ['Dell', 'HP', 'Epson'];
        foreach ($marcas as $marca) {
            Marca::updateOrCreate(['nombre' => $marca], ['is_deleted' => false]);
        }

        // Obtener IDs de marcas para modelos
        $dellId = Marca::where('nombre', 'Dell')->first()->id;
        $hpId = Marca::where('nombre', 'HP')->first()->id;
        $epsonId = Marca::where('nombre', 'Epson')->first()->id;

        // Insertar modelos
        $modelos = [
            ['nombre' => 'Latitude 7490', 'marca_id' => $dellId, 'imagen_normal' => null, 'imagen_gbl' => null, 'is_deleted' => false],
            ['nombre' => 'EliteBook 840', 'marca_id' => $hpId, 'imagen_normal' => null, 'imagen_gbl' => null, 'is_deleted' => false],
            ['nombre' => 'PowerLite X39', 'marca_id' => $epsonId, 'imagen_normal' => null, 'imagen_gbl' => null, 'is_deleted' => false],
        ];
        foreach ($modelos as $modelo) {
            Modelo::updateOrCreate(
                ['nombre' => $modelo['nombre'], 'marca_id' => $modelo['marca_id']],
                $modelo
            );
        }

        // Insertar estados
        $estados = ['Disponible', 'En reparación', 'En reposo', 'Dañado'];
        foreach ($estados as $estado) {
            Estado::updateOrCreate(['nombre' => $estado], ['is_deleted' => false]);
        }

        // Obtener IDs necesarios para insertar equipos
        $laptopTipoId = TipoEquipo::where('nombre', 'Laptop')->first()->id;
        $proyectorTipoId = TipoEquipo::where('nombre', 'Proyector')->first()->id;

        $dellModeloId = Modelo::where('nombre', 'Latitude 7490')->where('marca_id', $dellId)->first()->id;
        $hpModeloId = Modelo::where('nombre', 'EliteBook 840')->where('marca_id', $hpId)->first()->id;
        $epsonModeloId = Modelo::where('nombre', 'PowerLite X39')->where('marca_id', $epsonId)->first()->id;

        $disponibleEstadoId = Estado::where('nombre', 'Disponible')->first()->id;

        // Obtener algún tipo reserva para asignar, si quieres dejar null pon null
        $tipoReservaId = TipoReserva::first()?->id ?? null;

        // Insertar equipos
        $equipos = [
            [
                'tipo_equipo_id' => $laptopTipoId,
                'marca_id' => $dellId,
                'modelo_id' => $dellModeloId,
                'estado_id' => $disponibleEstadoId,
                'tipo_reserva_id' => $tipoReservaId,
                'numero_serie' => 'SN1234567890',  // único
                'vida_util' => 5,
                'detalles' => 'Laptop para desarrollo',
                'fecha_adquisicion' => '2022-01-15',
                'is_deleted' => false,
            ],
            [
                'tipo_equipo_id' => $laptopTipoId,
                'marca_id' => $hpId,
                'modelo_id' => $hpModeloId,
                'estado_id' => $disponibleEstadoId,
                'tipo_reserva_id' => $tipoReservaId,
                'numero_serie' => 'SN0987654321',  // único
                'vida_util' => 4,
                'detalles' => 'Laptop de oficina',
                'fecha_adquisicion' => '2021-06-30',
                'is_deleted' => false,
            ],
            [
                'tipo_equipo_id' => $proyectorTipoId,
                'marca_id' => $epsonId,
                'modelo_id' => $epsonModeloId,
                'estado_id' => $disponibleEstadoId,
                'tipo_reserva_id' => $tipoReservaId,
                'numero_serie' => 'SN1122334455',  // único
                'vida_util' => 6,
                'detalles' => 'Proyector para salas',
                'fecha_adquisicion' => '2020-09-10',
                'is_deleted' => false,
            ],

        ];

        foreach ($equipos as $equipo) {
            Equipo::updateOrCreate(
                ['numero_serie' => $equipo['numero_serie'] ?? 'no_serial_' . uniqid()],
                $equipo
            );
        }
    }
}
