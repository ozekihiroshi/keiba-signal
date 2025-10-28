# (任意) メンテナンスモードにしてから
docker compose exec -T -w /var/www/html keiba-app php artisan down --secret="ceri-maint"

# 変更内容のプレビュー（SQLを見るだけ）
docker compose exec -T -w /var/www/html keiba-app php artisan migrate --pretend --force

# 本番マイグレーション実行
docker compose exec -T -w /var/www/html keiba-app php artisan migrate --force

# Filament アセットを発行（必須）
docker compose exec -T -w /var/www/html keiba-app php artisan filament:assets --ansi


# 終了したら解除
docker compose exec -T -w /var/www/html keiba-app php artisan up

docker compose exec -T -w /var/www/html keiba-app bash -lc 'rm -f public/hot storage/framework/vite.hot || true; ls -l public/hot storage/framework/vite.hot || true'

docker compose exec -T -w /var/www/html keiba-app php artisan optimize:clear
docker compose exec -T -w /var/www/html keiba-app php artisan permission:cache-reset

