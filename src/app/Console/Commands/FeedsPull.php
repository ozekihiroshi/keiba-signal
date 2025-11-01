<?php

namespace App\Console\Commands;

use App\Models\Ingest;
use App\Models\Source;
use App\Services\Feeds\JraRssClient;
use Illuminate\Console\Command;
use App\Jobs\SummarizeIngestJob;
use App\Jobs\FetchOgpForIngest;

class FeedsPull extends Command
{
    protected $signature = 'feeds:pull {--source=jra}';
    protected $description = 'Pull news feeds and upsert into ingests';

    public function handle(): int
    {
        $sourceOpt = strtolower((string)$this->option('source'));

        if ($sourceOpt !== 'jra') {
            $this->error("Unknown source: {$sourceOpt}");
            return self::INVALID;
        }

        $client = JraRssClient::fromConfig();

        $source = Source::query()->firstOrCreate(
            ['code' => 'jra'],
            [
                'name' => 'Horse Racing in Japan (JRA, English RSS)',
                'type' => 'rss',
                'base_url' => 'https://japanracing.jp/en/',
                'rss_url' => config('feeds.sources.jra.url'),
                'license_tag' => config('feeds.sources.jra.license_tag'),
                'robots_allowed' => true,
                'fetch_interval_minutes' => 30,
            ]
        );

        $items = $client->fetch();
        $countUpsert = 0;

        foreach ($items as $it) {
            $url  = (string)($it['url'] ?? '');
            $guid = (string)($it['guid'] ?? '');

            if ($url === '' && $guid === '') {
                continue;
            }

            $hash = hash('sha256', $url ?: $guid);

            // ※ status は渡さない（既存publishedを壊さない）
            $payload = [
                'url'          => $url,
                'title'        => $it['title'] ?? '',
                'summary_raw'  => $it['description'] ?? null,
                'image_url'    => $it['image_url'] ?? null,
                'published_at' => $it['published_at'] ?? null,
                'license_tag'  => 'news',
                'raw_json'     => $it['raw'] ?? null,
                'lang'         => 'en',
                'hash'         => $hash,
            ];

            $ingest = Ingest::query()->updateOrCreate(
                [
                    'source_id' => $source->id,
                    'guid'      => $guid ?: $url ?: $hash,
                ],
                $payload
            );

            if (empty($ingest->summary)) {
                SummarizeIngestJob::dispatch($ingest->id);
            }
            if (empty($ingest->image_url) && !empty($ingest->url)) {
                FetchOgpForIngest::dispatch($ingest->id);
            }

            $countUpsert++;
        }

        $source->update(['last_fetched_at' => now()]);

        $this->info("Source={$sourceOpt} upserted={$countUpsert}");
        return self::SUCCESS;
    }
}
