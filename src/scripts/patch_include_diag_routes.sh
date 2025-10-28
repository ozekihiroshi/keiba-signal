#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WEB="$ROOT/routes/web.php"

if [[ ! -f "$WEB" ]]; then
  echo "routes/web.php not found" >&2
  exit 1
fi

if grep -q "require base_path('routes/diag.php');" "$WEB"; then
  echo "routes/web.php already includes routes/diag.php"
  exit 0
fi

printf "\n// added by patch_include_diag_routes.sh\nrequire base_path('routes/diag.php');\n" >> "$WEB"
echo "appended require to routes/web.php"

