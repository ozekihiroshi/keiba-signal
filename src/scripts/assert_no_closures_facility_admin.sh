#!/usr/bin/env bash
set -euo pipefail
docker compose exec -T -w /var/www/html app php artisan route:list \
  | grep -E "facility\.admin\.(graphs|alerts|settings)\." \
  | grep -i "Closure" && { echo "[NG] facility_admin に Closure が残っています"; exit 1; } || {
    echo "[OK] facility_admin.* に Closure はありません";
  }
