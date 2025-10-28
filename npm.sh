docker compose exec -T -w /var/www/html node npm run build
docker compose exec -T -w /var/www/html app php artisan view:clear
