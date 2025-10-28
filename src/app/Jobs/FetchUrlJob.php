<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class FetchUrlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $url, public string $sourceClass) {}

    public function handle(): void
    {
        $key = 'scrape:'.$this->sourceClass;
        RateLimiter::attempt($key, $perMinute = 30, function () {
            // pass
        }, 60); // 1分で30回

        $res = Http::retry(3, 500)->timeout(10)->get($this->url);
        if (!$res->ok()) { throw new \RuntimeException("HTTP ".$res->status()); }

        /** @var \App\Sources\AbstractSource $parser */
        $parser = app($this->sourceClass);
        $parser->parseRaceCard($res->body()); // 実装は Source 側へ
    }

    public function failed(Throwable $e): void
    {
        report($e);
    }
}
