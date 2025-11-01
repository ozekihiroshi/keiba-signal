<?php

namespace App\Support;

class Html
{
    /**
     * とりあえずの簡易サニタイズ:
     * - 許可タグ: p, br, strong, em, ul, ol, li, a, img
     * - on* 属性を削除
     * - javascript: URI を無効化
     * - //example.com -> https://example.com
     * - ベースURLがあれば /path を絶対URL化
     */
    public static function sanitize_basic(?string $html, ?string $baseUrl = null): string
    {
        $html = trim((string)$html);
        if ($html === '') return '';

        // 許可タグ以外は除去
        $html = strip_tags($html, '<p><br><strong><em><ul><ol><li><a><img>');

        // onxxx="..." を削除
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("|\').*?\1/iu', '', $html);

        // javascript: を削除
        $html = preg_replace('/(href|src)\s*=\s*("|\')\s*javascript:[^"\']*\2/iu', '$1="#"', $html);

        // protocol-relative -> https
        $html = preg_replace('/(href|src)=("|\')\/\/([^"\']+)\2/i', '$1="$2https://$3$2"', $html);

        // baseUrl があれば /path を絶対URLに
        if ($baseUrl) {
            $html = preg_replace_callback('/(href|src)=("|\')\/([^"\']+)\2/i', function ($m) use ($baseUrl) {
                $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
                $host   = parse_url($baseUrl, PHP_URL_HOST) ?: '';
                $port   = parse_url($baseUrl, PHP_URL_PORT);
                $port   = $port ? (':' . $port) : '';
                return $m[1] . '="' . "{$scheme}://{$host}{$port}/" . $m[3] . '"';
            }, $html);
        }

        return $html;
    }
}
