#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC="$ROOT/app/Http/Controllers"
STAMP="$(date +%Y%m%d_%H%M%S)"
OUTDIR="${1:-"$ROOT/tmp/controllers_$STAMP"}"
LINES_PER_FILE="${LINES_PER_FILE:-2000}"   # ← 環境変数で上限行数指定可
mkdir -p "$OUTDIR"

TMP="$OUTDIR/_controllers_all.txt"

echo "Controllers snapshot: $(date -Iseconds)" > "$TMP"
find "$SRC" -type f -name '*.php' -print0 | sort -z | while IFS= read -r -d '' f; do
  rel="${f#"$ROOT/"}"
  printf '\n================================================================================\n' >> "$TMP"
  echo "FILE: $rel" >> "$TMP"
  printf '================================================================================\n' >> "$TMP"
  cat "$f" >> "$TMP"
  printf '\n' >> "$TMP"
done
echo "Total: $(find "$SRC" -type f -name '*.php' | wc -l)" >> "$TMP"

# 分割
. "$ROOT/scripts/lib_split.sh"
split_by_lines "$TMP" "$LINES_PER_FILE" "$OUTDIR/controllers"

# インデックス
ls -1 "$OUTDIR"/controllers_part*.txt > "$OUTDIR/INDEX.txt"
echo "✅ Controllers -> $OUTDIR"
