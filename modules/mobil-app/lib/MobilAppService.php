<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN') && !defined('IN_PWA')) {
	exit;
}

class MobilAppService
{
	private const TABLE = 'mobil_app_settings';
	private const ROW_ID = 1;
	private const CACHE_VERSION = 'v2';

	public static function ensureSchema(): void
	{
		DB::execute(
			'CREATE TABLE IF NOT EXISTS `' . self::TABLE . '` (
				`id` tinyint unsigned NOT NULL DEFAULT 1,
				`enabled` tinyint(1) NOT NULL DEFAULT 1,
				`app_name` varchar(128) NOT NULL DEFAULT \'\',
				`short_name` varchar(64) NOT NULL DEFAULT \'\',
				`description` varchar(255) NOT NULL DEFAULT \'\',
				`theme_color` varchar(7) NOT NULL DEFAULT \'#194e70\',
				`background_color` varchar(7) NOT NULL DEFAULT \'#ffffff\',
				`orientation` varchar(32) NOT NULL DEFAULT \'portrait-primary\',
				`menu_enabled` tinyint(1) NOT NULL DEFAULT 1,
				`menu_label` varchar(128) NOT NULL DEFAULT \'Uygulamayı yükle\',
				`menu_hint_ios` varchar(255) NOT NULL DEFAULT \'Safari\'de Paylaş > Ana Ekrana Ekle\',
				`icon_192` varchar(255) NOT NULL DEFAULT \'\',
				`icon_512` varchar(255) NOT NULL DEFAULT \'\',
				`icon_apple` varchar(255) NOT NULL DEFAULT \'\',
				`offline_title` varchar(128) NOT NULL DEFAULT \'İnternet bağlantısı yok\',
				`offline_message` varchar(255) NOT NULL DEFAULT \'Bağlantınızı kontrol edip tekrar deneyin.\',
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		);

		if (!DB::getRowSafe(self::TABLE, 'id = ?', [self::ROW_ID])) {
			self::insertDefaults();
		}
	}

	public static function isModuleActive(): bool
	{
		try {
			$row = DB::getRowSafe('modules', 'name = ? AND installed = 1 AND active = 1', ['mobil-app']);

			return $row !== null;
		} catch (Throwable $e) {
			return false;
		}
	}

	public static function isEnabled(): bool
	{
		if (!self::isModuleActive()) {
			return false;
		}

		self::ensureSchema();
		$row = self::getRow();

		return $row && (int) ($row['enabled'] ?? 0) === 1;
	}

	/** @return array<string, mixed> */
	public static function getSettings(): array
	{
		try {
			self::ensureSchema();
			$row = self::getRow();

			if (!$row) {
				self::insertDefaults();
				$row = self::getRow() ?: [];
			}

			return self::normalizeRow($row);
		} catch (Throwable $e) {
			error_log('MobilAppService::getSettings: ' . $e->getMessage());

			return self::fallbackSettings();
		}
	}

	/** @param array<string, mixed> $input */
	public static function saveSettings(array $input): bool
	{
		self::ensureSchema();

		$data = [
			'enabled' => !empty($input['enabled']) ? 1 : 0,
			'app_name' => self::clip((string) ($input['app_name'] ?? ''), 128),
			'short_name' => self::clip((string) ($input['short_name'] ?? ''), 64),
			'description' => self::clip((string) ($input['description'] ?? ''), 255),
			'theme_color' => self::normalizeColor((string) ($input['theme_color'] ?? '#194e70')),
			'background_color' => self::normalizeColor((string) ($input['background_color'] ?? '#ffffff')),
			'orientation' => self::normalizeOrientation((string) ($input['orientation'] ?? 'portrait-primary')),
			'menu_enabled' => !empty($input['menu_enabled']) ? 1 : 0,
			'menu_label' => self::clip((string) ($input['menu_label'] ?? 'Uygulamayı yükle'), 128),
			'menu_hint_ios' => self::clip((string) ($input['menu_hint_ios'] ?? ''), 255),
			'offline_title' => self::clip((string) ($input['offline_title'] ?? ''), 128),
			'offline_message' => self::clip((string) ($input['offline_message'] ?? ''), 255),
		];

		$current = self::getSettings();

		foreach (['icon_192', 'icon_512', 'icon_apple'] as $iconKey) {
			if (!empty($input[$iconKey])) {
				$data[$iconKey] = (string) $input[$iconKey];
			} else {
				$data[$iconKey] = (string) ($current[$iconKey] ?? '');
			}
		}

		$sets = [];
		$params = [];

		foreach ($data as $key => $value) {
			$sets[] = '`' . $key . '` = ?';
			$params[] = $value;
		}

		$params[] = self::ROW_ID;

		return (bool) DB::execute(
			'UPDATE `' . self::TABLE . '` SET ' . implode(', ', $sets) . ' WHERE id = ?',
			$params
		);
	}

