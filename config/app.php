<?php

class App
{
	private static array $env = [];
	private static bool $booted = false;

	public static function boot(): void
	{
		if (self::$booted) {
			return;
		}

		self::$booted = true;

		$envFile = __DIR__ . '/env.php';

		if (!is_file($envFile)) {
			$envFile = __DIR__ . '/env.example.php';
		}

		$config = require $envFile;
		self::$env = is_array($config) ? $config : [];

		self::configureErrors();
	}

	public static function env(string $key, $default = null)
	{
		return self::$env[$key] ?? $default;
	}

	public static function isProduction(): bool
	{
		return self::env('APP_ENV', 'local') === 'production';
	}

	public static function isDebug(): bool
	{
		if (class_exists('Settings', false) && isset($GLOBALS['db'])) {
			$perfDebug = Settings::get('PERF_DEBUG');

			if ($perfDebug === '1') {
				return true;
			}

			if ($perfDebug === '0') {
				return false;
			}
		}

		return self::isDebugFromEnv();
	}

	public static function isDebugFromEnv(): bool
	{
		if (self::isProduction()) {
			return (bool) self::env('APP_DEBUG', false);
		}

		return (bool) self::env('APP_DEBUG', true);
	}

	public static function configureErrors(): void
	{
		$logDir = dirname(__DIR__) . '/logs';

		if (!is_dir($logDir)) {
			@mkdir($logDir, 0755, true);
		}

		$logFile = $logDir . '/php-error.log';

		if (self::isDebug()) {
			ini_set('display_errors', '1');
			ini_set('display_startup_errors', '1');
			error_reporting(E_ALL);
		} else {
			ini_set('display_errors', '0');
			ini_set('display_startup_errors', '0');
			error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
			ini_set('log_errors', '1');
			ini_set('error_log', $logFile);
		}
	}

	public static function configureSession(): void
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			return;
		}

		ini_set('session.use_strict_mode', '1');
		ini_set('session.cookie_httponly', '1');
		ini_set('session.use_only_cookies', '1');

		// Banka / PSP dönüşleri (cross-site POST) için SameSite=None + Secure gerekir.
		// HTTPS yoksa Lax kalır (siteler localhost HTTP'te test ederken).
		$domain = Settings::get('DOMAIN');
		$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
			|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
			|| (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
			|| (is_string($domain) && strpos($domain, 'https://') === 0);

		$sameSite = $secure ? 'None' : 'Lax';

		if (PHP_VERSION_ID >= 70300) {
			session_set_cookie_params([
				'lifetime' => 0,
				'path' => '/',
				'secure' => $secure,
				'httponly' => true,
				'samesite' => $sameSite,
			]);
		}

		ini_set('session.cookie_samesite', $sameSite);

		if ($secure) {
			ini_set('session.cookie_secure', '1');
		}
	}

	public static function sendSecurityHeaders(): void
	{
		if (headers_sent()) {
			return;
		}

		$originSources = self::cspOriginSources();

		header('X-Content-Type-Options: nosniff');
		header('X-Frame-Options: SAMEORIGIN');
		header('Referrer-Policy: strict-origin-when-cross-origin');
		header('X-XSS-Protection: 1; mode=block');
		header(
			'Content-Security-Policy: '
			. "default-src 'self'; "
			. "base-uri 'self'; "
			. "form-action 'self' https:; "
			. "frame-ancestors 'self'; "
			. "img-src 'self' data: https: blob:; "
			. "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net; "
			. "style-src {$originSources} 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; "
			. "script-src {$originSources} 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.onesignal.com https://api.onesignal.com https://www.googletagmanager.com https://googleads.g.doubleclick.net https://www.googleadservices.com https://www.google.com https://www.gstatic.com https://www.recaptcha.net https://connect.facebook.net; "
			. "worker-src {$originSources} blob:; "
			. "manifest-src {$originSources}; "
			. "connect-src {$originSources} https://api.onesignal.com https://cdn.onesignal.com https://www.google-analytics.com https://region1.google-analytics.com https://www.googletagmanager.com https://analytics.google.com https://www.google.com https://www.gstatic.com https://www.recaptcha.net https://www.facebook.com https://connect.facebook.net https://googleads.g.doubleclick.net https://www.googleadservices.com https://ad.doubleclick.net; "
			. "frame-src 'self' https:;"
		);

		if (self::isProduction()) {
			header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
		}
	}

	/** CSP: 'self' + aktif istek ve DOMAIN ayarındaki origin'ler (www / www'siz uyumu). */
	private static function cspOriginSources(): string
	{
		$origins = ["'self'"];
		$seen = [];

		$addOrigin = static function (?string $origin) use (&$origins, &$seen): void {
			$origin = trim((string) $origin);

			if ($origin === '' || isset($seen[$origin])) {
				return;
			}

			$seen[$origin] = true;
			$origins[] = $origin;
		};

		$addHostVariants = static function (string $scheme, string $host) use ($addOrigin): void {
			$host = strtolower(trim($host));

			if ($host === '') {
				return;
			}

			$addOrigin($scheme . '://' . $host);

			if (strpos($host, 'www.') === 0) {
				$addOrigin($scheme . '://' . substr($host, 4));
			} else {
				$addOrigin($scheme . '://www.' . $host);
			}
		};

		$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
			|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
			|| (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
		$scheme = $https ? 'https' : 'http';

		if (!empty($_SERVER['HTTP_HOST'])) {
			$addHostVariants($scheme, (string) $_SERVER['HTTP_HOST']);
		}

		if (class_exists('Settings', false)) {
			$domain = trim((string) Settings::get('DOMAIN'));

			if ($domain !== '') {
				$parsed = parse_url($domain);

				if (!empty($parsed['host'])) {
					$domainScheme = !empty($parsed['scheme']) ? (string) $parsed['scheme'] : $scheme;
					$port = isset($parsed['port']) ? ':' . (int) $parsed['port'] : '';
					$host = strtolower((string) $parsed['host']) . $port;
					$addOrigin($domainScheme . '://' . $host);

					$hostOnly = strtolower((string) $parsed['host']);

					if (strpos($hostOnly, 'www.') === 0) {
						$addOrigin($domainScheme . '://' . substr($hostOnly, 4) . $port);
					} else {
						$addOrigin($domainScheme . '://www.' . $hostOnly . $port);
					}
				}
			}
		}

		return implode(' ', $origins);
	}
}
