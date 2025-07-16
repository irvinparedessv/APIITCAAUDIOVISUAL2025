<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoEquipo;

class TipoEquipoSeeder extends Seeder
{
    public function run()
    {
        $tipos = [
            ['nombre' => 'Proyector',         'is_deleted' => false],
            ['nombre' => 'Laptop',            'is_deleted' => false],
            ['nombre' => 'Micrófono',         'is_deleted' => false],
            ['nombre' => 'Parlante',          'is_deleted' => false],
            ['nombre' => 'Pantalla LED',      'is_deleted' => false],
            ['nombre' => 'Cámara Web',        'is_deleted' => false],
            ['nombre' => 'Servidor',          'is_deleted' => false],
            ['nombre' => 'Router',            'is_deleted' => false],
            ['nombre' => 'Switch',            'is_deleted' => false],
            ['nombre' => 'Tablet Gráfica',    'is_deleted' => false],
            ['nombre' => 'Sistema de Videoconferencia','is_deleted' => false],
            ['nombre' => 'Control Remoto de Presentación','is_deleted' => false],
        ];

        foreach ($tipos as $tipo) {
            TipoEquipo::create($tipo);
        }
    }
}
