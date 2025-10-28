<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class PredictRace extends Command
{
    protected $signature = 'predict:race {csv=/var/www/html/storage/app/features.csv}';
    protected $description = 'Run Python model to predict probabilities from CSV';

    public function handle(): int
    {
        $csv = $this->argument('csv');
        $py = base_path('scripts/predict.py');
        if (!file_exists($py)) {
            $this->error("Missing $py");
            return self::FAILURE;
        }
        $p = new Process(['python3', $py, $csv]);
        $p->setTimeout(120);
        $p->run();
        $this->line($p->getOutput());
        if (!$p->isSuccessful()) { $this->error($p->getErrorOutput()); return self::FAILURE; }
        return self::SUCCESS;
    }
}
