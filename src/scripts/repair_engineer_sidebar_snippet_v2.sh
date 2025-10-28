#!/usr/bin/env bash
# 壊れた「月次点検チェックリスト」ブロックを正規形に置換（冪等）
set -euo pipefail
FILE="resources/views/engineer/_sidebar.blade.php"

SNIPPET="$(cat <<'SNIP'
  @if (Route::has('engineer.inspections.monthly.create'))
    <a href="{{ route('engineer.inspections.monthly.create', array_filter(['company_id' => $companyId, 'facility_id' => $facilityId])) }}"
       class="list-group-item list-group-item-action {{ request()->routeIs('engineer.inspections.*') ? 'active' : '' }}">
      月次点検チェックリスト
    </a>
  @endif
SNIP
)"

cp "$FILE" "$FILE.bak.$(date +%Y%m%d_%H%M%S)"

# 1) 既存ブロック（壊れていてもOK）を検出して置換
perl -0777 -pe '
  s{
    ^\s*\@if\s*\([^\n]*engineer\.inspections\.monthly\.create[^\n]*\)\s*    # @if (...) ライン
    (?:.*?\n)*?                                                             # 中身（改行跨り最短）
    ^\s*\@endif\s*$                                                         # 対応する @endif
  }{$ENV{SNIPPET}\n}xms
' -i "$FILE"

# 2) もし存在しなかった場合は、プロフィール編集リンクの直前へ挿入
if ! grep -Fq "engineer.inspections.monthly.create" "$FILE"; then
  awk -v snippet="$SNIPPET" '
    BEGIN{printed=0}
    /engineer\.profile\.edit/ && !printed { print snippet; printed=1 }
    { print }
    END { if (!printed) print snippet }
  ' "$FILE" > "$FILE.tmp" && mv "$FILE.tmp" "$FILE"
fi

echo "[DONE] repaired: $FILE"
echo "次に: docker compose exec -T -w /var/www/html app php artisan view:clear"
