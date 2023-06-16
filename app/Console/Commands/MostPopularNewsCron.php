<?php

namespace App\Console\Commands;

use App\Http\Controllers\NewYorkTimesAPIController;
use Illuminate\Console\Command;

class MostPopularNewsCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'most-popular-news:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $nycController = new NewYorkTimesAPIController();
        $nycController->getMostPopular();
    }
}
