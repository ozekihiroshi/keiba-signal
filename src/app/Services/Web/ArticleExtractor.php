<?php

namespace App\Services\Web;

class ArticleExtractor
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

    /** @return array{html?:string,image?:string} */
    public function extract(string $url): array
    {
        $html = $this->download($url);
        if ($html === null) return [];

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        if (!$dom->loadHTML($html)) return [];
        $xp = new \DOMXPath($dom);

        // 1) JSON-LD から articleBody が取れたら最優先
        if ($body = $this->extractJsonLdArticleBody($xp)) {
            $san = $this->sanitizeHtml($body, $url);
            $img = $this->pickImage($xp, $url);
            return array_filter(['html' => $san, 'image' => $img]);
        }

        // 2) 代表的なコンテナから p / ul / ol を抽出（先頭～適量）
        $containers = [
            '//article',
            '//*[contains(@class,"article") or contains(@id,"article")]',
            '//*[contains(@class,"news") or contains(@id,"news")]',
            '//*[@id="main" or @id="contents" or contains(@class,"content")]',
        ];
        foreach ($containers as $q) {
            $nodeList = $xp->query($q);
            if (!$nodeList || !$nodeList->length) continue;
            for ($i = 0; $i < min(3, $nodeList->length); $i++) {
                $node = $nodeList->item($i);
                $htmlFrag = $this->collectTextBlocks($xp, $node);
                if ($htmlFrag !== '') {
                    $san = $this->sanitizeHtml($htmlFrag, $url);
                    $img = $this->pickImage($xp, $url);
                    return array_filter(['html' => $san, 'image' => $img]);
                }
            }
        }

        // 3) 最後の手段：ページ全体から最初の p×3 を拾う
        $htmlFrag = $this->collectTextBlocks($xp, $dom);
        if ($htmlFrag !== '') {
            $san = $this->sanitizeHtml($htmlFrag, $url);
            $img = $this->pickImage($xp, $url);
            return array_filter(['html' => $san, 'image' => $img]);
        }

        return [];
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

    private function extractJsonLdArticleBody(\DOMXPath $xp): ?string
    {
        $nodes = $xp->query('//script[@type="application/ld+json"]');
        if (!$nodes) return null;
        for ($i = 0; $i < $nodes->length; $i++) {
            $json = trim((string)$nodes->item($i)->textContent);
            if ($json === '') continue;
            try { $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR); }
            catch (\Throwable) { continue; }
            $body = $this->findInJsonLd($data, ['articleBody', 'text']);
            if (is_string($body) && trim($body) !== '') {
                // 改行を段落化
                $paras = array_filter(array_map('trim', preg_split("/\r\n|\r|\n/", $body)));
                $paras = array_slice($paras, 0, 3);
                return '<p>'.implode('</p><p>', array_map('htmlspecialchars', $paras)).'</p>';
            }
        }
        return null;
    }

    private function findInJsonLd($data, array $keys)
    {
        if (is_array($data)) {
            foreach ($keys as $k) if (isset($data[$k])) return $data[$k];
            if (isset($data['@graph']) && is_array($data['@graph'])) {
                foreach ($data['@graph'] as $n) {
                    $v = $this->findInJsonLd($n, $keys);
                    if ($v) return $v;
                }
            }
            foreach ($data as $v) {
                $r = $this->findInJsonLd($v, $keys);
                if ($r) return $r;
            }
        }
        return null;
    }

    private function collectTextBlocks(\DOMXPath $xp, \DOMNode $scope): string
    {
        $nodes = $xp->query('.//p | .//ul | .//ol', $scope);
        if (!$nodes || !$nodes->length) return '';
        $out = [];
        $len = 0;
        for ($i = 0; $i < $nodes->length; $i++) {
            $n = $nodes->item($i);
            $html = $n->C14N(); // ミニファイ気味に
            if ($html) {
                $out[] = $html;
                $len += mb_strlen(strip_tags($html));
                if ($len > 1200) break; // 抜粋は～1200文字程度
            }
            if (count($out) >= 6) break;
        }
        return implode('', $out);
    }

    private function sanitizeHtml(string $html, string $baseUrl): string
    {
        // 許可タグのみ、相対URL→絶対化、JSスキーム排除
        $html = strip_tags($html, '<p><br><strong><em><ul><ol><li><a>');
        // a[href]
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("|\').*?\1/iu', '', $html);
        $html = preg_replace('/href\s*=\s*("|\')\s*javascript:[^"\']*\1/iu', 'href="#"', $html);
        // 相対→絶対
        $html = preg_replace_callback('/href=("|\')([^"\']+)\1/i', function($m) use ($baseUrl) {
            return 'href="'.$this->absolutizeUrl($m[2], $baseUrl).'"';
        }, $html);
        return $html;
    }

    private function pickImage(\DOMXPath $xp, string $baseUrl): ?string
    {
        // 本文内の <img> から一番大きそうなものを選ぶ（GIF/アイコン類は除外）
        $cands = $xp->query('//article//img | //img');
        if (!$cands) return null;
        $best = null; $bestW = -1;
        for ($i = 0; $i < $cands->length; $i++) {
            /** @var \DOMElement $img */
            $img = $cands->item($i);
            $src = trim((string)$img->getAttribute('src'));
            $srcset = trim((string)$img->getAttribute('srcset'));
            $url = $src;
            $w = 0;
            if ($srcset) {
                foreach (explode(',', $srcset) as $part) {
                    $part = trim($part);
                    if (preg_match('#\s(\d+)w$#', $part, $m)) {
                        $u = trim(substr($part, 0, -strlen($m[0])));
                        if ((int)$m[1] > $w) { $url = $u; $w = (int)$m[1]; }
                    }
                }
            }
            if ($url === '') continue;
            $abs = $this->absolutizeUrl($url, $baseUrl);
            if ($this->isPlaceholder($abs)) continue;
            if ($w > $bestW) { $best = $abs; $bestW = $w; }
        }
        return $best;
    }

    private function isPlaceholder(string $url): bool
    {
        $u = strtolower($url);
        if (preg_match('/\.(gif|svg)(\?|$)/', $u)) return true;
        foreach (['/common/img/ogp.jpg','com_plg_ic01.jpg','related.gif','/sprite','/icon','/logo','/_img/pdf'] as $bad) {
            if (str_contains($u, $bad)) return true;
        }
        return false;
    }

    private function absolutizeUrl(string $maybeRelative, string $base): string
    {
        $u = trim($maybeRelative);
        if ($u === '') return $u;
        if (str_starts_with($u, '//')) {
            $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
            $u = $scheme . ':' . $u;
        } elseif (!preg_match('#^https?://#i', $u)) {
            $bp = parse_url($base);
            if ($bp && !empty($bp['scheme']) && !empty($bp['host'])) {
                $scheme = $bp['scheme'];
                $host   = $bp['host'];
                $port   = isset($bp['port']) ? ':' . $bp['port'] : '';
                $dir    = rtrim(isset($bp['path']) ? dirname($bp['path']) : '/', '/');
                if (!str_starts_with($u, '/')) $u = $dir . '/' . $u;
                $u = "{$scheme}://{$host}{$port}" . (str_starts_with($u,'/') ? $u : "/{$u}");
            }
        }
        // ./ と ../ を正規化
        $p = parse_url($u);
        if ($p && isset($p['path'])) {
            $segs = explode('/', $p['path']); $stack = [];
            foreach ($segs as $seg) {
                if ($seg === '' || $seg === '.') continue;
                if ($seg === '..') { array_pop($stack); continue; }
                $stack[] = $seg;
            }
            $u = ($p['scheme'] ?? 'https').'://'.$p['host'].(isset($p['port'])?':'.$p['port']:'').'/'.implode('/',$stack)
               .(isset($p['query'])?'?'.$p['query']:'').(isset($p['fragment'])?'#'.$p['fragment']:'');
        }
        return $u;
    }
}