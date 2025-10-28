# まずビルドを試す。失敗したら依存インストールして再ビルド
docker run --rm -v "$PWD":/work -w /work node:20-alpine sh -lc "npm run build || (npm ci && npm run build)"

docker compose exec -T -w /var/www/html app php artisan optimize:clear

TOKEN=$(curl -s -k https://mobile.ceri.link/login | grep -oP '<meta name="csrf-token" content="\K[^"]+')
curl -s -k -D - -o /dev/null \
  -H "X-CSRF-TOKEN: $TOKEN" -H "X-Requested-With: XMLHttpRequest" \
  --data "email=super@example.com&password=password" \
  https://mobile.ceri.link/login | sed -n '1,25p'


