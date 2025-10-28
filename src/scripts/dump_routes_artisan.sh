#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
STAMP="$(date +%Y%m%d_%H%M%S)"
OUTDIR="${1:-"$ROOT/tmp/routes_list_$STAMP"}"
LINES_PER_FILE="${LINES_PER_FILE:-2000}"
mkdir -p "$OUTDIR"
TMP="$OUTDIR/_routes_list.txt"

php "$ROOT/artisan" route:list > "$TMP"

. "$ROOT/scripts/lib_split.sh"
split_by_lines "$TMP" "$LINES_PER_FILE" "$OUTDIR/routes_list"
ls -1 "$OUTDIR"/routes_list_part*.txt > "$OUTDIR/INDEX.txt"
echo "✅ Routes(list) -> $OUTDIR"
