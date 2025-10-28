#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WEB="$ROOT/routes/web.php"
REQLINE="require __DIR__.'/engineer.inspections.monthly.php';"

if [[ ! -f "$WEB" ]]; then
  echo "ERROR: $WEB not found." >&2
  exit 1
fi

ts="$(date +%Y%m%d%H%M%S)"
cp "$WEB" "$WEB.bak.$ts"

# 1) 月次ルートの読み込みを追記（未挿入なら末尾に追加）
if ! grep -Fq "$REQLINE" "$WEB"; then
  printf "\n%s\n" "$REQLINE" >> "$WEB"
fi

# 2) 旧 MonthlyInspectionController の参照行をまるごとコメントアウト（あれば）
#    ※ 1行定義想定。複数行定義がある場合は必要に応じて手動調整してください。
tmp="$WEB.tmp.$ts"
awk '
  {
    if ($0 ~ /MonthlyInspectionController/) {
      print "// " $0
    } else {
      print $0
    }
  }
' "$WEB" > "$tmp"
mv "$tmp" "$WEB"

echo "OK: routes/web.php patched. Backup: $WEB.bak.$ts"
