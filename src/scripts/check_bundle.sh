#!/usr/bin/env bash
set -euo pipefail
usage(){ echo "Usage: scripts/check_bundle.sh <bundle.txt>"; }

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUNDLE="${1:-}"; [[ -f "$BUNDLE" ]] || { usage; exit 1; }

# ヘルパ
sha256_file() {
  local f="$1"
  if command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$f" | awk '{print $1}'
  else
    shasum -a 256 "$f" | awk '{print $1}'
  fi
}

# 解析＆比較
declare -i total=0 same=0 diff=0 missing=0
while IFS= read -r line; do
  [[ "$line" =~ ^===== BEGIN\ FILE:\ (.+)$ ]] || continue
  rel="${BASH_REMATCH[1]}"

  # 次の行で SHA を拾う
  read -r meta
  if [[ "$meta" =~ ^\#\ SHA256:\ ([0-9a-fA-F]{64})\  ]]; then
    want="${BASH_REMATCH[1]}"; :
  else
    echo "?? header SHA not found for $rel"; continue
  fi

  ((total++))
  if [[ -f "$ROOT/$rel" ]]; then
    have="$(sha256_file "$ROOT/$rel")"
    if [[ "$have" == "$want" ]]; then
      ((same++))
      echo "OK  $rel"
    else
      ((diff++))
      echo "DIFF $rel"
    fi
  else
    ((missing++))
    echo "MISS $rel"
  fi
done < "$BUNDLE"

echo "---"
echo "Total: $total  OK: $same  DIFF: $diff  MISS: $missing"
# 差分や欠けがあれば終了コード1
(( diff==0 && missing==0 )) || exit 1
