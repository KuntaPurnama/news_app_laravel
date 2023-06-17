<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\NewYorkTimesAPIController;
use App\Http\Controllers\NewsAPIController;

class TopNewsCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'top-news:cron';

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
        $nycController = new NewYorkTimesAPIController();
        $newsApiController = new NewsAPIController();
        $nycController->getTopStoriesNews();
        $newsApiController->getTopHeadlinesNewsAPI('business');
        $newsApiController->getTopHeadlinesNewsAPI('entertainment');
        $newsApiController->getTopHeadlinesNewsAPI('general');
        $newsApiController->getTopHeadlinesNewsAPI('health');
        $newsApiController->getTopHeadlinesNewsAPI('science');
        $newsApiController->getTopHeadlinesNewsAPI('sports');
        $newsApiController->getTopHeadlinesNewsAPI('technology');
    }
}
