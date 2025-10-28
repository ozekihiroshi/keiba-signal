#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ROUTES="$ROOT/routes/web.php"

block=$'# ===== engineer.users engagement edit =====\n'
block+=$'use App\\Http\\Controllers\\Engineer\\UserManageController;\n'
block+=$"\\Route::middleware(['web','auth','verified','role:engineer'])->prefix('engineer')->name('engineer.')->group(function(){\n"
block+=$"    \\Route::get('users/{user}/engagement',[UserManageController::class,'engagement'])->name('users.engagement');\n"
block+=$"    \\Route::patch('users/{user}/engagement',[UserManageController::class,'updateEngagement'])->name('users.engagement.update');\n"
block+=$"});\n"

# 既に入っていなければ末尾に追記
if ! grep -Fq "users/{user}/engagement" "$ROUTES"; then
  printf "\n%s" "$block" >> "$ROUTES"
  echo "Patched routes/web.php with engineer.users engagement routes."
else
  echo "Routes already present. Skipped."
fi
