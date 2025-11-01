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

        $xpath = new \DOMXPath($dom);

        $getMeta = function(array $keys) use ($xpath): ?string {
            foreach ($keys as $key) {
                // property or name
                $q = "//meta[translate(@property,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='$key' or translate(@name,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='$key']/@content";
                $nodes = $xpath->query($q);
                if ($nodes && $nodes->length) {
                    $v = trim((string)$nodes->item(0)->nodeValue);
                    if ($v !== '') return $v;
                }
            }
            return null;
        };

        $ogImage = $getMeta(['og:image','twitter:image','twitter:image:src','image']);
        $title   = $getMeta(['og:title']) ?: $this->firstText($xpath, '//title');
        $desc    = $getMeta(['og:description','twitter:description','description']);

        if ($ogImage) {
            $ogImage = $this->absolutizeUrl($ogImage, $url);
        }

        $out = [];
        if ($ogImage) $out['image'] = $ogImage;
        if ($title)   $out['title'] = $title;
        if ($desc)    $out['description'] = $desc;

        return $out;
    }

    private function download(string $url): ?string
    {
        $ctx = stream_context_create([
            'http' => [
                'header' => "User-Agent: {$this->userAgent}\r\nAccept: text/html\r\n",
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
}
