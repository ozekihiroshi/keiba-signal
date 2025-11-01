<?php

namespace App\Jobs;

use App\Models\Ingest;
use App\Services\Web\ArticleExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchArticleBodyForIngest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $ingestId) {}

    public function handle(): void
    {
        $ing = Ingest::find($this->ingestId);
        if (!$ing || empty($ing->url)) return;

        $ex = ArticleExtractor::fromConfig();
        $res = $ex->extract($ing->url);

        $dirty = false;
        if (!empty($res['html'])) {
            // フィードの description が空のときに本文抜粋を格納
            if (empty($ing->summary_raw)) {
                $ing->summary_raw = $res['html'];
                $dirty = true;
            }
        }
        if (!empty($res['image']) && (empty($ing->image_url) || preg_match('#/common/img/ogp\.jpg$#i',$ing->image_url))) {
            $ing->image_url = $res['image'];
            $dirty = true;
        }
        if ($dirty) {
            $ing->save();
            // 本文が入ったら改めて要約
            dispatch(new SummarizeIngestJob($ing->id));
        }
    }
}