	public static function getScopePath(): string
	{
		try {
			$folder = trim((string) Settings::get('FOLDER'), '/');

			if ($folder !== '' && $folder !== '/') {
				return '/' . $folder . '/';
			}
		} catch (Throwable $e) {
			// ignore
		}

		return self::detectScopePath();
	}

	public static function detectScopePath(): string
	{
		$script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

		if (preg_match('#^(/.+?)/(?:manifest\.php|manifest\.json|sw\.php|sw\.js|pwa-icon\.php|index\.php)#', $script, $m)) {
			return rtrim($m[1], '/') . '/';
		}

		$uri = str_replace('\\', '/', (string) ($_SERVER['REQUEST_URI'] ?? ''));
		$uri = (string) parse_url($uri, PHP_URL_PATH);

		if (preg_match('#^/([^/]+)/(?:manifest\.php|manifest\.json|sw\.js|sw\.php|pwa-icon\.php)#', $uri, $m)) {
			return '/' . $m[1] . '/';
		}

		return '/';
	}

	public static function detectDomain(): string
	{
		try {
			$domain = trim((string) Settings::get('DOMAIN'));

			if ($domain !== '') {
				return rtrim($domain, '/') . '/';
			}
		} catch (Throwable $e) {
			// ignore
		}

		$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
			|| (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
		$scheme = $https ? 'https' : 'http';
		$host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
		$scope = rtrim(self::detectScopePath(), '/');

		return $scheme . '://' . $host . ($scope !== '' ? $scope : '') . '/';
	}

	public static function getStartUrl(): string
	{
		return self::getScopePath();
	}

	public static function getDomain(): string
	{
		try {
			$domain = trim((string) Settings::get('DOMAIN'));

			if ($domain !== '') {
				return rtrim($domain, '/') . '/';
			}
		} catch (Throwable $e) {
			// ignore
		}

		return self::detectDomain();
	}

	public static function absoluteUrl(string $path): string
	{
		$path = ltrim($path, '/');
		$domain = rtrim((string) Settings::get('DOMAIN'), '/');

		return $domain . '/' . $path;
	}

	/** @return array<string, mixed> */
	public static function buildManifest(): array
	{
		$s = self::getEffectiveSettings();
		$scope = self::getScopePath();
		$icons = self::buildIconEntries($s);

		return [
			'name' => (string) ($s['app_name'] ?: 'FShop'),
			'short_name' => (string) ($s['short_name'] ?: self::clip((string) ($s['app_name'] ?: 'FShop'), 12)),
			'description' => (string) ($s['description'] ?: 'Online alışveriş'),
			'id' => $scope,
			'lang' => self::getLangCode(),
			'start_url' => $scope . '?source=pwa',
			'scope' => $scope,
			'display' => 'standalone',
			'background_color' => (string) ($s['background_color'] ?: '#ffffff'),
			'theme_color' => (string) ($s['theme_color'] ?: '#194e70'),
			'orientation' => (string) ($s['orientation'] ?: 'portrait-primary'),
			'categories' => ['shopping'],
			'icons' => $icons,
		];
	}

	public static function renderManifestJson(): string
	{
		$json = json_encode(self::buildManifest(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

		return is_string($json) ? $json : self::renderEmergencyManifestJson();
	}

	public static function renderEmergencyManifestJson(): string
	{
		$scope = self::getScopePath();
		$domain = rtrim(self::getDomain(), '/');
		$siteName = 'FShop';

		try {
			$name = trim((string) Settings::get('SITE_NAME'));
			if ($name !== '') {
				$siteName = $name;
			}
		} catch (Throwable $e) {
			// ignore
		}

		$data = [
			'name' => $siteName,
			'short_name' => self::clip($siteName, 12),
			'description' => 'Online alışveriş',
			'id' => $scope,
			'start_url' => $scope . '?source=pwa',
			'scope' => $scope,
			'display' => 'standalone',
			'background_color' => '#ffffff',
			'theme_color' => '#194e70',
			'icons' => [
				[
					'src' => $domain . '/pwa-icon.php?size=192',
					'sizes' => '192x192',
					'type' => 'image/png',
					'purpose' => 'any maskable',
				],
				[
					'src' => $domain . '/pwa-icon.php?size=512',
					'sizes' => '512x512',
					'type' => 'image/png',
					'purpose' => 'any maskable',
				],
			],
		];

		$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

		return is_string($json) ? $json : '{}';
	}

	public static function renderServiceWorker(): string
	{
		$s = self::getEffectiveSettings();
		$scope = self::getScopePath();
		$offlineUrl = $scope . 'offline.html';
		$icon = self::resolveIconUrl($s['icon_192'] !== '' ? $s['icon_192'] : $s['icon_512']);

		if ($icon === '') {
			$icon = self::getDynamicIconUrl(192);
		}
		$cacheName = 'mobil-app-' . self::CACHE_VERSION;

		$js = <<<'JS'
/* FShop mobil-app service worker */
const CACHE_NAME = '__CACHE_NAME__';
const SCOPE_PATH = '__SCOPE_PATH__';
const OFFLINE_URL = '__OFFLINE_URL__';
const DEFAULT_ICON = '__DEFAULT_ICON__';

self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) =>
      cache.addAll([OFFLINE_URL, DEFAULT_ICON].filter(Boolean))
    )
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys.filter((key) => key.startsWith('mobil-app-') && key !== CACHE_NAME).map((key) => caches.delete(key))
      )
    ).then(() => self.clients.claim())
  );
});

