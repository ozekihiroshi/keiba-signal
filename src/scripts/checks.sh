#!/usr/bin/env bash
set -euo pipefail

# 健全性チェック一式（失敗で終了）
fail=0
say(){ printf "• %s\n" "$*"; }

say "PHP 8.3 (keiba-app)"
docker compose exec -T -w /var/www/html keiba-app php -v | grep -q "PHP 8.3" || { echo "✗ PHP8.3未満"; fail=1; }

say "Laravel 12.x"
docker compose exec -T -w /var/www/html keiba-app php artisan --version | grep -q "Laravel Framework 12" || { echo "✗ Laravel 12.x 以外"; fail=1; }

say "Nginx: default.conf マウント確認"
docker compose exec -T keiba-web sh -lc 'nginx -T | grep -n "/etc/nginx/conf.d/default.conf"' >/dev/null || { echo "✗ default.conf 未読込"; fail=1; }

say "Nginx: Filament ロケーション 3本"
docker compose exec -T keiba-web sh -lc 'nginx -T | grep -nE "location \^~ /(fonts|css|js)/filament/"' >/dev/null || { echo "✗ Filament ロケーション不足"; fail=1; }

say "Filament アセット存在"
docker compose exec -T -w /var/www/html keiba-app sh -lc 'test -f public/js/filament/filament/app.js' || { echo "✗ app.js 不在（filament:assets 必須）"; fail=1; }

say "内側ヘルス（Nginx直）"
docker compose exec -T keiba-web sh -lc \
  'curl -sI -H "Host: keiba.ceri.link" http://127.0.0.1/js/filament/filament/app.js | grep -E "^HTTP/1\.1 20[0-9]|^HTTP/1\.1 304"' >/dev/null || { echo "✗ 内側 非200/304"; fail=1; }

say "外側ヘルス（https）"
curl -sI https://keiba.ceri.link/js/filament/filament/app.js | grep -E "^HTTP/2 20[0-9]|^HTTP/2 304" >/dev/null || { echo "✗ 外側 非200/304"; fail=1; }

say "Mixed Content 検出チェック（/admin）"
if docker compose exec -T keiba-web sh -lc \
  'curl -s -H "Host: keiba.ceri.link" http://127.0.0.1/admin | grep -Eo "http://[^\" ]+/(css|js|fonts)/filament/[^\" ]+"' >/dev/null; then
  echo "✗ Mixed Content 検出（.envの APP_URL/ASSET_URL=https を確認）"; fail=1
else
  echo "✓ Mixed Content なし"
fi

exit $fail
