<?php
namespace App\Sources;

use App\Models\Race;
use App\Models\Track;
use Illuminate\Support\Str;

class ExampleSource extends AbstractSource {
    public function parseRaceCard(string $html): void
    {
        // TODO: DOM解析（Symfony DomCrawler 等を導入してもOK）
        // 例: タイトルからダミー登録
        $track = Track::firstOrCreate(['code'=>'TOKYO'], ['name'=>'東京']);
        Race::updateOrCreate(
            ['ext_id' => 'sample-20250101-TOKYO-11'],
            [
                'track_id' => $track->id,
                'date' => '2025-01-01',
                'race_no' => 11,
                'name' => 'サンプルS',
                'distance' => 1600,
                'surface' => 'TURF',
                'going' => 'FIRM',
                'grade' => 'OP',
            ]
        );
    }
}