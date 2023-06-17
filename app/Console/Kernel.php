<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        Commands\MostPopularNewsCron::class
    ];
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('most-popular-news:cron')->everyMinute();
        $schedule->command('review-article-news:cron')->everyMinute();
        $schedule->command('top-news:cron')->everyMinute();
        // $schedule->command('most-recent-news:cron')->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        // Commands\MostPopularNewsCron::class;

        require base_path('routes/console.php');
    }
}
