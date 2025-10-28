#!/usr/bin/env bash
# Laravel feature blocks helper: inventory → block manifest
# Non-destructive. Writes under manifests/ and tmp/.

set -euo pipefail

# --- CONFIG --------------------------------------------------------------
ART="docker compose exec -T -w /var/www/html app php artisan"
PHP="docker compose exec -T -w /var/www/html app php -d detect_unicode=0 -d short_open_tag=0"

REPO_ROOT="$(pwd)"
MANIFEST_DIR="${REPO_ROOT}/manifests"
TMP_DIR="${REPO_ROOT}/tmp"
INV_DIR="${TMP_DIR}/inventory"
BLOCK_TAG="fblocks"

mkdir -p "$MANIFEST_DIR" "$INV_DIR"

# --- HELP ----------------------------------------------------------------
usage() {
  cat <<'USAGE'
fblocks.sh - Laravel file inventory & feature block manifest helper

SUBCOMMANDS
  inventory
      Scan project and write categorized lists under tmp/inventory/.
  resolve-class <FQN>
      Resolve a Composer class FQN to a file path.
  from-route <route.name> <block-name>
      Build manifests/<block-name>.manifest tracing the route's controller method.
  from-controller <FQN> <block-name>
      Same as from-route but starting from a controller FQN.
  from-keyword <keyword> <block-name>
      Grep controllers/views/routes for a keyword and build a draft manifest.
  preview <manifest-path>
      Print BUNDLE-style header (FOUND/MISSING) for a given manifest.

EXAMPLES
  scripts/fblocks.sh inventory
  scripts/fblocks.sh from-route super.facilities.create org-facilities
  scripts/fblocks.sh from-controller App\\Http\\Controllers\\Super\\FacilityController org-facilities
  scripts/fblocks.sh from-keyword facilities org-facilities
  scripts/fblocks.sh preview manifests/org-facilities.manifest
USAGE
}

# --- GUARDS --------------------------------------------------------------
require_repo() {
  if [[ ! -f artisan ]] || [[ ! -f .env ]]; then
    echo "[${BLOCK_TAG}] ERROR: Run in Laravel project root (artisan/.env present)." >&2
    exit 1
  fi
}

# --- UTILS ---------------------------------------------------------------
normpath() {
  # print path relative to repo root if inside, else absolute
  local p="$1"
  if command -v realpath >/dev/null 2>&1; then
    p="$(realpath -m "$p" 2>/dev/null || echo "$p")"
  else
    p="$(python3 -c 'import os,sys; print(os.path.abspath(sys.argv[1]))' "$p" 2>/dev/null || echo "$p")"
  fi
  if [[ "$p" == "$REPO_ROOT"* ]]; then
    p="${p#$REPO_ROOT/}"
  fi
  printf "%s\n" "$p"
}

dedup_sort() { awk 'NF' | sed 's#//\+#/#g' | sort -u; }

# --- INVENTORY -----------------------------------------------------------
cmd_inventory() {
  require_repo
  find app/Http/Controllers -type f -name '*.php' 2>/dev/null | sort -u > "${INV_DIR}/controllers.txt" || true
  find app/Models            -type f -name '*.php' 2>/dev/null | sort -u > "${INV_DIR}/models.txt"      || true
  find app/Http/Requests     -type f -name '*.php' 2>/dev/null | sort -u > "${INV_DIR}/requests.txt"    || true
  find resources/views       -type f -name '*.blade.php' 2>/dev/null | sort -u > "${INV_DIR}/views.txt" || true
  find routes                -type f -name '*.php' 2>/dev/null | sort -u > "${INV_DIR}/routes.txt"      || true
  find database/migrations   -type f -name '*.php' 2>/dev/null | sort -u > "${INV_DIR}/migrations.txt"  || true
  find database/seeders      -type f -name '*.php' 2>/dev/null | sort -u > "${INV_DIR}/seeders.txt"     || true
  find app/Policies          -type f -name '*.php' 2>/dev/null | sort -u > "${INV_DIR}/policies.txt"    || true
  find app/Console/Commands  -type f -name '*.php' 2>/dev/null | sort -u > "${INV_DIR}/commands.txt"    || true
  find app/Services          -type f -name '*.php' 2>/dev/null | sort -u > "${INV_DIR}/services.txt"    || true
  find config                -type f -name '*.php' 2>/dev/null | sort -u > "${INV_DIR}/config.txt"      || true
  echo "[${BLOCK_TAG}] Inventory: ${INV_DIR}"
}

