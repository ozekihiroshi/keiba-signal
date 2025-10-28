#!/usr/bin/env bash
set -euo pipefail
docker compose exec -T -w /var/www/html app php artisan route:list | grep -E "facility\.admin\.(graphs|api)\." || true
echo "If the list above contains only Controller-based handlers (no Closures), you're good."
