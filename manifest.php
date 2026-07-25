<?php

/**
 * PWA manifest — bağımsız endpoint (App.php / settings.php yüklenmez).
 * Her durumda HTTP 200 + geçerli JSON döner.
 */

if (!headers_sent()) {
	header('Content-Type: application/manifest+json; charset=utf-8');
	header('Cache-Control: public, max-age=120');
	http_response_code(200);
}

/**
 * @return string
 */
function fshop_pwa_scope()
{
	$script = str_replace('\\', '/', (string) (isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : ''));

	if (preg_match('#^(/.+?)/manifest\.php#', $script, $m)) {
		return rtrim($m[1], '/') . '/';
	}

	$uri = str_replace('\\', '/', (string) (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''));
	$path = (string) parse_url($uri, PHP_URL_PATH);

	if (preg_match('#^(/[^/]+)/#', $path, $m)) {
		return rtrim($m[1], '/') . '/';
	}

	return '/fshop/';
}

/**
 * @return string
 */
function fshop_pwa_base_url()
{
	$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
		|| (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
	$scope = rtrim(fshop_pwa_scope(), '/');
	$host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : 'localhost';

	return ($https ? 'https' : 'http') . '://' . $host . $scope;
}

/**
 * @param string $value
 * @param int $max
 * @return string
 */
function fshop_pwa_clip($value, $max)
{
	$value = trim((string) $value);

	if (function_exists('mb_substr')) {
		return mb_substr($value, 0, $max, 'UTF-8');
	}

	return substr($value, 0, $max);
}

/**
 * @return array<string, string>
 */
function fshop_pwa_defaults()
{
	return [
		'name' => 'FShop',
		'short_name' => 'FShop',
		'description' => 'Online alışveriş',
		'theme_color' => '#194e70',
		'background_color' => '#ffffff',
		'orientation' => 'portrait-primary',
	];
}

/**
 * @return array<string, string>
 */
function fshop_pwa_load_db_settings()
{
	$settings = fshop_pwa_defaults();
	$envFile = __DIR__ . '/config/env.php';

	if (!is_file($envFile)) {
		return $settings;
	}

	$env = require $envFile;

	if (!is_array($env)) {
		return $settings;
	}

	try {
		$host = isset($env['DB_HOST']) ? (string) $env['DB_HOST'] : 'localhost';
		$name = isset($env['DB_NAME']) ? (string) $env['DB_NAME'] : 'fshop';
		$user = isset($env['DB_USER']) ? (string) $env['DB_USER'] : '';
		$pass = isset($env['DB_PASS']) ? (string) $env['DB_PASS'] : '';

		$pdo = new PDO(
			'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4',
			$user,
			$pass,
			[
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			]
		);

		$stmt = $pdo->prepare('SELECT value FROM settings WHERE title = ? LIMIT 1');

		foreach (['SITE_NAME', 'SITE_DESC'] as $title) {
			$stmt->execute([$title]);
			$row = $stmt->fetch();

			if (!$row || trim((string) $row['value']) === '') {
				continue;
			}

			if ($title === 'SITE_NAME') {
				$settings['name'] = trim((string) $row['value']);
				$settings['short_name'] = fshop_pwa_clip($settings['name'], 12);
			} else {
				$settings['description'] = trim((string) $row['value']);
			}
		}

		$tableCheck = $pdo->query("SHOW TABLES LIKE 'mobil_app_settings'");

		if ($tableCheck && $tableCheck->fetch()) {
			$row = $pdo->query('SELECT app_name, short_name, description, theme_color, background_color, orientation FROM mobil_app_settings WHERE id = 1 LIMIT 1')->fetch();

			if (is_array($row)) {
				foreach (['app_name' => 'name', 'short_name' => 'short_name', 'description' => 'description', 'theme_color' => 'theme_color', 'background_color' => 'background_color', 'orientation' => 'orientation'] as $col => $out) {
					if (!empty($row[$col])) {
						$settings[$out] = trim((string) $row[$col]);
					}
				}
			}
		}
	} catch (Exception $e) {
		// DB yoksa varsayılanlarla devam
	}

	if ($settings['short_name'] === '' && $settings['name'] !== '') {
		$settings['short_name'] = fshop_pwa_clip($settings['name'], 12);
	}

	return $settings;
}

require_once __DIR__ . '/config/pwa_helpers.php';

$scope = fshop_pwa_scope();
$s = fshop_pwa_load_db_settings();
$base = fshop_pwa_base_url();

$manifest = [
	'name' => $s['name'] !== '' ? $s['name'] : 'FShop',
	'short_name' => $s['short_name'] !== '' ? $s['short_name'] : fshop_pwa_clip($s['name'], 12),
	'description' => $s['description'] !== '' ? $s['description'] : 'Online alışveriş',
	'id' => $scope,
	'lang' => 'tr',
	'start_url' => $scope . '?source=pwa',
	'scope' => $scope,
	'display' => 'standalone',
	'background_color' => $s['background_color'],
	'theme_color' => $s['theme_color'],
	'orientation' => $s['orientation'],
	'categories' => ['shopping'],
	'icons' => fshop_pwa_manifest_icon_entries($base),
];

$json = json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

echo is_string($json) ? $json : '{"name":"FShop","short_name":"FShop","display":"standalone","start_url":"/","scope":"/","icons":[]}';
