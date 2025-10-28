#!/usr/bin/env bash
set -euo pipefail

BASE="/home/ubuntu/docker/keiba-signal"
DOMAIN="keiba.ceri.link"
UIDGID="$(id -u):$(id -g)"   # ← これでDocker内の実効ユーザ=ホストのubuntuに

mkdir -p "$BASE/src/docker/nginx"

# 1) Laravel 骨格の用意（src が空でない場合は一時ディレクトリ経由）
if [ ! -f "$BASE/src/artisan" ]; then
  if [ -z "$(ls -A "$BASE/src" 2>/dev/null)" ]; then
    echo "[+] create-project into src (empty)"
    docker run --rm -u "$UIDGID" -v "$BASE/src":/app -w /app composer:2 \
      create-project laravel/laravel .
  else
    echo "[+] src is not empty; staging in temp dir then overlay"
    TMPDIR=$(mktemp -d)
    trap 'sudo rm -rf "$TMPDIR"' EXIT
    docker run --rm -u "$UIDGID" -v "$TMPDIR":/app -w /app composer:2 \
      create-project laravel/laravel .
    # Laravel骨格を流し込み（既存の docker/, Dockerfile は保持）
    rsync -a --delete \
      --exclude 'docker/' \
      --exclude 'Dockerfile' \
      "$TMPDIR"/ "$BASE/src"/
  fi
fi

# 2) .env 準備（未作成なら）
if [ ! -f "$BASE/src/.env" ]; then
  if [ -f "$BASE/src/.env.example" ]; then
    cp -n "$BASE/src/.env.example" "$BASE/src/.env"
  fi
  sed -i "s#^APP_URL=.*#APP_URL=https://$DOMAIN#g" "$BASE/src/.env" || true
fi

# 3) 所有者をubuntuに揃える（念のため）
chown -R $(id -u):$(id -g) "$BASE/src"

# 4) コンテナ起動（ビルド含む）
cd "$BASE"
docker compose up -d --build

# 5) APP_KEY 生成と権限
docker compose exec -T -w /var/www/html keiba-app php artisan key:generate
docker compose exec -T -w /var/www/html keiba-app php -r "is_dir('storage') && chmod('storage', 0775);"
docker compose exec -T -w /var/www/html keiba-app sh -lc "chmod -R ug+rw storage bootstrap/cache || true"

echo "== Bootstrap finished =="
echo "Open: https://$DOMAIN"

