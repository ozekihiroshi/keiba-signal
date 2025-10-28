#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC="$ROOT/routes"
STAMP="$(date +%Y%m%d_%H%M%S)"
OUTDIR="${1:-"$ROOT/tmp/routes_files_$STAMP"}"
LINES_PER_FILE="${LINES_PER_FILE:-2000}"
mkdir -p "$OUTDIR"
TMP="$OUTDIR/_routes_all.txt"

echo "Routes (files) snapshot: $(date -Iseconds)" > "$TMP"
find "$SRC" -type f -name '*.php' -print0 | sort -z | while IFS= read -r -d '' f; do
  rel="${f#"$ROOT/"}"
  printf '\n================================================================================\n' >> "$TMP"
  echo "FILE: $rel" >> "$TMP"
  printf '================================================================================\n' >> "$TMP"
  cat "$f" >> "$TMP"
  printf '\n' >> "$TMP"
done

. "$ROOT/scripts/lib_split.sh"
split_by_lines "$TMP" "$LINES_PER_FILE" "$OUTDIR/routes_files"
ls -1 "$OUTDIR"/routes_files_part*.txt > "$OUTDIR/INDEX.txt"
echo "✅ Routes(files) -> $OUTDIR"
