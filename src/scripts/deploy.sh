#!/usr/bin/env bash
set -euo pipefail

# マニフェスト駆動のSOP: デプロイの最小手順
# - すべて Docker 経由（ホストで PHP/Composer/Artisan を叩かない）
# - Filament アセットを毎回発行
# - 内外2段のヘルスチェック（adminの混在も防止）

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# 1) コンテナ起動（必要なら）
docker compose up -d keiba-app keiba-web keiba-redis

# 2) アプリ側メンテ（コンテナ内）
docker compose exec -T -w /var/www/html keiba-app bash -lc '
set -e
php -v
php artisan --version
php artisan migrate --force
php artisan filament:assets --ansi
php artisan optimize:clear
'

# 3) 内側ヘルスチェック（Nginx直）
docker compose exec -T keiba-web sh -lc \
  '\''curl -sI -H "Host: keiba.ceri.link" http://127.0.0.1/js/filament/filament/app.js | head -n1'\''

# 4) 外側ヘルスチェック（https）
curl -sI https://keiba.ceri.link/js/filament/filament/app.js | head -n1 || true
