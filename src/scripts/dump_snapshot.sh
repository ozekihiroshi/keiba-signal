#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
STAMP="$(date +%Y%m%d_%H%M%S)"
OUTROOT="$ROOT/tmp/snapshots_$STAMP"
mkdir -p "$OUTROOT"

export LINES_PER_FILE="${LINES_PER_FILE:-2000}"  # ← 必要なら調整

bash "$ROOT/scripts/dump_controllers.sh" "$OUTROOT/controllers"
bash "$ROOT/scripts/dump_routes_files.sh" "$OUTROOT/routes_files"
bash "$ROOT/scripts/dump_routes_artisan.sh" "$OUTROOT/routes_list"
bash "$ROOT/scripts/dump_models.sh" "$OUTROOT/models"
bash "$ROOT/scripts/dump_blade_views.sh" "$OUTROOT/blade_views"
bash "$ROOT/scripts/dump_vue_pages.sh" "$OUTROOT/vue_pages"

echo "✅ Snapshot dir -> $OUTROOT"
