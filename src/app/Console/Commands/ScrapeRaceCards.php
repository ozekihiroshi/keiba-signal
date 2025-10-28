<?php

namespace App\Console\Commands;

use App\Jobs\FetchUrlJob;
use Illuminate\Console\Command;

class ScrapeRaceCards extends Command
{
    protected $signature = 'scrape:race-cards {url*} {--source=App\\Sources\\ExampleSource}';
    protected $description = 'Fetch race cards from given URLs and parse with source class';

    public function handle(): int
    {
        $source = $this->option('source');
        foreach ($this->argument('url') as $url) {
            FetchUrlJob::dispatch($url, $source);
            $this->info("Queued: $url");
        }
        return self::SUCCESS;
    }
}
