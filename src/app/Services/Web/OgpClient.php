<?php

namespace App\Services\Web;

class OgpClient
{
    public function __construct(
        private readonly string $userAgent = 'keiba-signal-bot/1.0 (+contact@example.com)',
        private readonly int $timeout = 15
    ) {}

    public static function fromConfig(): self
    {
        $ua = config('feeds.sources.jra.user_agent', 'keiba-signal-bot/1.0 (+contact@example.com)');
        return new self($ua);
    }

    /** @return array{image?:string,title?:string,description?:string} */
    public function fetch(string $url): array
    {
        $html = $this->download($url);
        if ($html === null) return [];

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        if (!$dom->loadHTML($html)) {
            return [];
        }
        $xp = new \DOMXPath($dom);

        // 1) メタから
        $getMeta = function(array $keys) use ($xp): ?string {
            foreach ($keys as $key) {
                $q = "//meta[translate(@property,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='$key' or translate(@name,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='$key']/@content";
                $nodes = $xp->query($q);
                if ($nodes && $nodes->length) {
                    $v = trim((string)$nodes->item(0)->nodeValue);
                    if ($v !== '') return $v;
                }
            }
            return null;
        };

        $ogImage = $getMeta(['og:image','twitter:image','twitter:image:src','image']);
        $title   = $getMeta(['og:title']) ?: $this->firstText($xp, '//title');
        $desc    = $getMeta(['og:description','twitter:description','description']);

        // 絶対URL化
        if ($ogImage) $ogImage = $this->absolutizeUrl($ogImage, $url);

        // JRAの汎用OGPは「画像なし扱い」にして、より良い候補を探す
        if ($this->isGenericOgp($ogImage)) {
            $ogImage = null;
        }

        // 2) JSON-LD (NewsArticle / Article / WebPage など)
        $jsonLdImg = $this->extractJsonLdImage($xp, $url);

        // 3) 本文内の img（srcset対応）
        $contentImg = $this->extractContentImage($xp, $url);

        // 優先順位: JSON-LD > OG meta > 本文内
        $image = $jsonLdImg ?: ($ogImage ?: $contentImg);

        $out = [];
        if ($image) $out['image'] = $image;
        if ($title) $out['title'] = $title;
        if ($desc)  $out['description'] = $desc;

        return $out;
    }

    private function download(string $url): ?string
    {
        $ctx = stream_context_create([
            'http' => [
                'header'  => "User-Agent: {$this->userAgent}\r\nAccept: text/html\r\n",
                'timeout' => $this->timeout,
            ],
        ]);
        $s = @file_get_contents($url, false, $ctx);
        return $s === false ? null : $s;
    }

    private function firstText(\DOMXPath $xp, string $query): ?string
    {
        $n = $xp->query($query);
        if ($n && $n->length) {
            $t = trim((string)$n->item(0)->textContent);
            return $t !== '' ? $t : null;
        }
        return null;
    }

    private function absolutizeUrl(string $maybeRelative, string $base): string
    {
        $u = trim($maybeRelative);
        if ($u === '') return $u;

        // protocol-relative
        if (str_starts_with($u, '//')) {
            $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $u;
        }
        // absolute
        if (preg_match('#^https?://#i', $u)) return $u;

        // relative
        $bp = parse_url($base);
        if (!$bp || empty($bp['scheme']) || empty($bp['host'])) return $u;

        $scheme = $bp['scheme'];
        $host   = $bp['host'];
        $port   = isset($bp['port']) ? ':' . $bp['port'] : '';
        $dir    = rtrim(isset($bp['path']) ? dirname($bp['path']) : '/', '/');
        if (!str_starts_with($u, '/')) {
            $u = $dir . '/' . $u;
        }
        return "{$scheme}://{$host}{$port}" . (str_starts_with($u,'/') ? $u : "/{$u}");
    }

    /** JRA汎用OGP（共通画像）なら true */
    private function isGenericOgp(?string $url): bool
    {
        if (!$url) return false;
        return (bool)preg_match('#/common/img/ogp\.jpg$#i', $url);
    }

