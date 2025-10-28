#!/usr/bin/env bash
set -euo pipefail

# split_by_lines <input_file> <lines_per_file> <prefix>
# 例: split_by_lines tmp/all.txt 2000 tmp/blade_views/blade_views
split_by_lines() {
  local in="$1"; local lines="$2"; local prefix="$3"
  mkdir -p "$(dirname "$prefix")"
  # 既存の同プレフィックスのパートを削除（混在防止）
  rm -f "${prefix}_part"[0-9][0-9].txt 2>/dev/null || true
  # 00始まりの連番でそのまま出力（上書き事故を防ぐ）
  split -l "$lines" -d -a 2 --additional-suffix=".txt" "$in" "${prefix}_part"
}
