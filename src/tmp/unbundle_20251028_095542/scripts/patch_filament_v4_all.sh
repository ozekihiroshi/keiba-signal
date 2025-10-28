#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# Filament v4 互換パッチを一括適用
find "$ROOT/app/Filament" -type f -name "*Resource.php" -print0 \
  | xargs -0 -n 50 php "$ROOT/scripts/patch/filament_v4_resource_form_signature.php"

find "$ROOT/app/Filament" -type f -name "*Resource.php" -print0 \
  | xargs -0 -n 50 php "$ROOT/scripts/patch/filament_v4_navigation_icon.php"

echo "✓ Resources patched（form() と \$navigationIcon）"
