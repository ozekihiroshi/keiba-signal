#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'USAGE'
Usage: scripts/bundle_files.sh -m <manifest> [-o <outfile>] [--strict]
- manifest: 1行に1パス（相対/絶対/グロブOK）。# でコメント、空行OK
- outfile : 省略時 tmp/bundles/<manifest名>_YYYYmmdd_HHMMSS.txt
- --strict: 未発見が1つでもあれば最後にエラー終了（途中では止めない）
USAGE
}

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
STAMP="$(date +%Y%m%d_%H%M%S)"
STRICT=0
MANIFEST=""
OUTFILE=""

# 引数
while (( $# )); do
  case "$1" in
    -m|--manifest) MANIFEST="$2"; shift 2;;
    -o|--out) OUTFILE="$2"; shift 2;;
    --strict) STRICT=1; shift;;
    -h|--help) usage; exit 0;;
    *) echo "Unknown arg: $1" >&2; usage; exit 1;;
  esac
done
[[ -n "$MANIFEST" ]] || { echo "manifest is required"; usage; exit 1; }
[[ -f "$MANIFEST" ]] || { echo "manifest not found: $MANIFEST"; exit 1; }

mkdir -p "$ROOT/tmp/bundles"
if [[ -z "${OUTFILE:-}" ]]; then
  base="$(basename "$MANIFEST")"; base="${base%.*}"
  OUTFILE="$ROOT/tmp/bundles/${base}_${STAMP}.txt"
fi

# ---- helpers (GNU/BSD 両対応) ----
get_size() {
  local f="$1"
  if stat -c %s "$f" >/dev/null 2>&1; then
    stat -c %s "$f"
  else
    stat -f %z "$f"
  fi
}
get_epoch() {
  local f="$1"
  if stat -c %Y "$f" >/dev/null 2>&1; then
    stat -c %Y "$f"
  else
    stat -f %m "$f"
  fi
}
fmt_epoch() {
  local e="$1"
  if date -d "@$e" +%Y-%m-%dT%H:%M:%S%z >/dev/null 2>&1; then
    date -d "@$e" +%Y-%m-%dT%H:%M:%S%z
  else
    date -r "$e" +%Y-%m-%dT%H:%M:%S%z
  fi
}
get_sha256() {
  local f="$1"
  if command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$f" | awk '{print $1}'
  else
    shasum -a 256 "$f" | awk '{print $1}'
  fi
}

shopt -s globstar nullglob

declare -a FILES=()
declare -a MISSING=()

# マニフェスト読み込み（全行チェック）
while IFS= read -r raw || [[ -n "$raw" ]]; do
  # CR除去＆前後スペース保持しつつ評価
  line="${raw%$'\r'}"
  # コメント/空行スキップ
  [[ -z "${line// }" ]] && continue
  [[ "$line" =~ ^[[:space:]]*# ]] && continue

  # ルート相対に正規化
  if [[ "$line" = /* ]]; then
    pat="$ROOT${line}"
  else
    pat="$ROOT/$line"
  fi

  matched=0
  for f in $pat; do
    [[ -f "$f" ]] || continue
    FILES+=("$f")
    matched=1
  done
  if (( matched == 0 )); then
    MISSING+=("$line")
  fi
done < "$MANIFEST"

# 重複除去＆ソート
if (( ${#FILES[@]} )); then
  IFS=$'\n' read -r -d '' -a FILES < <(printf "%s\n" "${FILES[@]}" | sed '/^$/d' | sort -u && printf '\0')
fi

# 出力
{
  echo "BUNDLE: $(date +%Y-%m-%dT%H:%M:%S%z)"
  echo "ROOT  : $ROOT"
  echo "MANIFEST: $MANIFEST"
  echo "FOUND : ${#FILES[@]}"
  echo "MISSING: ${#MISSING[@]}"
  if (( ${#MISSING[@]} )); then
    echo "MISSING LIST:"
    for m in "${MISSING[@]}"; do echo " - ${m#"$ROOT/"}"; done
  fi
  echo
  for f in "${FILES[@]}"; do
    rel="${f#"$ROOT/"}"
    size="$(get_size "$f")"
    epoch="$(get_epoch "$f")"
    mtime="$(fmt_epoch "$epoch")"
    hash="$(get_sha256 "$f")"
    printf '\n================================================================================\n'
    echo "===== BEGIN FILE: $rel"
    echo "# SHA256: $hash  BYTES: $size  MTIME: $mtime"
    echo "================================================================================"
    cat "$f"
    printf '\n===== END FILE: %s\n' "$rel"
  done
} > "$OUTFILE"

echo "✅ Bundled -> $OUTFILE"
if (( ${#MISSING[@]} )); then
  echo "⚠️  Missing ${#MISSING[@]} entries（ヘッダ参照）。"
  if (( STRICT )); then exit 2; fi
fi
