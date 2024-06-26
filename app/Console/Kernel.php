<?php

namespace App\Console;

use App\Jobs\CambiarEstadoDeDocumentosTaller;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
         //$schedule->command('app:cambia_estado_documentos')->everyMinute();
         $schedule->job(new CambiarEstadoDeDocumentosTaller)->daily();
         $schedule->command('migrar:documentos')->weeklyOn(6, '0:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    protected function scheduleTimezone()
    {
        return 'America/Lima';
    }
}
