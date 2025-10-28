#!/usr/bin/env bash
set -euo pipefail
echo "== grep models for trait =="
grep -RIn "use BelongsToCompany;" app/Models || true
echo "== env flag =="
grep -n "^TENANT_ENFORCE=" .env || true
echo "== reminder =="
echo "config:cache を忘れずに。問題があれば TENANT_ENFORCE=false に戻してください。"
