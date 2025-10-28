#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.."; pwd)"
WEB_ROUTES="$ROOT_DIR/routes/web.php"
BACKUP="$ROOT_DIR/routes/web.php.bak.reissue.$(date +%Y%m%d%H%M%S)"

echo "[info] backup -> $BACKUP"
cp "$WEB_ROUTES" "$BACKUP"

# Engineer グループに reissue を追加（重複は無視）
if ! grep -q "engineer\.invites\.reissue" "$WEB_ROUTES"; then
  sed -i "/Route::middleware(\[\'auth\'\,\'verified\'\,\'role:engineer\|super-admin\'\])\.prefix('engineer')/q" "$WEB_ROUTES" || true
  cat >> "$WEB_ROUTES" <<'PHP'
/* (appended) Engineer invites reissue */
Route::middleware(['auth','verified','role:engineer|super-admin'])->prefix('engineer')->name('engineer.')->group(function () {
    Route::post('invites/{invite}/reissue', [\App\Http\Controllers\Engineer\InviteController::class, 'reissue'])->name('invites.reissue');
});
PHP
fi

# Super グループに reissue を追加（重複は無視）
if ! grep -q "super\.invites\.reissue" "$WEB_ROUTES"; then
  cat >> "$WEB_ROUTES" <<'PHP'
/* (appended) Super invites reissue */
Route::middleware(['auth','verified','role:super-admin'])->prefix('super')->name('super.')->group(function () {
    Route::post('invites/{invite}/reissue', [\App\Http\Controllers\Super\InviteController::class, 'reissue'])->name('invites.reissue');
});
PHP
fi

echo "[done] routes patched. Backup at: $BACKUP"