    /** JSON-LD から image を抽出（string / array / object(url) を想定） */
    private function extractJsonLdImage(\DOMXPath $xp, string $baseUrl): ?string
    {
        $nodes = $xp->query('//script[@type="application/ld+json"]');
        if (!$nodes || !$nodes->length) return null;

        for ($i = 0; $i < $nodes->length; $i++) {
            $json = trim((string)$nodes->item($i)->textContent);
            if ($json === '') continue;
            try {
                $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) { continue; }

            $image = $this->findImageInJsonLd($data);
            if ($image) {
                $img = is_string($image) ? $image : ($image['url'] ?? '');
                $img = $this->absolutizeUrl($img, $baseUrl);
                if (!$this->isGenericOgp($img)) {
                    return $img;
                }
            }
        }
        return null;
    }

    private function findImageInJsonLd($data)
    {
        if (is_array($data)) {
            // @graph 内にいることもある
            if (isset($data['@graph']) && is_array($data['@graph'])) {
                foreach ($data['@graph'] as $node) {
                    $found = $this->findImageInJsonLd($node);
                    if ($found) return $found;
                }
            }
            // NewsArticle / Article / WebPage 優先
            $type = strtolower((string)($data['@type'] ?? ''));
            if (in_array($type, ['newsarticle','article','reportagenewsarticle','webpage'], true)) {
                if (!empty($data['image'])) return $data['image'];
            }
            // フォールバック: 再帰で探す
            foreach ($data as $v) {
                $found = $this->findImageInJsonLd($v);
                if ($found) return $found;
            }
        }
        return null;
    }

    /** 本文内の画像のうち「一番良さそう」なものを拾う（srcset最大幅優先） */
    private function extractContentImage(\DOMXPath $xp, string $baseUrl): ?string
    {
        // よくあるコンテナっぽい場所を優先して検索
        $queries = [
            '//article//img',
            '//*[contains(@class,"article") or contains(@id,"article") or contains(@class,"body") or contains(@id,"main")]//img',
            '//img',
        ];

        foreach ($queries as $q) {
            $nodes = $xp->query($q);
            if (!$nodes || !$nodes->length) continue;

            $best = null;
            $bestW = -1;

            for ($i = 0; $i < $nodes->length; $i++) {
                /** @var \DOMElement $img */
                $img = $nodes->item($i);
                $src = trim((string)$img->getAttribute('src'));
                $srcset = trim((string)$img->getAttribute('srcset'));

                $candidate = '';
                $width = 0;

                if ($srcset !== '') {
                    [$candidate, $width] = $this->pickLargestFromSrcset($srcset);
                } elseif ($src !== '') {
                    $candidate = $src;
                }

                if ($candidate !== '') {
                    $candidate = $this->absolutizeUrl($candidate, $baseUrl);
                    if ($this->isGenericOgp($candidate)) {
                        continue; // 共通OGPはスキップ
                    }
                    if ($width > $bestW) {
                        $best = $candidate;
                        $bestW = $width;
                    }
                }
            }

            if ($best) return $best;
        }

        return null;
    }

    /** srcset から最大幅のURLを選ぶ */
    private function pickLargestFromSrcset(string $srcset): array
    {
        $best = '';
        $bestW = -1;
        foreach (explode(',', $srcset) as $part) {
            $part = trim($part);
            if ($part === '') continue;
            if (!preg_match('#\s+(\d+)w$#', $part, $m)) {
                // "url 2x" とかの形式もあるが、ここでは一旦スキップ
                $urlOnly = preg_replace('#\s+\S+$#', '', $part);
                $urlOnly = trim((string)$urlOnly);
                $w = 0;
            } else {
                $urlOnly = trim(substr($part, 0, -strlen($m[0])));
                $w = (int)$m[1];
            }
            if ($urlOnly !== '' && $w >= $bestW) {
                $best = $urlOnly;
                $bestW = $w;
            }
        }
        return [$best, $bestW];
    }
}
