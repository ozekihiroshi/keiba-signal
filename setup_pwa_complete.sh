#!/bin/bash
set -e

echo "=== PWA セットアップ開始 ==="

cd src

# 1. manifest.json
echo "1. manifest.json を作成中..."
cat > public/manifest.json << 'JSON'
{
  "name": "EleView - 電見（エレビュー）",
  "short_name": "EleView",
  "description": "電気設備点検管理システム",
  "start_url": "/",
  "scope": "/",
  "display": "standalone",
  "background_color": "#0f172a",
  "theme_color": "#0f172a",
  "orientation": "portrait-primary",
  "icons": [
    {
      "src": "/android-chrome-192x192.png",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "any maskable"
    },
    {
      "src": "/android-chrome-512x512.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "any maskable"
    }
  ],
  "screenshots": [
    {
      "src": "/screenshot1.png",
      "sizes": "540x720",
      "type": "image/png"
    }
  ]
}
JSON

# 2. Service Worker
echo "2. Service Worker を作成中..."
cat > public/sw.js << 'JS'
const CACHE_VERSION = 'eleview-v1';
const CACHE_FILES = [
  '/',
  '/manifest.json'
];

// インストール時
self.addEventListener('install', event => {
  console.log('[SW] インストール');
  event.waitUntil(
    caches.open(CACHE_VERSION)
      .then(cache => cache.addAll(CACHE_FILES))
      .then(() => self.skipWaiting())
  );
});

// アクティベート時
self.addEventListener('activate', event => {
  console.log('[SW] アクティベート');
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.filter(key => key !== CACHE_VERSION)
            .map(key => caches.delete(key))
      );
    }).then(() => self.clients.claim())
  );
});

// フェッチ時（ネットワーク優先、フォールバックでキャッシュ）
self.addEventListener('fetch', event => {
  event.respondWith(
    fetch(event.request)
      .catch(() => caches.match(event.request))
  );
});
JS

echo "✅ PWA ファイル作成完了"
echo ""
echo "次のステップ:"
echo "1. resources/views/layouts/console.blade.php の <head> に以下を追加:"
echo ""
echo '<link rel="manifest" href="/manifest.json">'
echo '<meta name="mobile-web-app-capable" content="yes">'
echo '<meta name="apple-mobile-web-app-capable" content="yes">'
echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">'
echo '<meta name="apple-mobile-web-app-title" content="EleView">'
echo ""
echo '2. Service Worker 登録スクリプトを <head> に追加:'
echo ""
echo '<script>'
echo "if ('serviceWorker' in navigator) {"
echo "  window.addEventListener('load', () => {"
echo "    navigator.serviceWorker.register('/sw.js')"
echo "      .then(reg => console.log('SW registered:', reg))"
echo "      .catch(err => console.log('SW error:', err));"
echo "  });"
echo "}"
echo '</script>'
echo ""
echo "3. Docker コンテナを再起動:"
echo "   docker compose restart app"
echo ""
echo "4. ブラウザでアクセスして動作確認"
