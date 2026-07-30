<?php

class Performance
{
	public const KEY_CACHE = 'PERF_CACHE_ENABLED';
	public const KEY_PAGE_CACHE = 'PERF_PAGE_CACHE';
	public const KEY_PAGE_CACHE_TTL = 'PERF_PAGE_CACHE_TTL';
	public const KEY_DEBUG = 'PERF_DEBUG';
	public const KEY_GZIP = 'PERF_GZIP';
	public const KEY_HTML_MINIFY = 'PERF_HTML_MINIFY';
	public const KEY_CSS_BUNDLE = 'PERF_CSS_BUNDLE';

	private const PAGE_CACHE_DIR = 'pages';
	private const CSS_BUNDLE_DIR = 'assets/css';

	/** @var array<string, string> */
	public static function defaults(): array
	{
		return [
			self::KEY_CACHE => '1',
			self::KEY_PAGE_CACHE => '0',
			self::KEY_PAGE_CACHE_TTL => '15',
			self::KEY_DEBUG => '',
			self::KEY_GZIP => '1',
			self::KEY_HTML_MINIFY => '0',
			self::KEY_CSS_BUNDLE => '0',
		];
	}

	public static function ensureDefaults(): void
	{
		foreach (self::defaults() as $key => $value) {
			if (Settings::get($key) === '') {
				Settings::set($key, $value);
			}
		}
	}

	/** @return array<string, string> */
	public static function getConfig(): array
	{
		self::ensureDefaults();
		$config = [];

		foreach (self::defaults() as $key => $default) {
			$config[$key] = Settings::get($key);
		}

		return $config;
	}

	/** @param array<string, mixed> $input */
	public static function saveConfig(array $input): array
	{
		$flags = [
			self::KEY_CACHE,
			self::KEY_PAGE_CACHE,
			self::KEY_GZIP,
			self::KEY_HTML_MINIFY,
			self::KEY_CSS_BUNDLE,
		];

		foreach ($flags as $key) {
			Settings::set($key, !empty($input[$key]) ? '1' : '0');
		}

		$ttl = max(1, min(1440, (int) ($input[self::KEY_PAGE_CACHE_TTL] ?? 15)));
		Settings::set(self::KEY_PAGE_CACHE_TTL, (string) $ttl);

		if (array_key_exists('perf_debug_mode', $input)) {
			$debugMode = (string) $input['perf_debug_mode'];

			if ($debugMode === 'env') {
				Settings::set(self::KEY_DEBUG, '');
			} else {
				Settings::set(self::KEY_DEBUG, $debugMode === '1' ? '1' : '0');
			}
		}

		App::configureErrors();
		self::clearCssBundleCache();

		return ['success' => true, 'message' => self::t('Performance settings have been saved')];
	}

	public static function isCacheEnabled(): bool
	{
		return Settings::get(self::KEY_CACHE) !== '0';
	}

	public static function isPageCacheEnabled(): bool
	{
		return Settings::get(self::KEY_PAGE_CACHE) === '1';
	}

	public static function getPageCacheTtl(): int
	{
		$minutes = (int) (Settings::get(self::KEY_PAGE_CACHE_TTL) ?: 15);

		return max(60, $minutes * 60);
	}

	public static function isGzipEnabled(): bool
	{
		return Settings::get(self::KEY_GZIP) !== '0';
	}

	public static function shouldMinifyHtml(): bool
	{
		return Settings::get(self::KEY_HTML_MINIFY) === '1';
	}

	public static function isCssBundleEnabled(): bool
	{
		return Settings::get(self::KEY_CSS_BUNDLE) === '1';
	}

