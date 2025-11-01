<?php

namespace App\Services\Feeds;

class JraRssClient
{
    public function __construct(
        private readonly string $feedUrl,
        private readonly string $userAgent = 'keiba-signal-bot/1.0'
    ) {}

    public static function fromConfig(): self
    {
        $cfg = config('feeds.sources.jra');
        return new self(
            $cfg['url'] ?? 'https://japanracing.jp/rss/hrj_news.rdf',
            $cfg['user_agent'] ?? 'keiba-signal-bot/1.0'
        );
    }

    /** Fetch & parse RSS/RDF into unified items. */
    public function fetch(): array
    {
        $xmlString = $this->download();
        if ($xmlString === null) return [];

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlString);
        if (!$xml) return [];

        // Register common namespaces (RDF/RSS1.0/DC/media/content)
        $xml->registerXPathNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
        $xml->registerXPathNamespace('rss', 'http://purl.org/rss/1.0/');
        $xml->registerXPathNamespace('dc',  'http://purl.org/dc/elements/1.1/');
        $xml->registerXPathNamespace('media', 'http://search.yahoo.com/mrss/');
        $xml->registerXPathNamespace('content', 'http://purl.org/rss/1.0/modules/content/');

        $candidates = [];

        // RSS 2.0/Atom 風（保険）
        foreach ($xml->xpath('//item') ?: [] as $it)   { $candidates[] = $it; }
        // RSS 1.0（RDF）：名前空間つき item
        foreach ($xml->xpath('//rss:item') ?: [] as $it){ $candidates[] = $it; }
        // RDF Description（rss1.0 で item が Description になるケース）
        foreach ($xml->xpath('//rdf:Description') ?: [] as $it) { $candidates[] = $it; }

        // さらに最悪どれも取れない場合、local-name() で保険
        if (empty($candidates)) {
            foreach ($xml->xpath('//*[local-name()="item"]') ?: [] as $it) { $candidates[] = $it; }
            foreach ($xml->xpath('//*[local-name()="Description"]') ?: [] as $it) { $candidates[] = $it; }
        }

        $items = [];
        foreach ($candidates as $it) {
            $title = $this->nxText($it, ['rss:title','title']);
            $link  = $this->nxText($it, ['rss:link','link']);
            $desc  = $this->nxText($it, ['rss:description','description','content:encoded']);

            // RDF のとき URL は rdf:about にあることがある
            if ($link === '' && isset($it['rdf:about'])) {
                $link = (string)$it['rdf:about'];
            }

            $pub = $this->nxText($it, ['dc:date','pubDate','date']);

            // 画像（あれば）
            $img = '';
            if (isset($it->enclosure['url'])) {
                $img = (string)$it->enclosure['url'];
            } elseif ($m = $it->children('media', true)) {
                if (isset($m->content['url'])) {
                    $img = (string)$m->content['url'];
                }
            }

            // guid が無い場合は link を流用
            $guid = trim((string)($it->guid ?? '')) ?: $link;

            // 何も揃っていないノードはスキップ
            if ($title === '' && $link === '') continue;

            $items[] = [
                'guid'         => $guid ?: null,
                'url'          => $link,
                'title'        => $title,
                'description'  => $desc,
                'image_url'    => $img ?: null,
                'published_at' => $this->normalizeDate($pub),
                'raw'          => $this->xmlToArray($it),
            ];
        }

        return $items;
    }

    private function download(): ?string
    {
        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: {$this->userAgent}\r\nAccept: application/xml, text/xml\r\n",
                'timeout' => 20,
            ],
        ]);
        $s = @file_get_contents($this->feedUrl, false, $context);
        return $s === false ? null : $s;
    }

    private function normalizeDate(?string $s): ?string
    {
        $s = $s ? trim($s) : '';
        if ($s === '') return null;
        try {
            // RSS1.0 の dc:date は ISO8601 が多い
            $dt = new \DateTimeImmutable($s);
            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function xmlToArray(\SimpleXMLElement $xml): array
    {
        $json = json_decode(json_encode($xml), true);
        return is_array($json) ? $json : [];
    }

    /** 名前空間対応: 候補キーを順に探して最初に見つかったテキストを返す */
    private function nxText(\SimpleXMLElement $node, array $paths): string
    {
        foreach ($paths as $path) {
            $parts = explode(':', $path, 2);
            if (count($parts) === 2) {
                [$ns, $name] = $parts;
                $children = $node->children($ns, true);
                if (isset($children->$name)) {
                    $v = trim((string)$children->$name);
                    if ($v !== '') return $v;
                }
            }
            // 非名前空間
            if (isset($node->$path)) {
                $v = trim((string)$node->$path);
                if ($v !== '') return $v;
            }
            // XPath フォールバック
            foreach ($node->xpath('.//*['."local-name()='{$path}'".']') ?: [] as $x) {
                $v = trim((string)$x);
                if ($v !== '') return $v;
            }
        }
        return '';
    }
}
