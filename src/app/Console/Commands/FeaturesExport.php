<?php

namespace App\Console\Commands;

use App\Models\Race;
use Illuminate\Console\Command;

class FeaturesExport extends Command
{
    protected $signature = 'features:export {--out=/var/www/html/storage/app/features.csv}';
    protected $description = 'Export simple features to CSV for training';

    public function handle(): int
    {
        $out = $this->option('out');
        $fh = fopen($out, 'w');
        fputcsv($fh, ['race_id','horse_no','distance','surface_TURF','surface_DIRT','is_favorite','finish']); // finishは教師信号

        // TODO: 過去データから埋める
        foreach (Race::with('entries')->get() as $race) {
            foreach ($race->entries as $e) {
                fputcsv($fh, [
                    $race->id,
                    $e->horse_no,
                    $race->distance,
                    $race->surface === 'TURF' ? 1 : 0,
                    $race->surface === 'DIRT' ? 1 : 0,
                    ($e->morning_odds && $e->morning_odds < 5.0) ? 1 : 0,
                    null, // 確定後に1/0で埋める（例：3着以内=1）
                ]);
            }
        }
        fclose($fh);
        $this->info("Wrote: $out");
        return self::SUCCESS;
    }
}
