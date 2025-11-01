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
        if (!$ingest) return;

        // すでに画像があるなら何もしない
        if (!empty($ingest->image_url)) return;
        if (empty($ingest->url)) return;

        $client = OgpClient::fromConfig();
        $og = $client->fetch($ingest->url);

        $dirty = false;
        if (($og['image'] ?? '') !== '') {
            $ingest->image_url = $og['image'];
            $dirty = true;
        }
        // description が空なら、OGP説明で穴を埋める
        if (empty($ingest->summary_raw) && !empty($og['description'])) {
            $ingest->summary_raw = $og['description'];
            $dirty = true;
        }

        if ($dirty) {
            $ingest->save();
        }
    }
}
