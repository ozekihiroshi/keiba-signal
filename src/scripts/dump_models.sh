#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC="$ROOT/app/Models"
STAMP="$(date +%Y%m%d_%H%M%S)"
OUTDIR="${1:-"$ROOT/tmp/models_$STAMP"}"
LINES_PER_FILE="${LINES_PER_FILE:-2000}"
mkdir -p "$OUTDIR"
TMP="$OUTDIR/_models_all.txt"

echo "Models snapshot: $(date -Iseconds)" > "$TMP"
if [[ -d "$SRC" ]]; then
  find "$SRC" -type f -name '*.php' -print0 | sort -z | while IFS= read -r -d '' f; do
    rel="${f#"$ROOT/"}"
    printf '\n================================================================================\n' >> "$TMP"
    echo "FILE: $rel" >> "$TMP"
    printf '================================================================================\n' >> "$TMP"
    cat "$f" >> "$TMP"
    printf '\n' >> "$TMP"
  done
fi

. "$ROOT/scripts/lib_split.sh"
split_by_lines "$TMP" "$LINES_PER_FILE" "$OUTDIR/models"
ls -1 "$OUTDIR"/models_part*.txt > "$OUTDIR/INDEX.txt"
echo "✅ Models -> $OUTDIR"