	/**
	 * @param Smarty\Smarty $smarty
	 */
	public static function assignThemeStylesheets($smarty): void
	{
		if (!self::isCssBundleEnabled()) {
			$smarty->assign('themeCssBundleUrl', '');

			return;
		}

		$theme = (string) ($smarty->getTemplateVars('activeTheme') ?? Settings::get('THEME') ?: 'fyazilim');
		$pageCss = $smarty->getTemplateVars('css');
		$pageCss = is_string($pageCss) && $pageCss !== '' && $pageCss !== '0' ? $pageCss : null;
		$themeOptions = $smarty->getTemplateVars('themeOptions');
		$includeHeader2 = is_array($themeOptions) && ($themeOptions['header'] ?? '') === 'header2';

		$url = self::buildThemeCssBundleUrl($theme, $pageCss, $includeHeader2);
		$smarty->assign('themeCssBundleUrl', $url);
	}

	public static function buildThemeCssBundleUrl(string $theme, ?string $pageCss = null, bool $includeHeader2 = false): string
	{
		if (!class_exists('Theme', false) || !Theme::isValidName($theme)) {
			return '';
		}

		$files = self::collectThemeCssFiles($theme, $pageCss, $includeHeader2);

		if ($files === []) {
			return '';
		}

		$signature = self::cssBundleSignature($theme, $files);
		$cacheFile = self::cssBundleCacheDir() . '/' . $theme . '-' . $signature . '.css';

		if (!is_file($cacheFile)) {
			if (!self::writeThemeCssBundle($theme, $files, $cacheFile)) {
				return '';
			}
		}

		global $domain;

		return rtrim((string) $domain, '/') . '/cache/' . self::CSS_BUNDLE_DIR . '/' . $theme . '-' . $signature . '.css';
	}

	/** @return string[] */
	public static function collectThemeCssFiles(string $theme, ?string $pageCss, bool $includeHeader2): array
	{
		$lists = [
			'fyazilim' => [
				'colors.css',
				'custom.css',
				'style.css',
				'pages.css',
				'notifications.css',
				'cart-modal.css',
				'fyazilim.css',
			],
			'blue' => [
				'colors.css',
				'custom.css',
				'style.css',
				'pages.css',
				'notifications.css',
				'cart-modal.css',
			],
			'shopmore' => [
				'colors.css',
				'custom.css',
				'style.css',
				'shopmore.css',
				'pages.css',
				'notifications.css',
				'cart-modal.css',
			],
			'theme4' => [
				'colors.css',
				'custom.css',
				'style.css',
				'shopmore.css',
				'pages.css',
				'notifications.css',
				'cart-modal.css',
			],
			'restoran' => [
				'bootstrap.min.css',
				'colors.css',
				'custom.css',
				'app.css',
				'cart-modal.css',
			],
		];

		$files = $lists[$theme] ?? [];

		if ($includeHeader2 && $theme === 'blue') {
			$files[] = 'header2.css';
		}

		if ($pageCss !== null && preg_match('/^[a-zA-Z0-9_-]+\.css$/', $pageCss)) {
			$files[] = $pageCss;
		}

		$cssDir = self::rootPath() . '/templates/' . $theme . '/css/';
		$existing = [];

		foreach ($files as $file) {
			if (is_file($cssDir . $file)) {
				$existing[] = $file;
			}
		}

		return $existing;
	}

	/** @param string[] $files */
	private static function cssBundleSignature(string $theme, array $files): string
	{
		$cssDir = self::rootPath() . '/templates/' . $theme . '/css/';
		$parts = [$theme, self::coreVersion()];

		foreach ($files as $file) {
			$path = $cssDir . $file;
			$parts[] = $file . ':' . (is_file($path) ? (int) filemtime($path) : 0);
		}

		return substr(hash('sha256', implode('|', $parts)), 0, 16);
	}

