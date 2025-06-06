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
        $servidores = [
            'Dell PowerEdge R640', 'HPE ProLiant DL360 Gen10', 'Lenovo ThinkSystem SR630',
            'Cisco UCS C220 M5', 'Fujitsu PRIMERGY RX2540 M5', 'Supermicro SuperServer 1029U',
            'Asus ESC4000 G4', 'Intel Server System R2312', 'IBM Power System S914',
            'Oracle SPARC T8-1'
        ];

        $routers = [
            'Cisco ISR 4221', 'MikroTik RB3011', 'Ubiquiti EdgeRouter X', 'TP-Link Archer C80',
            'Asus RT-AC86U', 'Netgear Nighthawk X6', 'Huawei AR1220', 'Juniper SRX320',
            'D-Link DIR-878', 'Zyxel Armor Z2'
        ];

        $laptops = [
            'Dell XPS 13 9300', 'HP Spectre x360', 'Lenovo ThinkPad X1 Carbon Gen 8',
            'Apple MacBook Air 2020', 'Acer Swift 3', 'ASUS ZenBook 13 UX325',
            'Microsoft Surface Laptop 3', 'Razer Blade Stealth 13', 'LG Gram 14 2020',
            'MSI Prestige 14'
        ];

        // === Insertar servidores
        foreach ($servidores as $nombre) {
            Equipo::create([
                'nombre' => "Servidor $nombre",
                'descripcion' => "Servidor lanzado en 2020: $nombre",
                'estado' => rand(0, 1),
                'cantidad' => rand(1, 4),
                'is_deleted' => false,
                'tipo_equipo_id' => $servidorId,
                'tipo_reserva_id' => $reservaEventosId,
                'imagen' => 'default.png'
            ]);
        }

        // === Insertar routers
        foreach ($routers as $nombre) {
            Equipo::create([
                'nombre' => "Router $nombre",
                'descripcion' => "Router popular en 2020: $nombre",
                'estado' => rand(0, 1),
                'cantidad' => rand(1, 6),
                'is_deleted' => false,
                'tipo_equipo_id' => $routerId,
                'tipo_reserva_id' => $reservaReunionId,
                'imagen' => 'default.png'
            ]);
        }

        // === Insertar laptops
        foreach ($laptops as $nombre) {
            Equipo::create([
                'nombre' => "Laptop $nombre",
                'descripcion' => "Laptop usada en 2020: $nombre",
                'estado' => rand(0, 1),
                'cantidad' => rand(2, 10),
                'is_deleted' => false,
                'tipo_equipo_id' => $laptopId,
                'tipo_reserva_id' => $reservaClaseId,
                'imagen' => 'default.png'
            ]);
        }
    }
}