# --- RESOLVE CLASS VIA COMPOSER CLASSMAP --------------------------------
resolve_class_path() {
  local fqn="$1"
  # Use PHP to consult Composer classmap reliably
  $PHP -r '
    $fqn = $argv[1] ?? null;
    if (!$fqn) { exit(3); }
    $map = @include "vendor/composer/autoload_classmap.php";
    if (is_array($map) && isset($map[$fqn])) { echo $map[$fqn], PHP_EOL; exit; }
    $guess = "app/" . str_replace("\\\\", "/", preg_replace("#^App\\\\\\\\#","",$fqn)) . ".php";
    if (file_exists($guess)) { echo $guess, PHP_EOL; exit; }
    $short = preg_replace("#^.*\\\\\\\\#","",$fqn);
    $out = [];
    @exec("grep -RIl --include=*.php \"class $short\" app 2>/dev/null", $out);
    if ($out) { echo $out[0], PHP_EOL; exit; }
    exit(4);
  ' -- "$fqn" 2>/dev/null || true
}

cmd_resolve_class() {
  require_repo
  local fqn="${1:-}"; [[ -z "$fqn" ]] && { echo "resolve-class <FQN>"; exit 1; }
  local p; p="$(resolve_class_path "$fqn" || true)"
  if [[ -z "$p" ]]; then echo "[${BLOCK_TAG}] Not found: $fqn" >&2; exit 2; fi
  normpath "$p"
}

# --- ROUTE LOOKUP (JSON, robust) ----------------------------------------
find_route_action_by_name() {
  local rname="$1"
  $ART route:list --json 2>/dev/null \
  | python3 - "$rname" - <<'PY'
import sys, json
name = (sys.argv[1] or "").lower()
data = json.load(sys.stdin)
for r in data:
    if (r.get("name") or "").lower() == name:
        print(r.get("action") or "")
        sys.exit(0)
sys.exit(1)
PY
}

# --- CONTROLLER PARSER ---------------------------------------------------
scan_controller_deps() {
  local ctrl_file="$1" method="$2"

  # Direct uses: Requests & Models
  local uses reqs models
  uses="$(grep -E '^[[:space:]]*use[[:space:]]+App\\' "$ctrl_file" | sed 's/^[[:space:]]*use[[:space:]]\+//; s/[ ;]$//' | sort -u || true)"
  reqs="$(printf "%s\n" "$uses" | grep -E '^App\\Http\\Requests\\' || true)"
  models="$(printf "%s\n" "$uses" | grep -E '^App\\Models\\' || true)"

  # View names in target method
  local views=()
  if [[ -n "$method" ]]; then
    awk -v m="$method" '
      $0 ~ "function[[:space:]]+"m"\\(" {inm=1}
      inm {print}
      inm && $0 ~ "function[[:space:]]+" && $0 !~ "function[[:space:]]+"m"\\(" {exit}
    ' "$ctrl_file" > "${TMP_DIR}/.fblocks_method.tmp" || true
    if [[ -s "${TMP_DIR}/.fblocks_method.tmp" ]]; then
      mapfile -t views < <(
        grep -E "view\(['\"][A-Za-z0-9_./-]+['\"]\)|View::make\(['\"][A-Za-z0-9_./-]+['\"]\)" "${TMP_DIR}/.fblocks_method.tmp" \
        | sed -E "s/.*view\\(['\"]([^'\"]+)['\"].*/\\1/; s/.*View::make\\(['\"]([^'\"]+)['\"].*/\\1/" \
        | sort -u
      )
    fi
  fi

  printf "REQS=%s\n"  "$(printf "%s\n" "$reqs"   | paste -sd',' -)"
  printf "MODELS=%s\n" "$(printf "%s\n" "$models" | paste -sd',' -)"
  printf "VIEWS=%s\n"  "$(printf "%s\n" "${views[@]:-}" | paste -sd',' -)"
}

views_to_paths() {
  tr ',' '\n' | sed '/^[[:space:]]*$/d' \
    | sed -E 's#\.#/#g; s#^#resources/views/#; s#$#.blade.php#'
}

fqn_list_to_paths() {
  tr ',' '\n' | sed '/^[[:space:]]*$/d' \
    | while read -r fqn; do
        resolve_class_path "$fqn" || true
      done | sed '/^[[:space:]]*$/d'
}

write_manifest() {
  local block="$1"; shift
  local mf="${MANIFEST_DIR}/${block}.manifest"
  printf "%s\n" "$@" | dedup_sort > "$mf"
  echo "$mf"
}

# --- FROM-CONTROLLER -----------------------------------------------------
cmd_from_controller() {
  require_repo
  local fqn="${1:-}"; local block="${2:-}"
  [[ -z "$fqn" || -z "$block" ]] && { echo "from-controller <FQN> <block-name>"; exit 1; }

  local ctrl_file; ctrl_file="$(resolve_class_path "$fqn" || true)"
  [[ -z "$ctrl_file" ]] && { echo "[${BLOCK_TAG}] Controller not found: $fqn" >&2; exit 2; }

  local method="index"  # default guess
  local deps; deps="$(scan_controller_deps "$ctrl_file" "$method")"
  local reqs models views
  reqs="$(echo "$deps" | sed -n 's/^REQS=//p')"
  models="$(echo "$deps" | sed -n 's/^MODELS=//p')"
  views="$(echo "$deps" | sed -n 's/^VIEWS=//p')"

  mapfile -t files < <(
    printf "%s\n" "$ctrl_file"
    printf "%s\n" "$reqs"   | fqn_list_to_paths
    printf "%s\n" "$models" | fqn_list_to_paths
    printf "%s\n" "$views"  | views_to_paths
    local base; base="$(basename "$ctrl_file")"
    grep -RIl --include=*.php -e "$fqn" -e "$base" routes 2>/dev/null || true
  )

  local mf; mf="$(write_manifest "$block" "${files[@]}")"
  echo "[${BLOCK_TAG}] Manifest: $mf"
  cmd_preview "$mf" || true
}

