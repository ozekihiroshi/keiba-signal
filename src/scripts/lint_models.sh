#!/usr/bin/env bash
set -euo pipefail
files=("$@")
if [[ ${#files[@]} -eq 0 ]]; then
  files=("app/Models/Meter.php" "app/Models/Facility.php")
fi
for f in "${files[@]}"; do
  [[ -f "$f" ]] || { echo "[SKIP] $f not found"; continue; }
  echo "== php -l $f =="
  docker compose exec -T -w /var/www/html app php -l "$f" || true
done
