<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Equipo;
use App\Models\TipoReserva;
use App\Models\TipoEquipo;

class EquipoSeeder extends Seeder
{
    public function run()
    {
        // Obtener IDs dinámicos de tipo_equipo
        $servidorId = TipoEquipo::where('nombre', 'Servidor')->value('id');
        $routerId = TipoEquipo::where('nombre', 'Router')->value('id');
        $laptopId = TipoEquipo::where('nombre', 'Laptop')->value('id');

        // Obtener IDs dinámicos de tipo_reserva
        $reservaEventosId = TipoReserva::where('nombre', 'Eventos')->value('id');
        $reservaReunionId = TipoReserva::where('nombre', 'Reunión')->value('id');
        $reservaClaseId = TipoReserva::where('nombre', 'Clase')->value('id');

        // === Equipos de 2020 ===
        $tiposEquipo = [
            'Proyector' => [
                'modelos' => [
                    'Epson PowerLite X49',
                    'BenQ MW535A',
                    'ViewSonic PA503W',
                    'LG PF50KA',
                    'Optoma HD146X',
                    'Sony VPL-EX575'
                ],
                'tipo_reserva' => $reservaClaseId
            ],
            'Micrófono' => [
                'modelos' => [
                    'Shure SM58',
                    'Blue Yeti',
                    'Rode Wireless GO II',
                    'Audio-Technica AT875R',
                    'Samson Go Mic',
                    'Sennheiser MKE 400'
                ],
                'tipo_reserva' => rand(0, 1) ? $reservaEventosId : $reservaReunionId
            ],
            'Parlante' => [
                'modelos' => [
                    'JBL EON610',
                    'Bose S1 Pro',
                    'Mackie Thump12A',
                    'Yamaha DBR10'
                ],
                'tipo_reserva' => $reservaEventosId
            ],
            'Pantalla LED' => [
                'modelos' => [
                    'Samsung Flip 2',
                    'LG LED Monitor 27UL500',
                    'ViewSonic TD2455'
                ],
                'tipo_reserva' => $reservaClaseId
            ],
            'Cámara Web' => [
                'modelos' => [
                    'Logitech C920',
                    'Razer Kiyo Pro',
                    'Microsoft LifeCam HD-3000'
                ],
                'tipo_reserva' => $reservaReunionId
            ],
            'Tablet Gráfica' => [
                'modelos' => [
                    'Wacom Intuos S',
                    'Huion H610 Pro V2',
                    'XP-PEN Deco 01 V2'
                ],
                'tipo_reserva' => $reservaClaseId
            ],
            'Sistema de Videoconferencia' => [
                'modelos' => [
                    'Logitech Rally Bar',
                    'Poly Studio X50',
                    'Cisco Webex Room Kit Mini'
                ],
                'tipo_reserva' => $reservaReunionId
            ],
            'Control Remoto de Presentación' => [
                'modelos' => [
                    'Logitech R400',
                    'Kensington Expert Presenter',
                    'DinoFire Wireless Presenter'
                ],
                'tipo_reserva' => $reservaClaseId
            ],
        ];

        // Iterar sobre cada tipo y sus modelos
        foreach ($tiposEquipo as $tipo => $data) {
            $tipoEquipoId = TipoEquipo::where('nombre', $tipo)->value('id');

            foreach ($data['modelos'] as $modelo) {
                Equipo::create([
                    'nombre' => "$tipo $modelo",
                    'descripcion' => "$tipo modelo $modelo disponible para reserva.",
                    'estado' => rand(0, 1),
                    'cantidad' => rand(1, 5),
                    'is_deleted' => false,
                    'tipo_equipo_id' => $tipoEquipoId,
                    'tipo_reserva_id' => is_int($data['tipo_reserva']) ? $data['tipo_reserva'] : (rand(0, 1) ? $reservaEventosId : $reservaReunionId),
                    'imagen' => 'default.png'
                ]);
            }
        }
    }
}
