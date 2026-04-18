<?php

namespace App\Console;

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
    protected $commands = [
        \App\Console\Commands\DeleteUnverifiedUsers::class,
        \App\Console\Commands\RefreshLockedEmploymentStatuses::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('users:cleanup-unverified')->everyMinute();

        if (config('recruitment.locked_employment_refresh.enabled', true)) {
            $schedule->command('users:refresh-locked-employment-statuses')
                ->cron(config('recruitment.locked_employment_refresh.cron', '*/30 * * * *'))
                ->withoutOverlapping();
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