# --- FROM-ROUTE ----------------------------------------------------------
cmd_from_route() {
  require_repo
  local rname="${1:-}"; local block="${2:-}"
  [[ -z "$rname" || -z "$block" ]] && { echo "from-route <route.name> <block-name>"; exit 1; }

  local action; action="$(find_route_action_by_name "$rname" || true)"
  if [[ -z "$action" ]]; then
    echo "[${BLOCK_TAG}] Route not found (or no action). Falling back to keyword search…" >&2
    cmd_from_keyword "$rname" "$block"
    exit 0
  fi

  if [[ "$action" == "Closure" ]]; then
    echo "[${BLOCK_TAG}] Route uses Closure. Falling back to keyword search…" >&2
    cmd_from_keyword "$rname" "$block"
    exit 0
  fi

  local fqn="${action%@*}"
  local method="${action##*@}"

  local ctrl_file; ctrl_file="$(resolve_class_path "$fqn" || true)"
  [[ -z "$ctrl_file" ]] && { echo "[${BLOCK_TAG}] Controller not found: $fqn" >&2; exit 2; }

  local deps; deps="$(scan_controller_deps "$ctrl_file" "$method")"
  local reqs models views
  reqs="$(echo "$deps" | sed -n 's/^REQS=//p')"
  models="$(echo "$deps" | sed -n 's/^MODELS=//p')"
  views="$(echo "$deps" | sed -n 's/^VIEWS=//p')"

  mapfile -t files < <(
    printf "%s\n" "$ctrl_file"
    printf "%s\n" "$reqs"   | fqn_list_to_paths
    printf "%s\n" "$models" | fqn_list_to_paths
    printf "%s\n" "$views"  | views_to_paths
    grep -RIl --include=*.php -e "$rname" -e "$fqn" -e "$(basename "$ctrl_file")" routes 2>/dev/null || true
  )

  local mf; mf="$(write_manifest "$block" "${files[@]}")"
  echo "[${BLOCK_TAG}] Manifest: $mf"
  cmd_preview "$mf" || true
}

# --- FROM-KEYWORD --------------------------------------------------------
cmd_from_keyword() {
  require_repo
  local kw="${1:-}"; local block="${2:-}"
  [[ -z "$kw" || -z "$block" ]] && { echo "from-keyword <keyword> <block-name>"; exit 1; }

  mapfile -t files < <(
    grep -RIl --include=*.php --include=*.blade.php -i -e "$kw" \
      app/Http/Controllers resources/views routes 2>/dev/null || true
  )
  if [[ ${#files[@]} -eq 0 ]]; then
    echo "[${BLOCK_TAG}] No hits for keyword: $kw" >&2; exit 3;
  fi

  local mf; mf="$(write_manifest "$block" "${files[@]}")"
  echo "[${BLOCK_TAG}] Manifest: $mf"
  cmd_preview "$mf" || true
}

# --- PREVIEW (BUNDLE HEADER) --------------------------------------------
cmd_preview() {
  local mf="${1:-}"; [[ -z "$mf" ]] && { echo "preview <manifest-path>"; exit 1; }
  [[ ! -f "$mf" ]] && { echo "[${BLOCK_TAG}] Manifest not found: $mf" >&2; exit 2; }

  echo "BUNDLE: ${BLOCK_TAG}"
  echo "ROOT:   ${REPO_ROOT}"
  echo "MANIFEST: $(normpath "$mf")"
  echo "FOUND:"
  local missing=()
  while IFS= read -r p; do
    [[ -z "$p" ]] && continue
    if [[ -f "$p" ]]; then
      echo "  - $p"
    else
      missing+=("$p")
    fi
  done < "$mf"
  echo "MISSING:"
  if [[ ${#missing[@]} -eq 0 ]]; then
    echo "  - (none)"
  else
    for m in "${missing[@]}"; do echo "  - $m"; done
    return 10
  fi
}

# --- MAIN ---------------------------------------------------------------
sub="${1:-help}"; shift || true
case "$sub" in
  help|-h|--help) usage ;;
  inventory)      cmd_inventory "$@" ;;
  resolve-class)  cmd_resolve_class "$@" ;;
  from-route)     cmd_from_route "$@" ;;
  from-controller)cmd_from_controller "$@" ;;
  from-keyword)   cmd_from_keyword "$@" ;;
  preview)        cmd_preview "$@" ;;
  *) echo "Unknown subcommand: $sub"; usage; exit 1 ;;
esac
