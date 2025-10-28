#!/usr/bin/env bash
set -euo pipefail

# 使い方:
#   chmod +x scripts/cleanup_routes_refactor.sh
#   ./scripts/cleanup_routes_refactor.sh
#
# 既存の重複・未使用ルートファイルを routes/_deprecated/YYYYmmdd_HHMMSS/ へ退避します。
# 実削除はしません（必要に応じて git で追って削除してください）。

TS="$(date +%Y%m%d_%H%M%S)"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ROUTES_DIR="$ROOT/routes"
DEP_DIR="$ROUTES_DIR/_deprecated/$TS"

mkdir -p "$DEP_DIR"

move_if_exists () {
  local p="$1"
  if [[ -f "$p" ]]; then
    echo ">> move: $p -> $DEP_DIR/"
    mv "$p" "$DEP_DIR/"
  else
    echo "-- skip: $p (not found)"
  fi
}

# 重複・旧版（今回の方針では未使用）
move_if_exists "$ROUTES_DIR/engineer.php"              # ← dashboard のクロージャ版
move_if_exists "$ROUTES_DIR/super_dashboard.php"       # ← super.dashboard の旧定義
move_if_exists "$ROUTES_DIR/super_companies.php"       # ← super_console.php に集約
move_if_exists "$ROUTES_DIR/areas/super.php"           # ← 未使用 / 紛らわしいため退避
move_if_exists "$ROUTES_DIR/facility_history.php"      # ← 新: facility_graphs_history.php あり
move_if_exists "$ROUTES_DIR/facility_max_demand.php"   # ← 新: facility_graphs_maxdemand.php あり
move_if_exists "$ROUTES_DIR/company_admin.php"         # ← 空ファイル（誤認起点）なら退避

echo "== done. moved files are in: $DEP_DIR"
echo "※ 退避後は route キャッシュをクリアしてください。"
echo "   docker compose exec -T -w /var/www/html app php artisan route:clear"
