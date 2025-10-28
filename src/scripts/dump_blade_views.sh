#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC="$ROOT/resources/views"
STAMP="$(date +%Y%m%d_%H%M%S)"
OUTDIR="${1:-"$ROOT/tmp/blade_views_$STAMP"}"
LINES_PER_FILE="${LINES_PER_FILE:-2000}"
mkdir -p "$OUTDIR"
TMP="$OUTDIR/_views_all.txt"

echo "Blade views snapshot: $(date -Iseconds)" > "$TMP"
find "$SRC" -type f -name '*.blade.php' -print0 | sort -z | while IFS= read -r -d '' f; do
  rel="${f#"$ROOT/"}"
  printf '\n================================================================================\n' >> "$TMP"
  echo "FILE: $rel" >> "$TMP"
  printf '================================================================================\n' >> "$TMP"
  cat "$f" >> "$TMP"
  printf '\n' >> "$TMP"
done

. "$ROOT/scripts/lib_split.sh"
split_by_lines "$TMP" "$LINES_PER_FILE" "$OUTDIR/blade_views"
ls -1 "$OUTDIR"/blade_views_part*.txt > "$OUTDIR/INDEX.txt"
echo "✅ Blade views -> $OUTDIR"
