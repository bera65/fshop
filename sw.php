<?php

/**
 * PWA service worker — bağımsız endpoint.
 */

if (!headers_sent()) {
	header('Content-Type: application/javascript; charset=utf-8');
	header('Cache-Control: public, max-age=120');
	http_response_code(200);
}

$scope = '/fshop/';
$script = str_replace('\\', '/', (string) (isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : ''));

if (preg_match('#^(/.+?)/sw\.php#', $script, $m)) {
	$scope = rtrim($m[1], '/') . '/';
}

$uri = str_replace('\\', '/', (string) (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''));
$path = (string) parse_url($uri, PHP_URL_PATH);

if (preg_match('#^(/[^/]+)/#', $path, $m)) {
	$scope = rtrim($m[1], '/') . '/';
}

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
	|| (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
$host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : 'localhost';
$icon = ($https ? 'https' : 'http') . '://' . $host . rtrim($scope, '/') . '/pwa-icon.php?size=192';
$offline = $scope . 'offline.html';

header('Service-Worker-Allowed: ' . $scope);

echo 'const CACHE_NAME="mobil-app-v3";const SCOPE_PATH=' . json_encode($scope) . ';const OFFLINE_URL=' . json_encode($offline) . ';const DEFAULT_ICON=' . json_encode($icon) . ";\n";
echo <<<'JS'
self.addEventListener('install', function (event) {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(function (cache) {
      return cache.addAll([OFFLINE_URL, DEFAULT_ICON].filter(Boolean));
    })
  );
});
self.addEventListener('activate', function (event) {
  event.waitUntil(self.clients.claim());
});
self.addEventListener('message', function (event) {
  var data = event.data || {};
  if (data.type !== 'SHOW_NOTIFICATION') {
    return;
  }
  self.registration.showNotification(data.title || 'Bildirim', {
    body: data.body || '',
    icon: data.icon || DEFAULT_ICON,
    badge: data.badge || DEFAULT_ICON,
    data: data.data || {},
    tag: data.tag || ('cn-' + Date.now()),
    renotify: true
  });
});
self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var url = (event.notification.data && event.notification.data.url) ? event.notification.data.url : SCOPE_PATH;
  event.waitUntil(clients.openWindow(url));
});
self.addEventListener('push', function (event) {
  var title = 'Bildirim';
  var body = '';
  var url = SCOPE_PATH;
  try {
    if (event.data) {
      var payload = event.data.json();
      title = payload.title || title;
      body = payload.body || payload.message || '';
      url = payload.url || url;
    }
  } catch (e) {}
  event.waitUntil(self.registration.showNotification(title, {
    body: body,
    icon: DEFAULT_ICON,
    data: { url: url }
  }));
});
self.addEventListener('fetch', function (event) {
  if (event.request.method !== 'GET') {
    return;
  }
  event.respondWith(
    fetch(event.request).catch(function () {
      return caches.match(event.request).then(function (cached) {
        if (cached) {
          return cached;
        }
        if (event.request.mode === 'navigate') {
          return caches.match(OFFLINE_URL);
        }
        return new Response('Offline', { status: 503, statusText: 'Offline' });
      });
    })
  );
});
JS;
