<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: public, max-age=120');
header('Service-Worker-Allowed: /');

$scope = '/';
$script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

if (preg_match('#^(/.+?)/api/#', $script, $m)) {
	$scope = rtrim($m[1], '/') . '/';
}

$host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
	|| ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
$origin = ($https ? 'https' : 'http') . '://' . $host;
$icon = $origin . rtrim($scope, '/') . '/img/favicon.ico';
$iconFile = dirname(__DIR__, 3) . '/img/favicon.ico';
if (is_file($iconFile)) {
	$icon .= '?v=' . filemtime($iconFile);
}


echo 'const CN_SCOPE=' . json_encode($scope) . ';const CN_ICON=' . json_encode($icon) . ";\n";
echo <<<'JS'
self.addEventListener('install', function (event) {
  self.skipWaiting();
});
self.addEventListener('activate', function (event) {
  event.waitUntil(self.clients.claim());
});
self.addEventListener('message', function (event) {
  var data = event.data || {};
  if (data.type !== 'SHOW_NOTIFICATION') {
    return;
  }
  var title = data.title || 'Bildirim';
  var options = {
    body: data.body || '',
    icon: data.icon || CN_ICON,
    badge: data.badge || CN_ICON,
    data: data.data || { url: CN_SCOPE },
    tag: data.tag || ('cn-' + Date.now()),
    renotify: true
  };
  event.waitUntil(self.registration.showNotification(title, options));
});
self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var url = (event.notification.data && event.notification.data.url) ? event.notification.data.url : CN_SCOPE;
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
      for (var i = 0; i < list.length; i++) {
        var client = list[i];
        if (client.url && 'focus' in client) {
          client.navigate(url);
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(url);
      }
    })
  );
});
self.addEventListener('push', function (event) {
  var title = 'Bildirim';
  var body = '';
  var url = CN_SCOPE;
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
    icon: CN_ICON,
    data: { url: url }
  }));
});
JS;
exit;
