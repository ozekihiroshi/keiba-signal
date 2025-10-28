#!/usr/bin/env bash
set -euo pipefail
# 目的:
# - class 宣言の「{」の直後に「use BelongsToCompany;」を配置
# - namespace 直後に「use App\Models\Concerns\BelongsToCompany;」が無ければ補完
# 使い方: bash scripts/fix_belongs_trait.sh [対象モデル...]
# 引数無しなら Meter と Facility を対象にします

targets=("$@")
if [[ ${#targets[@]} -eq 0 ]]; then
  targets=("app/Models/Meter.php" "app/Models/Facility.php")
fi

for f in "${targets[@]}"; do
  if [[ ! -f "$f" ]]; then
    echo "[SKIP] $f not found"; continue
  fi
  bak="$f.bak.fix.$(date +%Y%m%d_%H%M%S)"
  cp -a "$f" "$bak"

  # 1) クラス宣言〜最初の { までに現れる誤配置の「use BelongsToCompany;」を退避し、
  #    { の直後に 1 回だけ挿入する awk
  awk '
    BEGIN { state=0; inserted=0 }
    {
      line=$0
      if (state==0) {
        # クラス行検出
        if (match(line, /^[[:space:]]*class[[:space:]]+[A-Za-z0-9_]+/)) {
          # クラス行に { を含む場合
          if (index(line, "{")>0) {
            print line
            if (inserted==0) {
              print "    use BelongsToCompany;"
              inserted=1
            }
            next
          } else {
            state=1
            print line
            next
          }
        }
        print line
        next
      } else if (state==1) {
        # クラス行〜最初の { の間
        if (match(line, /^[[:space:]]*use[[:space:]]+BelongsToCompany;[[:space:]]*$/)) {
          # 誤配置は捨てる
          next
        }
        print line
        if (index(line, "{")>0) {
          if (inserted==0) {
            print "    use BelongsToCompany;"
            inserted=1
          }
          state=0
        }
        next
      }
      print line
    }
  ' "$bak" > "$f.tmp1"

  # 2) namespace 直後に Concerns の import を補完（無ければ）
  if ! grep -qE '^[[:space:]]*use[[:space:]]+App\\Models\\Concerns\\BelongsToCompany;' "$f.tmp1"; then
    awk '
      BEGIN { done=0 }
      {
        print $0
        if (done==0 && $0 ~ /^namespace[[:space:]]+[A-Za-z0-9_\\]+;/) {
          print "use App\\Models\\Concerns\\BelongsToCompany;";
          done=1
        }
      }
    ' "$f.tmp1" > "$f.tmp2"
  else
    mv "$f.tmp1" "$f.tmp2"
  fi

  mv "$f.tmp2" "$f"
  echo "[OK] fixed: $f (backup: $bak)"
done
