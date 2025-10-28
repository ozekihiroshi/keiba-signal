#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CONF="$ROOT/config/session.php"

[[ -f "$CONF" ]] || { echo "config/session.php not found" >&2; exit 1; }

cp -a "$CONF" "$CONF.bak.$(date +%Y%m%d_%H%M%S)"

perl -0777 -pe '
  s/
    (["\x27]domain["\x27]\s*=>\s*)env\(\s*["\x27]SESSION_DOMAIN["\x27][^)]*\)
  /
    $1 . "(function(){ \$d = env(\x27SESSION_DOMAIN\x27); if (\$d === null || \$d === \"\" || strtolower((string)\$d) === \"null\") { return null; } return ltrim(\$d, \".\"); })()"
  /xse
' -i "$CONF"

echo "patched: config/session.php (domain now falls back to null when unset/empty/'null')"