let HAS_PWA = false;
let HAS_PWA_TS = 0;

function isPwaAlive() {
  return HAS_PWA && Date.now() - HAS_PWA_TS < 120000;
}

self.addEventListener('message', (event) => {
  const data = event.data || {};

  if (data.type === 'I_AM_PWA') {
    HAS_PWA = true;
    HAS_PWA_TS = Date.now();
    return;
  }

  if (data.type === 'HAS_PWA_CLIENT') {
    event.ports?.[0]?.postMessage({ hasPwa: isPwaAlive() });
    return;
  }

  if (data.type === 'SHOW_NOTIFICATION') {
    const title = data.title || 'Bildirim';
    const options = {
      body: data.body || '',
      icon: data.icon || DEFAULT_ICON,
      badge: data.badge || DEFAULT_ICON,
      data: data.data || {},
    };
    self.registration.showNotification(title, options);
    return;
  }
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = event.notification.data?.url || SCOPE_PATH;
  event.waitUntil(clients.openWindow(url));
});

self.addEventListener('fetch', (event) => {
  const req = event.request;

  if (req.method !== 'GET') {
    return;
  }

  event.respondWith(
    fetch(req)
      .then((res) => {
        if (res && res.status === 200 && req.url.startsWith(self.location.origin)) {
          const copy = res.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
        }
        return res;
      })
      .catch(async () => {
        const cached = await caches.match(req);
        if (cached) {
          return cached;
        }
        if (req.mode === 'navigate') {
          const offline = await caches.match(OFFLINE_URL);
          if (offline) {
            return offline;
          }
        }
        return new Response('Offline', { status: 503, statusText: 'Offline' });
      })
  );
});
JS;

		return str_replace(
			['__CACHE_NAME__', '__SCOPE_PATH__', '__OFFLINE_URL__', '__DEFAULT_ICON__'],
			[
				addslashes($cacheName),
				addslashes($scope),
				addslashes($offlineUrl),
				addslashes($icon),
			],
			$js
		);
	}

	/** @return array<string, mixed> */
	public static function getEffectiveSettings(): array
	{
		if (defined('IN_PWA')) {
			return self::getReadOnlySettings();
		}

		try {
			if (self::isModuleActive()) {
				return self::getSettings();
			}
		} catch (Throwable $e) {
			error_log('MobilAppService::getEffectiveSettings: ' . $e->getMessage());
		}

		return self::fallbackSettings();
	}

	/** @return array<string, mixed> */
	public static function getReadOnlySettings(): array
	{
		try {
			if (self::isModuleActive() && self::tableExists()) {
				$row = DB::getRowSafe(self::TABLE, 'id = ?', [self::ROW_ID]);

				if ($row) {
					return self::normalizeRow($row);
				}
			}
		} catch (Throwable $e) {
			error_log('MobilAppService::getReadOnlySettings: ' . $e->getMessage());
		}

		return self::fallbackSettings();
	}

	private static function tableExists(): bool
	{
		try {
			$tables = DB::execute("SHOW TABLES LIKE '" . self::TABLE . "'");

			return !empty($tables);
		} catch (Throwable $e) {
			return false;
		}
	}

	public static function renderStaticManifestJson(): string
	{
		return self::renderEmergencyManifestJson();
	}

	/** @return array<string, mixed> */
	public static function fallbackSettings(): array
	{
		$siteName = 'FShop';

		try {
			$name = trim((string) Settings::get('SITE_NAME'));
			if ($name !== '') {
				$siteName = $name;
			}
		} catch (Throwable $e) {
			// ignore
		}

		return [
			'enabled' => 1,
			'app_name' => $siteName,
			'short_name' => self::clip($siteName, 12),
			'description' => trim((string) Settings::get('SITE_DESC')) ?: 'Online alışveriş',
			'theme_color' => '#194e70',
			'background_color' => '#ffffff',
			'orientation' => 'portrait-primary',
			'menu_enabled' => 0,
			'menu_label' => 'Uygulamayı yükle',
			'menu_hint_ios' => 'Safari\'de Paylaş > Ana Ekrana Ekle',
			'icon_192' => '',
			'icon_512' => '',
			'icon_apple' => '',
			'offline_title' => 'İnternet bağlantısı yok',
			'offline_message' => 'Bağlantınızı kontrol edip tekrar deneyin.',
		];
	}

	public static function getIconDir(): string
	{
		return dirname(__DIR__) . '/assets/img';
	}

	/** Chrome PWA için 192 + 512 PNG ikonları oluşturur / eksikleri tamamlar. */
	public static function ensureDefaultIcons(): void
	{
		$tableExists = DB::execute("SHOW TABLES LIKE 'mobil_app_settings'");

		if (empty($tableExists)) {
			return;
		}

		$dir = self::getIconDir();

		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		$settings = self::getSettings();
		$needDbUpdate = false;
		$paths = [
			'icon_192' => $dir . '/icon-192.png',
			'icon_512' => $dir . '/icon-512.png',
			'icon_apple' => $dir . '/apple-touch-icon.png',
		];

		foreach ($paths as $key => $file) {
			if (is_file($file)) {
				if ((string) ($settings[$key] ?? '') === '') {
					$settings[$key] = 'assets/img/' . basename($file);
					$needDbUpdate = true;
				}
				continue;
			}

			$source = self::findLogoSourcePath();

			if ($source !== null) {
				$size = $key === 'icon_192' ? 192 : ($key === 'icon_apple' ? 180 : 512);
				$info = @getimagesize($source);

				if ($info && self::saveResizedImage($source, (int) $info[2], $file, $size)) {
					$settings[$key] = 'assets/img/' . basename($file);
					$needDbUpdate = true;
					continue;
				}
			}

			$size = $key === 'icon_192' ? 192 : ($key === 'icon_apple' ? 180 : 512);

			if (self::generatePlaceholderIcon($file, $size, (string) ($settings['theme_color'] ?? '#194e70'), (string) ($settings['short_name'] ?: $settings['app_name']))) {
				$settings[$key] = 'assets/img/' . basename($file);
				$needDbUpdate = true;
			}
		}

		if ($needDbUpdate) {
			self::saveSettings($settings);
		}
	}

	private static function findLogoSourcePath(): ?string
	{
		$root = dirname(__DIR__, 3);
		$candidates = [
			$root . '/img/logo.png',
			$root . '/img/logo.jpg',
			$root . '/img/logo.webp',
		];

		foreach ($candidates as $path) {
			if (is_file($path)) {
				return $path;
			}
		}

		return null;
	}

	private static function generatePlaceholderIcon(string $dest, int $size, string $hexColor, string $label): bool
	{
		if (!function_exists('imagecreatetruecolor')) {
			return false;
		}

		$hexColor = ltrim(self::normalizeColor($hexColor), '#');
		$r = hexdec(substr($hexColor, 0, 2));
		$g = hexdec(substr($hexColor, 2, 2));
		$b = hexdec(substr($hexColor, 4, 2));

		$canvas = imagecreatetruecolor($size, $size);
		$bg = imagecolorallocate($canvas, $r, $g, $b);
		imagefilledrectangle($canvas, 0, 0, $size, $size, $bg);

		$letter = strtoupper(mb_substr(trim($label) !== '' ? trim($label) : 'F', 0, 1, 'UTF-8'));
		$white = imagecolorallocate($canvas, 255, 255, 255);
		$font = 5;
		$textWidth = imagefontwidth($font) * strlen($letter);
		$textHeight = imagefontheight($font);
		$x = (int) (($size - $textWidth) / 2);
		$y = (int) (($size - $textHeight) / 2);
		imagestring($canvas, $font, max(0, $x), max(0, $y), $letter, $white);

		$result = imagepng($canvas, $dest);
		imagedestroy($canvas);

		return $result;
	}

	public static function handleIconUpload(string $field, array $file): ?string
	{
		if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
			return null;
		}

		$tmp = (string) ($file['tmp_name'] ?? '');

		if ($tmp === '' || !is_uploaded_file($tmp)) {
			return null;
		}

		$info = @getimagesize($tmp);

		if (!$info || !in_array((int) $info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)) {
			return null;
		}

		$map = [
			'icon_master' => 'icon-512.png',
			'icon_192' => 'icon-192.png',
			'icon_512' => 'icon-512.png',
			'icon_apple' => 'apple-touch-icon.png',
		];

		if (!isset($map[$field])) {
			return null;
		}

		$dir = self::getIconDir();

		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		$target = $dir . '/' . $map[$field];
		$size = $field === 'icon_192' ? 192 : ($field === 'icon_apple' ? 180 : 512);

		if (!self::saveResizedImage($tmp, (int) $info[2], $target, $size)) {
			return null;
		}

		if ($field === 'icon_master' || $field === 'icon_512') {
			self::saveResizedImage($tmp, (int) $info[2], $dir . '/icon-192.png', 192);
			self::saveResizedImage($tmp, (int) $info[2], $dir . '/apple-touch-icon.png', 180);
		}

		return 'assets/img/' . basename($target);
	}

	private static function saveResizedImage(string $src, int $type, string $dest, int $size): bool
	{
		if (!function_exists('imagecreatetruecolor')) {
			return copy($src, $dest);
		}

		switch ($type) {
			case IMAGETYPE_JPEG:
				$image = @imagecreatefromjpeg($src);
				break;
			case IMAGETYPE_WEBP:
				$image = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false;
				break;
			default:
				$image = @imagecreatefrompng($src);
				break;
		}

		if (!$image) {
			return copy($src, $dest);
		}

		$width = imagesx($image);
		$height = imagesy($image);
		$canvas = imagecreatetruecolor($size, $size);
		imagealphablending($canvas, false);
		imagesavealpha($canvas, true);
		$transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
		imagefilledrectangle($canvas, 0, 0, $size, $size, $transparent);

		$scale = min($size / max(1, $width), $size / max(1, $height));
		$newW = (int) round($width * $scale);
		$newH = (int) round($height * $scale);
		$dstX = (int) round(($size - $newW) / 2);
		$dstY = (int) round(($size - $newH) / 2);

		imagecopyresampled($canvas, $image, $dstX, $dstY, 0, 0, $newW, $newH, $width, $height);
		$result = imagepng($canvas, $dest);
		imagedestroy($image);
		imagedestroy($canvas);

		return $result;
	}

	/** @param array<string, mixed> $settings */
	private static function buildIconEntries(array $settings): array
	{
		$entries = [];
		$pairs = [
			['icon_192', '192x192', 192],
			['icon_512', '512x512', 512],
			['icon_apple', '180x180', 180],
		];

		foreach ($pairs as [$key, $sizeLabel, $px]) {
			$url = self::resolveIconUrl((string) ($settings[$key] ?? ''));

			if ($url === '' || !self::iconFileExists((string) ($settings[$key] ?? ''))) {
				$url = self::getDynamicIconUrl($px);
			}

			$entries[] = [
				'src' => $url,
				'sizes' => $sizeLabel,
				'type' => 'image/png',
				'purpose' => 'any maskable',
			];
		}

		return $entries;
	}

	public static function getDynamicIconUrl(int $size): string
	{
		return rtrim(self::getDomain(), '/') . '/pwa-icon.php?size=' . $size;
	}

	private static function iconFileExists(string $relative): bool
	{
		$path = self::resolveIconPath($relative);

		return $path !== null && is_file($path);
	}

	private static function resolveIconPath(string $relative): ?string
	{
		$relative = ltrim(str_replace('\\', '/', $relative), '/');

		if ($relative === '') {
			return null;
		}

		if (strpos($relative, 'assets/') === 0) {
			$path = self::getIconDir() . '/' . basename($relative);

			return is_file($path) ? $path : null;
		}

		if (strpos($relative, 'modules/mobil-app/assets/') === 0) {
			$path = dirname(__DIR__, 3) . '/' . $relative;

			return is_file($path) ? $path : null;
		}

		return null;
	}

	public static function outputIcon(int $size): void
	{
		if (!in_array($size, [180, 192, 512], true)) {
			$size = 512;
		}

		$settings = self::getEffectiveSettings();
		$key = $size >= 512 ? 'icon_512' : ($size <= 180 ? 'icon_apple' : 'icon_192');
		$file = self::resolveIconPath((string) ($settings[$key] ?? ''));

		if ($file === null && $size === 180) {
			$file = self::resolveIconPath((string) ($settings['icon_192'] ?? ''));
		}

		if ($file !== null && is_file($file)) {
			header('Content-Type: image/png');
			header('Cache-Control: public, max-age=86400');
			readfile($file);
			return;
		}

		header('Content-Type: image/png');
		header('Cache-Control: public, max-age=86400');
		self::streamPlaceholderIcon(
			$size,
			(string) ($settings['theme_color'] ?? '#194e70'),
			(string) (($settings['short_name'] ?? '') ?: ($settings['app_name'] ?? 'F'))
		);
	}

	private static function streamPlaceholderIcon(int $size, string $hexColor, string $label): void
	{
		if (!function_exists('imagecreatetruecolor')) {
			http_response_code(503);
			exit;
		}

		$hexColor = ltrim(self::normalizeColor($hexColor), '#');
		$r = hexdec(substr($hexColor, 0, 2));
		$g = hexdec(substr($hexColor, 2, 2));
		$b = hexdec(substr($hexColor, 4, 2));

		$canvas = imagecreatetruecolor($size, $size);
		$bg = imagecolorallocate($canvas, $r, $g, $b);
		imagefilledrectangle($canvas, 0, 0, $size, $size, $bg);

		$letter = strtoupper(self::clip(trim($label) !== '' ? trim($label) : 'F', 1));
		$white = imagecolorallocate($canvas, 255, 255, 255);
		$font = 5;
		$textWidth = imagefontwidth($font) * strlen($letter);
		$textHeight = imagefontheight($font);
		imagestring(
			$canvas,
			$font,
			max(0, (int) (($size - $textWidth) / 2)),
			max(0, (int) (($size - $textHeight) / 2)),
			$letter,
			$white
		);

		imagepng($canvas);
		imagedestroy($canvas);
	}

	private static function resolveIconUrl(string $relative): string
	{
		$relative = ltrim(str_replace('\\', '/', $relative), '/');

		if ($relative === '') {
			return '';
		}

		if (strpos($relative, 'http://') === 0 || strpos($relative, 'https://') === 0) {
			return $relative;
		}

		if (strpos($relative, 'modules/mobil-app/') === 0) {
			return self::absoluteUrl($relative);
		}

		if (strpos($relative, 'assets/') === 0) {
			return self::absoluteUrl('modules/mobil-app/' . $relative);
		}

		return self::absoluteUrl($relative);
	}

	private static function getLangCode(): string
	{
		global $selectLang;

		$lang = is_string($selectLang ?? null) ? $selectLang : 'tr';

		return $lang !== '' ? $lang : 'tr';
	}

	/** @return array<string, mixed>|null */
	private static function getRow(): ?array
	{
		$row = DB::getRowSafe(self::TABLE, 'id = ?', [self::ROW_ID]);

		return $row ?: null;
	}

	private static function insertDefaults(): void
	{
		$siteName = trim((string) Settings::get('SITE_NAME')) ?: 'FShop';

		DB::execute(
			'INSERT INTO `' . self::TABLE . '` (`id`, `enabled`, `app_name`, `short_name`, `description`) VALUES (?, 1, ?, ?, ?)',
			[
				self::ROW_ID,
				$siteName,
				self::clip($siteName, 12),
				trim((string) Settings::get('SITE_DESC')) ?: 'Online alışveriş',
			]
		);
	}

	/** @param array<string, mixed> $row */
	private static function normalizeRow(array $row): array
	{
		return [
			'enabled' => (int) ($row['enabled'] ?? 0),
			'app_name' => (string) ($row['app_name'] ?? ''),
			'short_name' => (string) ($row['short_name'] ?? ''),
			'description' => (string) ($row['description'] ?? ''),
			'theme_color' => self::normalizeColor((string) ($row['theme_color'] ?? '#194e70')),
			'background_color' => self::normalizeColor((string) ($row['background_color'] ?? '#ffffff')),
			'orientation' => self::normalizeOrientation((string) ($row['orientation'] ?? 'portrait-primary')),
			'menu_enabled' => (int) ($row['menu_enabled'] ?? 1),
			'menu_label' => (string) ($row['menu_label'] ?? 'Uygulamayı yükle'),
			'menu_hint_ios' => (string) ($row['menu_hint_ios'] ?? ''),
			'offline_title' => (string) ($row['offline_title'] ?? ''),
			'offline_message' => (string) ($row['offline_message'] ?? ''),
			'icon_192' => (string) ($row['icon_192'] ?? ''),
			'icon_512' => (string) ($row['icon_512'] ?? ''),
			'icon_apple' => (string) ($row['icon_apple'] ?? ''),
		];
	}

	private static function normalizeColor(string $color): string
	{
		$color = trim($color);

		if ($color === '') {
			return '#194e70';
		}

		if ($color[0] !== '#') {
			$color = '#' . $color;
		}

		if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
			return '#194e70';
		}

		return strtolower($color);
	}

	private static function normalizeOrientation(string $orientation): string
	{
		$allowed = ['any', 'natural', 'landscape', 'portrait', 'portrait-primary', 'portrait-secondary'];

		return in_array($orientation, $allowed, true) ? $orientation : 'portrait-primary';
	}

	private static function clip(string $value, int $max): string
	{
		$value = trim($value);

		if (function_exists('mb_substr')) {
			return mb_substr($value, 0, $max, 'UTF-8');
		}

		return substr($value, 0, $max);
	}
}
