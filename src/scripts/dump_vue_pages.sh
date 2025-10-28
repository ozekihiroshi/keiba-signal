#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC="$ROOT/resources/js/Pages"
STAMP="$(date +%Y%m%d_%H%M%S)"
OUTDIR="${1:-"$ROOT/tmp/vue_pages_$STAMP"}"
LINES_PER_FILE="${LINES_PER_FILE:-2000}"
mkdir -p "$OUTDIR"
TMP="$OUTDIR/_vue_all.txt"

echo "Vue Pages snapshot: $(date -Iseconds)" > "$TMP"
find "$SRC" -type f -name '*.vue' -print0 | sort -z | while IFS= read -r -d '' f; do
  rel="${f#"$ROOT/"}"
  printf '\n================================================================================\n' >> "$TMP"
  echo "FILE: $rel" >> "$TMP"
  printf '================================================================================\n' >> "$TMP"
  cat "$f" >> "$TMP"
  printf '\n' >> "$TMP"
done

. "$ROOT/scripts/lib_split.sh"
split_by_lines "$TMP" "$LINES_PER_FILE" "$OUTDIR/vue_pages"
ls -1 "$OUTDIR"/vue_pages_part*.txt > "$OUTDIR/INDEX.txt"
echo "✅ Vue pages -> $OUTDIR"

