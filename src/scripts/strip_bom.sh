# 使い方: bash scripts/strip_bom.sh
# リポジトリ内の PHP/Blade から UTF-8 BOM を除去します（再実行可）。
#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

# 対象拡張子（必要に応じて追加）
globs=(
  "*.php"
  "resources/views/**/*.blade.php"
)

# BOMパターン
BOM=$'\xEF\xBB\xBF'

# 検出と除去
echo "[strip_bom] scanning..."
while IFS= read -r -d '' f; do
  if head -c 3 "$f" | grep -q "$BOM"; then
    printf '[strip_bom] fix  %s\n' "$f"
    # 先頭の BOM を削除（バイナリ安全）
    tail -c +4 "$f" > "$f.nobom" && mv "$f.nobom" "$f"
  fi
done < <(printf '%s\0' "${globs[@]}" | xargs -0 -I{} bash -c 'shopt -s globstar nullglob; for p in {}; do printf "%s\0" "$p"; done')

echo "[strip_bom] done."

