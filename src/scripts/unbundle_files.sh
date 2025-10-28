#!/usr/bin/env bash
set -euo pipefail
usage(){ echo "Usage: scripts/unbundle_files.sh <bundle.txt> [outdir]"; }

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUNDLE="${1:-}"; [[ -f "$BUNDLE" ]] || { usage; exit 1; }
OUT="${2:-"$ROOT/tmp/unbundle_$(date +%Y%m%d_%H%M%S)"}"
mkdir -p "$OUT"

in=0
path=""
# CRLF対策: read 直後に \r を削除
while IFS= read -r line || [[ -n "$line" ]]; do
  line="${line%$'\r'}"

  if [[ "$line" == "===== BEGIN FILE: "* ]]; then
    in=1
    file="${line#===== BEGIN FILE: }"
    path="$OUT/$file"
    dir="$(dirname "$path")"
    mkdir -p "$dir"
    # 既存を空に（BEGIN直後のメタ行はスキップされる）
    : > "$path"
    continue
  fi

  if [[ "$line" == "===== END FILE: "* ]]; then
    in=0
    path=""
    continue
  fi

  if (( in )); then
    # メタ行・区切り線は捨てる
    [[ "$line" == \#\ SHA256:* ]] && continue
    [[ "$line" =~ ^=+$ ]] && continue
    printf '%s\n' "$line" >> "$path"
  fi
done < "$BUNDLE"

echo "✅ Unbundled -> $OUT"
echo "  （この出力先を git diff で確認してから手動で取り込んでください）"
