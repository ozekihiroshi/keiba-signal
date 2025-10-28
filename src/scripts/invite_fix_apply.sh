#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.."; pwd)"
WEB_ROUTES="$ROOT_DIR/routes/web.php"
BACKUP="$ROOT_DIR/routes/web.php.bak.invitefix.$(date +%Y%m%d%H%M%S)"

echo "[info] backup -> $BACKUP"
cp "$WEB_ROUTES" "$BACKUP"

# 1) 競合する GET invites.accept (/invites/accept/{token}) を削除（何回実行してもOK）
TMP="$WEB_ROUTES.tmp.$$"
LC_ALL=C sed -E "/Route::get\([[:space:]]*'invites\/accept\/\{token\}'[[:space:]]*,.*\)\s*->name\([[:space:]]*'invites\.accept'[[:space:]]*\);/d" \
  "$WEB_ROUTES" > "$TMP"
mv "$TMP" "$WEB_ROUTES"

# 2) InviteAcceptController の use が無ければ先頭の use 群の後に挿入
if ! grep -q "^use[[:space:]]\+App\\\Http\\\Controllers\\\InviteAcceptController;" "$WEB_ROUTES"; then
  # 最初の Route:: 定義の直前あたりに use を差し込む（既存 use 群の近く）
  TMP="$WEB_ROUTES.tmp.$$"
  awk '
    BEGIN{inserted=0}
    {
      print $0
      if (!inserted && $0 ~ /^use[[:space:]]+Illuminate\\\Support\\\Facades\\\Route;/) {
        print "use App\\Http\\Controllers\\InviteAcceptController;"
        inserted=1
      }
    }
    END{
      if (!inserted) {
        print "use App\\Http\\Controllers\\InviteAcceptController;"
      }
    }
  ' "$WEB_ROUTES" > "$TMP"
  mv "$TMP" "$WEB_ROUTES"
fi

# 3) 正規の /invites/{token} ルートが無ければ追加（GET/POST）
if ! grep -q "Route::get('/invites/{token}'" "$WEB_ROUTES"; then
  cat >> "$WEB_ROUTES" <<'PHP'

// ==== [Appended by invite_fix_apply.sh] Invite Accept Routes (canonical) ====
// 表示: invites.show / 確定: invites.accept（POST）; 競合する /invites/accept/{token} は削除済み
Route::get('/invites/{token}', [InviteAcceptController::class, 'show'])->name('invites.show');
Route::post('/invites/{token}', [InviteAcceptController::class, 'accept'])->name('invites.accept');
// ==== [End of appended block] ====
PHP
  echo "[ok] appended canonical invites routes."
else
  echo "[ok] canonical invites routes already exist."
fi

echo "[done] routes fixed. Backup at: $BACKUP"
