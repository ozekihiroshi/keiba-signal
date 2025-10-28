#!/usr/bin/env bash
# bin/refresh.sh — Viteのhot除去＋Laravelキャッシュ系を安全にクリア
set -euo pipefail
DC='docker compose exec -T -w /var/www/html keiba-app'

# 1) Viteのhotフラグを消して“本番ビルド”に固定
$DC bash -lc 'rm -f storage/framework/vite.hot public/hot || true'

# 2) Laravelキャッシュ類
$DC php artisan optimize:clear

# 3) 役割キャッシュ（spatie/permission）
$DC php artisan permission:cache-reset || true

echo "✔ refreshed (hot cleared, caches reset)"
