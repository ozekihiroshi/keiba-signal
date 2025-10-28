#!/usr/bin/env bash
set -euo pipefail
# Company モデルに getRouteKeyName(): 'slug' を“存在しない場合のみ”挿入する安全パッチ
# - 上書き事故防止：バックアップを .bak で作成
# - 既にメソッドがあれば何もしない（再実行安全）

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TARGET="$ROOT/app/Models/Company.php"

if [[ ! -f "$TARGET" ]]; then
  echo "!! Company model not found: $TARGET" >&2
  exit 1
fi

if rg -n "function\s+getRouteKeyName\s*\(" "$TARGET" >/dev/null 2>&1; then
  echo "= getRouteKeyName() already exists. Skip patch."
  exit 0
fi

cp "$TARGET" "$TARGET.bak"

# 挿入位置：最終のクラス終端 '}' の直前に差し込む
TMP="$(mktemp)"
awk '
  BEGIN{inserted=0}
  {
    line=$0
    buf[NR]=line
  }
  END{
    # クラス終端の最後の } を探す
    lastbrace=0
    for(i=1;i<=NR;i++){
      if(buf[i] ~ /^[ \t]*}\s*$/){ lastbrace=i }
    }
    if(lastbrace==0){ lastbrace=NR }
    for(i=1;i<=NR;i++){
      if(i==lastbrace){
        print "    public function getRouteKeyName() { return '\''slug'\''; }"
      }
      print buf[i]
    }
  }
' "$TARGET" > "$TMP"

mv "$TMP" "$TARGET"
echo "= Patched: $TARGET"
