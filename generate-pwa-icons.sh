#!/bin/bash
# PWAアイコン生成スクリプト
# 使い方: bash generate-pwa-icons.sh

set -e

# 作業ディレクトリ
cd "$(dirname "$0")"
PUBLIC_DIR="src/public"
SVG_FILE="$PUBLIC_DIR/favicon.svg"

# SVGファイルの存在確認
if [ ! -f "$SVG_FILE" ]; then
  echo "エラー: $SVG_FILE が見つかりません"
  exit 1
fi

echo "📱 PWAアイコンを生成します..."
echo "ソース: $SVG_FILE"
echo ""

# ImageMagickの確認
if command -v convert &> /dev/null; then
  echo "✓ ImageMagick を使用"
  CONVERTER="imagemagick"
elif command -v inkscape &> /dev/null; then
  echo "✓ Inkscape を使用"
  CONVERTER="inkscape"
else
  echo "❌ ImageMagick または Inkscape が必要です"
  echo ""
  echo "インストール方法:"
  echo "  Ubuntu/Debian: sudo apt-get install imagemagick"
  echo "  または: sudo apt-get install inkscape"
  exit 1
fi

# 生成する画像サイズの配列
declare -A SIZES=(
  ["apple-touch-icon.png"]="180"
  ["android-chrome-192x192.png"]="192"
  ["android-chrome-512x512.png"]="512"
  ["favicon-32x32.png"]="32"
  ["favicon-16x16.png"]="16"
)

# 各サイズの画像を生成
for filename in "${!SIZES[@]}"; do
  size="${SIZES[$filename]}"
  output="$PUBLIC_DIR/$filename"
  
  echo "生成中: $filename (${size}x${size})"
  
  if [ "$CONVERTER" = "imagemagick" ]; then
    # ImageMagick使用
    convert -background none -density 300 "$SVG_FILE" \
            -resize "${size}x${size}" "$output"
  else
    # Inkscape使用
    inkscape "$SVG_FILE" \
             --export-type=png \
             --export-filename="$output" \
             --export-width="$size" \
             --export-height="$size"
  fi
  
  if [ -f "$output" ]; then
    echo "  ✓ 作成完了: $output"
  else
    echo "  ❌ 作成失敗: $output"
  fi
done

echo ""
echo "✨ アイコン生成完了！"
echo ""
echo "生成されたファイル:"
ls -lh "$PUBLIC_DIR"/*.png 2>/dev/null | awk '{print "  " $9 " (" $5 ")"}'

echo ""
echo "📋 次のステップ:"
echo "  1. ブラウザで開発者ツールを開く（F12）"
echo "  2. Application > Manifest を確認"
echo "  3. Service Workers が登録されているか確認"
echo "  4. スマホでアクセスして「ホーム画面に追加」を試す"
