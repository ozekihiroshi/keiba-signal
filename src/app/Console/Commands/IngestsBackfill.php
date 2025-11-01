<?php

namespace App\Console\Commands;

use App\Jobs\FetchOgpForIngest;
use App\Jobs\SummarizeIngestJob;
use App\Models\Ingest;
use Illuminate\Console\Command;

class IngestsBackfill extends Command
{
    protected $signature = 'ingests:backfill
        {--fix-urls : Decode & canon URLs saved with &amp; etc.}
        {--ogp : Fetch OGP images for missing image_url}
        {--resummarize : Re-run summarization for all}
        {--all : Do all of the above}';

    protected $description = 'Backfill ingests: URL canonicalization, OGP fetch, and re-summarize.';

    public function handle(): int
    {
        $all = (bool) $this->option('all');
        $doFix = $all || (bool) $this->option('fix-urls');
        $doOgp = $all || (bool) $this->option('ogp');
        $doSum = $all || (bool) $this->option('resummarize');

        $count = 0;
        foreach (Ingest::cursor() as $ing) {
            $dirty = false;

            if ($doFix) {
                $origUrl = $ing->url;
                $origGuid = $ing->guid;

                // HTMLエンティティ解除（&amp; → &）と UTM除去・クエリソート
                $canon = fn($u) => \App\Support\Url::canon(html_entity_decode((string)$u, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if ($origUrl) {
                    $ing->url = $canon($origUrl);
                    if ($ing->url !== $origUrl) $dirty = true;
                }
                if ($origGuid) {
                    $ing->guid = $canon($origGuid);
                    if ($ing->guid !== $origGuid) $dirty = true;
                }

                // 新しい規則で hash を再計算（URL or GUID）
                $newHash = hash('sha256', $ing->url ?: $ing->guid ?: '');
                if ($newHash !== $ing->hash) {
                    $ing->hash = $newHash;
                    $dirty = true;
                }
            }

            if ($dirty) {
                $ing->save();
            }

            if ($doOgp && empty($ing->image_url) && !empty($ing->url)) {
                FetchOgpForIngest::dispatch($ing->id);
            }
            if ($doSum) {
                SummarizeIngestJob::dispatch($ing->id);
            }

            $count++;
        }

        $this->info("Processed {$count} ingests.");
        return self::SUCCESS;
    }
}
