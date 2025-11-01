<?php

namespace App\Jobs;

use App\Models\Ingest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SummarizeIngestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $ingestId) {}

    public function handle(): void
    {
        $ingest = Ingest::find($this->ingestId);
        if (!$ingest) return;

        $title = trim((string)$ingest->title);
        $raw = (string)($ingest->summary_raw ?? '');
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($raw)));

        // 生本文が空なら「タイトルのみ」（余計なダッシュは付けない）
        $summary = $clean === ''
            ? $title
            : ($title !== '' ? ($title . ' — ' . Str::limit($clean, 240)) : Str::limit($clean, 240));

        $ingest->summary = $summary ?: null;
        $ingest->save();
    }
}
