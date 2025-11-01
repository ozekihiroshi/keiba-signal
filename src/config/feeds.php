<?php

return [
    'sources' => [
        'jra' => [
            // 実フィードは /rss/hrj_news.rdf
            'url' => env('FEEDS_JRA_RSS_URL', 'https://japanracing.jp/rss/hrj_news.rdf'),
            'user_agent' => env('FEEDS_USER_AGENT', 'keiba-signal-bot/1.0 (+contact@example.com)'),
            'license_tag' => 'news',
        ],
    ],
];
