#!/usr/bin/env bash
set -euo pipefail
echo "== sidebar-brand / brand-link 検索 =="
grep -RIn --include="*.blade.php" -E 'sidebar-brand|brand-link' resources/views || true
echo ""
echo "== @include('partials.brand') 検索 =="
grep -RIn --include="*.blade.php" -F "@include('partials.brand')" resources/views || true
