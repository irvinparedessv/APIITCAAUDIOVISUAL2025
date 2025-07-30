<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Aquí registras tus comandos
        // Ejemplo:
        $schedule->command('equipos:revisar-vida-util')->dailyAt('00:05');
        $schedule->command('reservas:cancelar-vencidas')->dailyAt('00:05');
        $schedule->command('reservas:recordatorio-devolucion')->everyTenMinutes();
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