	/** @param string[] $files */
	private static function writeThemeCssBundle(string $theme, array $files, string $dest): bool
	{
		$cssDir = self::rootPath() . '/templates/' . $theme . '/css/';
		$chunks = [];

		foreach ($files as $file) {
			$path = $cssDir . $file;
			$content = @file_get_contents($path);

			if ($content === false) {
				continue;
			}

			$content = self::absolutizeCssUrls($content, $theme, $cssDir);
			$chunks[] = '/* ' . $file . " */\n" . trim($content);
		}

		if ($chunks === []) {
			return false;
		}

		$dir = dirname($dest);

		if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
			return false;
		}

		return file_put_contents($dest, implode("\n\n", $chunks) . "\n") !== false;
	}

	private static function absolutizeCssUrls(string $css, string $theme, string $cssDir): string
	{
		global $domain;

		$webRoot = rtrim((string) $domain, '/') . '/';
		$cssDir = rtrim(str_replace('\\', '/', $cssDir), '/') . '/';

		return (string) preg_replace_callback(
			'#url\(\s*([\'"]?)([^\'")]+)\1\s*\)#i',
			static function (array $match) use ($webRoot, $cssDir): string {
				$path = trim($match[2]);

				if ($path === ''
					|| preg_match('#^(data:|https?:|//|/)#i', $path)
				) {
					return $match[0];
				}

				$absolute = realpath($cssDir . $path);

				if ($absolute === false || !is_file($absolute)) {
					return $match[0];
				}

				$root = dirname(__DIR__);
				$relative = str_replace('\\', '/', substr($absolute, strlen($root)));
				$relative = ltrim($relative, '/');

				return 'url(' . $webRoot . $relative . ')';
			},
			$css
		);
	}

	private static function cssBundleCacheDir(): string
	{
		return self::rootPath() . '/cache/' . self::CSS_BUNDLE_DIR;
	}

	/** @return array{files: int, bytes: int} */
	public static function clearCssBundleCache(): array
	{
		return self::clearDirectory(self::cssBundleCacheDir());
	}

	/** @return 'env'|'0'|'1' */
	public static function getDebugMode(): string
	{
		$value = Settings::get(self::KEY_DEBUG);

		if ($value === '1' || $value === '0') {
			return $value;
		}

		return 'env';
	}

	public static function bootstrapFront(): void
	{
		if (!defined('IN_SCRIPT')) {
			return;
		}

		if (self::tryServePageCache()) {
			exit;
		}

		if (self::isPageCacheEnabled() && self::canUsePageCache()) {
			ob_start([self::class, 'finishPageCacheBuffer']);

			return;
		}

		if (self::isGzipEnabled() && self::canUseGzip()) {
			ob_start('ob_gzhandler');
		} elseif (self::shouldMinifyHtml()) {
			ob_start([self::class, 'minifyHtml']);
		}
	}

	public static function tryServePageCache(): bool
	{
		if (!self::isPageCacheEnabled() || !self::canUsePageCache()) {
			return false;
		}

		$file = self::getPageCacheFile();

		if ($file === null || !is_file($file)) {
			return false;
		}

		if ((time() - filemtime($file)) >= self::getPageCacheTtl()) {
			@unlink($file);

			return false;
		}

		$html = file_get_contents($file);

		if ($html === false) {
			return false;
		}

		self::sendHtmlResponse($html);

		return true;
	}

	public static function finishPageCacheBuffer(string $html): string
	{
		$file = self::getPageCacheFile();

		if ($file !== null) {
			$dir = dirname($file);

			if (!is_dir($dir)) {
				mkdir($dir, 0755, true);
			}

			file_put_contents($file, $html);
		}

		$html = self::minifyHtml($html);

		if (self::isGzipEnabled() && self::canUseGzip()) {
			header('Content-Encoding: gzip');
			header('Vary: Accept-Encoding', false);

			return (string) gzencode($html, 6);
		}

		return $html;
	}

	public static function minifyHtml(string $html): string
	{
		if (!self::shouldMinifyHtml() || stripos($html, '<html') === false) {
			return $html;
		}

		$preserved = [];
		$html = preg_replace_callback(
			'#<(pre|textarea|script|style)\b[^>]*>.*?</\1>#is',
			static function ($match) use (&$preserved) {
				$key = '@@FSHOP_BLOCK_' . count($preserved) . '@@';
				$preserved[$key] = $match[0];

				return $key;
			},
			$html
		) ?? $html;

		$html = preg_replace('/\s{2,}/', ' ', $html) ?? $html;
		$html = preg_replace('/>\s+</', '><', $html) ?? $html;

		return strtr($html, $preserved);
	}

	/** @return array{success: bool, message: string, stats?: array<string, mixed>} */
	public static function clearCaches(): array
	{
		$compile = self::clearDirectory(self::rootPath() . '/cache/force');
		$smarty = self::clearDirectory(self::rootPath() . '/cache/cache');
		$pages = self::clearDirectory(self::pageCacheRoot());
		$cssBundles = self::clearCssBundleCache();

		$total = $compile['files'] + $smarty['files'] + $pages['files'] + $cssBundles['files'];

		return [
			'success' => true,
			'message' => self::t('Cache cleared'),
			'stats' => self::getStats(),
		];
	}

	/** @return array<string, mixed> */
	public static function getStats(): array
	{
		$compile = self::directoryStats(self::rootPath() . '/cache/force');
		$smarty = self::directoryStats(self::rootPath() . '/cache/cache');
		$pages = self::directoryStats(self::pageCacheRoot());
		$cssBundles = self::directoryStats(self::cssBundleCacheDir());

		$opcache = false;

		if (function_exists('opcache_get_status')) {
			$status = opcache_get_status(false);
			$opcache = is_array($status) && !empty($status['opcache_enabled']);
		}

		return [
			'compile_files' => $compile['files'],
			'compile_bytes' => $compile['bytes'],
			'compile_size_kb' => number_format($compile['bytes'] / 1024, 1),
			'smarty_files' => $smarty['files'],
			'smarty_bytes' => $smarty['bytes'],
			'page_files' => $pages['files'],
			'page_bytes' => $pages['bytes'],
			'page_size_kb' => number_format($pages['bytes'] / 1024, 1),
			'css_bundle_files' => $cssBundles['files'],
			'css_bundle_bytes' => $cssBundles['bytes'],
			'opcache_enabled' => $opcache,
			'zlib_enabled' => extension_loaded('zlib'),
		];
	}

	private static function canUsePageCache(): bool
	{
		if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
			return false;
		}

		if (Customer::isLoggedIn()) {
			return false;
		}

		if (Tools::getValue('theme_preview') !== '' || Tools::getValue('q') !== '' || Tools::getValue('query') !== '') {
			return false;
		}

		$path = self::normalizeRequestPath();
		$blocked = [
			'cart', 'checkout', 'checkout-success', 'login', 'register', 'forgot-password',
			'reset-password', 'my-account', 'orders', 'order', 'favorites', 'api', 'admin',
		];

		if ($path === '' || $path === 'index.php') {
			return true;
		}

		$first = explode('/', $path)[0] ?? '';

		return !in_array($first, $blocked, true);
	}

	private static function normalizeRequestPath(): string
	{
		$path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
		$domain = Settings::get('DOMAIN');
		$base = (string) (parse_url($domain, PHP_URL_PATH) ?: '');

		if ($base !== '' && $base !== '/' && strpos($path, $base) === 0) {
			$path = substr($path, strlen($base)) ?: '/';
		}

		return trim($path, '/');
	}

	private static function getPageCacheFile(): ?string
	{
		$uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
		$theme = Settings::get('THEME') ?: 'default';
		$lang = $_SESSION['selectLang'] ?? Lang::getDefault();
		$key = hash('sha256', $uri . '|' . $theme . '|' . $lang);

		return self::pageCacheRoot() . '/' . $key . '.html';
	}

	private static function pageCacheRoot(): string
	{
		return self::rootPath() . '/cache/' . self::PAGE_CACHE_DIR;
	}

	private static function rootPath(): string
	{
		return dirname(__DIR__);
	}

	private static function canUseGzip(): bool
	{
		if (headers_sent() || !extension_loaded('zlib')) {
			return false;
		}

		if (ini_get('zlib.output_compression')) {
			return false;
		}

		$accept = (string) ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '');

		return stripos($accept, 'gzip') !== false;
	}

	private static function sendHtmlResponse(string $html): void
	{
		if (self::isGzipEnabled() && self::canUseGzip()) {
			header('Content-Encoding: gzip');
			header('Vary: Accept-Encoding', false);
			header('X-FShop-Cache: HIT');
			echo gzencode($html, 6);

			return;
		}

		header('X-FShop-Cache: HIT');
		echo $html;
	}

	/** @return array{files: int, bytes: int} */
	private static function clearDirectory(string $dir): array
	{
		$stats = ['files' => 0, 'bytes' => 0];

		if (!is_dir($dir)) {
			return $stats;
		}

		foreach (scandir($dir) ?: [] as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}

			$path = $dir . '/' . $entry;

			if (is_file($path) && @unlink($path)) {
				$stats['files']++;
			} elseif (is_dir($path)) {
				$nested = self::clearDirectory($path);
				$stats['files'] += $nested['files'];
				@rmdir($path);
			}
		}

		return $stats;
	}

	/** @return array{files: int, bytes: int} */
	private static function directoryStats(string $dir): array
	{
		$stats = ['files' => 0, 'bytes' => 0];

		if (!is_dir($dir)) {
			return $stats;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
		);

		foreach ($iterator as $item) {
			/** @var SplFileInfo $item */
			if (!$item->isFile()) {
				continue;
			}

			$stats['files']++;
			$stats['bytes'] += (int) $item->getSize();
		}

		return $stats;
	}
	private static function t(string $message): string
	{
		return function_exists('translate') ? translate($message) : $message;
	}

	public static function versionedUrl(string $url, ?string $filePath = null): string
	{
		$url = trim($url);

		if ($url === '') {
			return $url;
		}

		$version = self::fileVersion($filePath ?? self::urlToPath($url));

		if (preg_match('/([?&])v=[^&]*/', $url)) {
			return (string) preg_replace('/([?&])v=[^&]*/', '${1}v=' . rawurlencode($version), $url);
		}

		return $url . (strpos($url, '?') !== false ? '&' : '?') . 'v=' . rawurlencode($version);
	}

	public static function fileVersion(?string $filePath): string
	{
		$base = self::coreVersion();

		if ($filePath !== null && is_file($filePath)) {
			return $base . '.' . (int) filemtime($filePath);
		}

		return $base;
	}

	private static function coreVersion(): string
	{
		if (!class_exists('FShop', false)) {
			require_once dirname(__DIR__) . '/core/FShop.php';
		}

		return FShop::version();
	}

	private static function urlToPath(string $url): ?string
	{
		$path = (string) (parse_url($url, PHP_URL_PATH) ?: '');

		if ($path === '') {
			return null;
		}

		$root = self::rootPath();

		if (preg_match('#/templates/([^/]+)/css/([^/?]+)$#', $path, $m)) {
			return $root . '/templates/' . $m[1] . '/css/' . $m[2];
		}

		if (preg_match('#/templates/([^/]+)/js/([^/?]+)$#', $path, $m)) {
			return $root . '/templates/' . $m[1] . '/js/' . $m[2];
		}

		if (preg_match('#/modules/([^/]+)/assets/(css|js)/([^/?]+)$#', $path, $m)) {
			return $root . '/modules/' . $m[1] . '/assets/' . $m[2] . '/' . $m[3];
		}

		if (preg_match('#/img/([^/?]+)$#', $path, $m)) {
			return $root . '/img/' . $m[1];
		}

		return null;
	}
}
