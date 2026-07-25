<?php

/**
 * PWA manifest / ikon yardımcıları — App.php ve settings.php yüklenmeden kullanılır.
 */

/**
 * @return PDO|null
 */
function fshop_pwa_pdo()
{
	static $pdo = null;
	static $tried = false;

	if ($tried) {
		return $pdo;
	}

	$tried = true;
	$envFile = dirname(__DIR__) . '/config/env.php';

	if (!is_file($envFile)) {
		return null;
	}

	$env = require $envFile;

	if (!is_array($env)) {
		return null;
	}

	try {
		$pdo = new PDO(
			'mysql:host=' . (isset($env['DB_HOST']) ? $env['DB_HOST'] : 'localhost')
				. ';dbname=' . (isset($env['DB_NAME']) ? $env['DB_NAME'] : 'fshop') . ';charset=utf8mb4',
			isset($env['DB_USER']) ? $env['DB_USER'] : '',
			isset($env['DB_PASS']) ? $env['DB_PASS'] : '',
			[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
		);
	} catch (Exception $e) {
		$pdo = null;
	}

	return $pdo;
}

/**
 * @return array{icon_192: string, icon_512: string, icon_apple: string, label: string, color: string}
 */
function fshop_pwa_icon_meta()
{
	$meta = [
		'icon_192' => '',
		'icon_512' => '',
		'icon_apple' => '',
		'label' => 'F',
		'color' => '194e70',
	];

	$pdo = fshop_pwa_pdo();

	if ($pdo === null) {
		return $meta;
	}

	try {
		$stmt = $pdo->prepare('SELECT value FROM settings WHERE title = ? LIMIT 1');
		$stmt->execute(['SITE_NAME']);
		$row = $stmt->fetch();

		if ($row && trim((string) $row['value']) !== '') {
			$label = trim((string) $row['value']);
			$meta['label'] = function_exists('mb_substr')
				? strtoupper(mb_substr($label, 0, 1, 'UTF-8'))
				: strtoupper(substr($label, 0, 1));
		}

		$check = $pdo->query("SHOW TABLES LIKE 'mobil_app_settings'");

		if (!$check || !$check->fetch()) {
			return $meta;
		}

		$m = $pdo->query('SELECT short_name, app_name, theme_color, icon_192, icon_512, icon_apple FROM mobil_app_settings WHERE id = 1 LIMIT 1')->fetch();

		if (!is_array($m)) {
			return $meta;
		}

		foreach (['icon_192', 'icon_512', 'icon_apple'] as $key) {
			if (!empty($m[$key])) {
				$meta[$key] = trim((string) $m[$key]);
			}
		}

		$name = trim((string) (isset($m['short_name']) && $m['short_name'] !== '' ? $m['short_name'] : (isset($m['app_name']) ? $m['app_name'] : '')));

		if ($name !== '') {
			$meta['label'] = function_exists('mb_substr')
				? strtoupper(mb_substr($name, 0, 1, 'UTF-8'))
				: strtoupper(substr($name, 0, 1));
		}

		if (!empty($m['theme_color'])) {
			$meta['color'] = ltrim((string) $m['theme_color'], '#');
		}
	} catch (Exception $e) {
		// varsayılan
	}

	return $meta;
}

/**
 * @param string $relative
 * @return string|null
 */
function fshop_pwa_icon_path($relative)
{
	$relative = ltrim(str_replace('\\', '/', (string) $relative), '/');

	if ($relative === '') {
		return null;
	}

	$root = dirname(__DIR__);

	if (strpos($relative, 'assets/') === 0) {
		$path = $root . '/modules/mobil-app/assets/img/' . basename($relative);

		return is_file($path) ? $path : null;
	}

	if (strpos($relative, 'modules/mobil-app/') === 0) {
		$path = $root . '/' . $relative;

		return is_file($path) ? $path : null;
	}

	return null;
}

/**
 * @param string $relative
 * @param string $baseUrl
 * @return string
 */
function fshop_pwa_icon_url($relative, $baseUrl)
{
	$relative = ltrim(str_replace('\\', '/', (string) $relative), '/');

	if ($relative === '') {
		return '';
	}

	if (strpos($relative, 'http://') === 0 || strpos($relative, 'https://') === 0) {
		return $relative;
	}

	if (strpos($relative, 'modules/mobil-app/') === 0) {
		return rtrim($baseUrl, '/') . '/' . $relative;
	}

	if (strpos($relative, 'assets/') === 0) {
		return rtrim($baseUrl, '/') . '/modules/mobil-app/' . $relative;
	}

	return rtrim($baseUrl, '/') . '/' . $relative;
}

/**
 * @param array{icon_192: string, icon_512: string, icon_apple: string} $meta
 * @param int $size
 * @return string|null
 */
function fshop_pwa_icon_file_for_size(array $meta, $size)
{
	if ($size >= 512) {
		$candidates = ['icon_512', 'icon_192'];
	} elseif ($size <= 180) {
		$candidates = ['icon_apple', 'icon_192', 'icon_512'];
	} else {
		$candidates = ['icon_192', 'icon_512'];
	}

	foreach ($candidates as $key) {
		$path = fshop_pwa_icon_path((string) ($meta[$key] ?? ''));

		if ($path !== null) {
			return $path;
		}
	}

	return null;
}

/**
 * @param string $baseUrl
 * @return array<int, array<string, string>>
 */
function fshop_pwa_manifest_icon_entries($baseUrl)
{
	$meta = fshop_pwa_icon_meta();
	$entries = [];
	$pairs = [
		['icon_192', '192x192', 192],
		['icon_512', '512x512', 512],
	];

	foreach ($pairs as [$key, $sizeLabel, $px]) {
		$relative = (string) ($meta[$key] ?? '');
		$url = '';

		if ($relative !== '' && fshop_pwa_icon_path($relative) !== null) {
			$url = fshop_pwa_icon_url($relative, $baseUrl);
		} else {
			$file = fshop_pwa_icon_file_for_size($meta, $px);

			if ($file !== null) {
				$url = fshop_pwa_icon_url('assets/img/' . basename($file), $baseUrl);
			}
		}

		if ($url === '') {
			$url = rtrim($baseUrl, '/') . '/pwa-icon.php?size=' . $px;
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

/**
 * @param int $size
 * @return void
 */
function fshop_pwa_serve_icon($size)
{
	if (!in_array($size, [180, 192, 512], true)) {
		$size = 512;
	}

	$meta = fshop_pwa_icon_meta();
	$file = fshop_pwa_icon_file_for_size($meta, $size);

	if ($file !== null && is_readable($file)) {
		if (!headers_sent()) {
			header('Content-Type: image/png');
			header('Cache-Control: public, max-age=86400');
			http_response_code(200);
		}

		readfile($file);

		return;
	}

	if (!function_exists('imagecreatetruecolor')) {
		http_response_code(503);
		exit;
	}

	$hex = preg_replace('/[^0-9a-fA-F]/', '', $meta['color']);

	if (strlen($hex) !== 6) {
		$hex = '194e70';
	}

	$r = hexdec(substr($hex, 0, 2));
	$g = hexdec(substr($hex, 2, 2));
	$b = hexdec(substr($hex, 4, 2));

	$canvas = imagecreatetruecolor($size, $size);
	$bg = imagecolorallocate($canvas, $r, $g, $b);
	imagefilledrectangle($canvas, 0, 0, $size, $size, $bg);

	$white = imagecolorallocate($canvas, 255, 255, 255);
	$font = 5;
	$letter = substr($meta['label'], 0, 1);
	$tw = imagefontwidth($font) * strlen($letter);
	$th = imagefontheight($font);
	imagestring($canvas, $font, (int) (($size - $tw) / 2), (int) (($size - $th) / 2), $letter, $white);

	imagepng($canvas);
	imagedestroy($canvas);
}
