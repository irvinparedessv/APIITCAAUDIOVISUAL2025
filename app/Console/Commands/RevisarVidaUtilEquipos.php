<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\EmailVidaUtilEquipoNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RevisarVidaUtilEquipos extends Command
{
    protected $signature = 'equipos:revisar-vida-util';
    protected $description = 'Revisa la vida útil restante de los equipos y envía alerta si es menor a 24 horas';

    public function handle()
    {
        // Tomamos los datos desde la vista
        $equipos = DB::table('vista_equipos_vida_util')->get();

        $user = User::whereHas('role', function ($q) {
            $q->where('nombre', 'Administrador'); // Ajusta 'name' o 'nombre' según tu DB
        })->first();

        $equiposAlertados = [];

        foreach ($equipos as $equipo) {
            $vida_restante = ($equipo->vida_util + $equipo->vida_util_agregada_horas) - $equipo->tiempo_reserva_horas;
            if ($vida_restante < 24) {
                if ($user) {
                    $user->notify(new EmailVidaUtilEquipoNotification($equipo, $vida_restante));
                    $equiposAlertados[] = [
                        'id' => $equipo->equipo_id,
                        'modelo' => $equipo->modelo_nombre,
                        'serie' => $equipo->numero_serie,
                        'vida_restante' => $vida_restante
                    ];
                }
            }
        }

        $count = count($equiposAlertados);

        if ($user && $count > 0) {
            $this->info("Correo enviado a: {$user->email}");
            $this->info("Equipos alertados: $count");
            foreach ($equiposAlertados as $alertado) {
                $this->info("Equipo ID: {$alertado['id']}, Modelo: {$alertado['modelo']}, Serie: {$alertado['serie']}, Vida restante: {$alertado['vida_restante']} horas");
            }
        } elseif ($user) {
            $this->info("No hay equipos con vida útil menor a 24 horas para alertar. Correo de admin: {$user->email}");
        } else {
            $this->warn("No se encontró ningún usuario administrador para enviar el correo.");
        }

        $this->info('Revisión de vida útil ejecutada.');
    }
}
