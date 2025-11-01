<?php

namespace App\Support;

class Url
{
    /** URL正規化（エンコード整形、utm_*除去、ホスト小文字、末尾スラ削除、クエリキーソート） */
    public static function canon(?string $url): string
    {
        $url = trim((string)$url);
        if ($url === '') return '';

        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) return $url;

        $scheme = $parts['scheme'] ?? 'https';
        $host   = strtolower($parts['host']);
        $path   = isset($parts['path']) ? preg_replace('#/+#','/',$parts['path']) : '/';

        $query = '';
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $q);
            $q = array_filter($q, fn($v, $k) => !preg_match('#^utm_#i', (string)$k), ARRAY_FILTER_USE_BOTH);
            if (!empty($q)) {
                ksort($q);
                $query = http_build_query($q);
            }
        }

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $canon = "{$scheme}://{$host}{$path}";
        if ($query !== '') $canon .= "?{$query}";

        return $canon;
    }
}
