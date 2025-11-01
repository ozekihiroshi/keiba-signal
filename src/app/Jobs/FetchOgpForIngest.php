<?php

namespace App\Jobs;

use App\Models\Ingest;
use App\Services\Web\OgpClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchOgpForIngest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $ingestId) {}

    public function handle(): void
    {
        $ingest = Ingest::find($this->ingestId);
        if (!$ingest || empty($ingest->url)) return;

        // 既に画像があっても「汎用OGP」なら取り直す
        $hasGeneric = $ingest->image_url && preg_match('#/common/img/ogp\.jpg$#i', $ingest->image_url);
        if (!empty($ingest->image_url) && !$hasGeneric) {
            return;
        }

        $client = OgpClient::fromConfig();
        $og = $client->fetch($ingest->url);

        $dirty = false;
        if (!empty($og['image'])) {
            $ingest->image_url = $og['image'];
            $dirty = true;
        }
        if (empty($ingest->summary_raw) && !empty($og['description'])) {
            $ingest->summary_raw = $og['description'];
            $dirty = true;
        }

        if ($dirty) {
            $ingest->save();
        }
    }
}
