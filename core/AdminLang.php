<?php

class AdminLang
{
	private static ?array $strings = null;
	private static string $loadedLang = '';

	/** @return string[] */
	public static function getAvailable(): array
	{
		return ['tr', 'en'];
	}

	public static function isValid(string $code): bool
	{
		return in_array($code, self::getAvailable(), true);
	}

	public static function getDefault(): string
	{
		if (class_exists('Settings', false)) {
			$setting = trim((string) Settings::get('ADMIN_DEFAULT_LANG'));

			if (self::isValid($setting)) {
				return $setting;
			}
		}

		return 'en';
	}

	public static function current(): string
	{
		if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['adminLang'])) {
			$code = strtolower(trim((string) $_SESSION['adminLang']));

			if (self::isValid($code)) {
				return $code;
			}
		}

		return self::getDefault();
	}

	public static function handleSwitchRequest(): void
	{
		if (session_status() !== PHP_SESSION_ACTIVE || !isset($_GET['set_admin_lang'])) {
			return;
		}

		$code = strtolower(trim((string) $_GET['set_admin_lang']));

		if (self::isValid($code)) {
			$_SESSION['adminLang'] = $code;
		}

		$redirect = trim((string) ($_GET['redirect'] ?? ''));

		if ($redirect === '') {
			$redirect = parse_url($_SERVER['REQUEST_URI'] ?? self::defaultAdminPath(), PHP_URL_PATH) ?: self::defaultAdminPath();
		}

		if (strpos($redirect, '://') !== false || strncmp($redirect, '//', 2) === 0) {
			$redirect = self::defaultAdminPath();
		}

		header('Location: ' . $redirect);
		exit;
	}

	public static function makeSwitchUrl(string $code): string
	{
		$code = strtolower(trim($code));
		$path = parse_url($_SERVER['REQUEST_URI'] ?? self::defaultAdminPath(), PHP_URL_PATH) ?: self::defaultAdminPath();
		$query = $_GET;
		unset($query['set_admin_lang'], $query['redirect']);

		$query['set_admin_lang'] = $code;
		$query['redirect'] = $path;

		return $path . '?' . http_build_query($query);
	}

	private static function defaultAdminPath(): string
	{
		$uri = 'admin';

		if (class_exists('Admin', false)) {
			$uri = Admin::uri();
		} elseif (class_exists('App', false)) {
			$raw = trim((string) App::env('ADMIN_URI', 'admin'), "/ \t\n\r\0\x0B");
			$sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '', $raw);
			$uri = ($sanitized !== null && $sanitized !== '') ? $sanitized : 'admin';
		}

		$folder = '';

		if (class_exists('Settings', false)) {
			$folder = trim((string) Settings::get('FOLDER'), '/');
		}

		if ($folder !== '') {
			return '/' . $folder . '/' . $uri . '/';
		}

		return '/' . $uri . '/';
	}

	public static function label(string $code): string
	{
		$labels = [
			'tr' => 'Türkçe',
			'en' => 'English',
		];

		return $labels[$code] ?? strtoupper($code);
	}

	/** @return array<int, array{code: string, label: string, url: string, active: bool}> */
	public static function getSwitcherList(): array
	{
		$current = self::current();
		$list = [];

		foreach (self::getAvailable() as $code) {
			$list[] = [
				'code' => $code,
				'label' => self::label($code),
				'url' => self::makeSwitchUrl($code),
				'active' => $code === $current,
			];
		}

		return $list;
	}

	public static function translate(string $text): string
	{
		$langCode = self::current();

		if (self::$strings === null || self::$loadedLang !== $langCode) {
			self::$loadedLang = $langCode;
			$path = dirname(__DIR__) . '/lang/admin/' . $langCode . '.php';

			if (!is_file($path)) {
				$path = dirname(__DIR__) . '/lang/admin/en.php';
			}

			self::$strings = is_file($path) ? require $path : [];

			if (!is_array(self::$strings)) {
				self::$strings = [];
			}
		}

		if (isset(self::$strings[$text])) {
			return (string) self::$strings[$text];
		}

		if ($langCode !== 'en') {
			static $enStrings = null;

			if ($enStrings === null) {
				$enPath = dirname(__DIR__) . '/lang/admin/en.php';
				$enStrings = is_file($enPath) ? require $enPath : [];

				if (!is_array($enStrings)) {
					$enStrings = [];
				}
			}

			if (isset($enStrings[$text])) {
				return (string) $enStrings[$text];
			}
		}

		return $text;
	}

	private static function getLangFilePath(string $code): string
	{
		return dirname(__DIR__) . '/lang/admin/' . strtolower(trim($code)) . '.php';
	}

	/** @return array<string, string> */
	public static function loadUiDictionary(string $code): array
	{
		$code = strtolower(trim($code));

		if (!self::isValid($code) && $code !== 'en') {
			return [];
		}

		$path = self::getLangFilePath($code);

		if (!is_file($path)) {
			return [];
		}

		$map = include $path;

		if (!is_array($map)) {
			return [];
		}

		$out = [];

		foreach ($map as $key => $value) {
			if (!is_string($key)) {
				continue;
			}

			$out[$key] = is_scalar($value) ? (string) $value : '';
		}

		return $out;
	}

	/**
	 * @param array<string, string> $updates
	 * @return array{success:bool,message:string}
	 */
	public static function mergeUiDictionary(string $code, array $updates): array
	{
		$code = strtolower(trim($code));

		if (!self::isValid($code) && $code !== 'en') {
			return self::fail('Invalid language code');
		}

		$existing = self::loadUiDictionary($code);

		foreach ($updates as $key => $value) {
			if (!is_string($key) || $key === '') {
				continue;
			}

			$existing[$key] = trim((string) $value);
		}

		ksort($existing, SORT_STRING);

		$path = self::getLangFilePath($code);
		$dir = dirname($path);

		if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
			return self::fail('Could not create language folder');
		}

		$content = "<?php\n\treturn [\n";

		foreach ($existing as $key => $value) {
			$content .= "\t\t" . var_export($key, true) . ' => ' . var_export($value, true) . ",\n";
		}

		$content .= "\t];\n";

		if (@file_put_contents($path, $content) === false) {
			return self::fail('Could not write language file');
		}

		return self::ok('Translations saved');
	}

	/**
	 * @return array{success:bool,message:string}
	 */
	public static function addUiTranslationKey(
		string $key,
		string $enValue,
		string $targetLang = '',
		string $targetValue = ''
	): array {
		$key = trim($key);
		$enValue = trim($enValue);
		$targetLang = strtolower(trim($targetLang));
		$targetValue = trim($targetValue);

		if ($key === '') {
			return self::fail('Translation key is required');
		}

		if (mb_strlen($key) > 512) {
			return self::fail('Translation key is too long');
		}

		if ($enValue === '') {
			$enValue = $key;
		}

		if (isset(self::loadUiDictionary('en')[$key])) {
			return self::fail('This key already exists');
		}

		$enResult = self::mergeUiDictionary('en', [$key => $enValue]);

		if (empty($enResult['success'])) {
			return $enResult;
		}

		if ($targetLang !== '' && $targetLang !== 'en' && $targetValue !== '' && self::isValid($targetLang)) {
			$targetResult = self::mergeUiDictionary($targetLang, [$key => $targetValue]);

			if (empty($targetResult['success'])) {
				return $targetResult;
			}
		}

		return self::ok('Translation key added');
	}

	/**
	 * @return array{
	 *   rows: list<array{key:string,en:string,translation:string,missing:bool}>,
	 *   total:int,
	 *   missing:int
	 * }
	 */
	public static function getUiTranslationWorkspace(string $targetLang, string $filter = 'all', string $q = ''): array
	{
		$targetLang = strtolower(trim($targetLang));
		$en = self::loadUiDictionary('en');
		$target = self::loadUiDictionary($targetLang);
		$q = mb_strtolower(trim($q));
		$filter = $filter === 'missing' ? 'missing' : 'all';

		$keys = array_unique(array_merge(array_keys($en), array_keys($target)));
		sort($keys, SORT_STRING);

		$rows = [];
		$missingCount = 0;

		foreach ($keys as $key) {
			$enValue = $en[$key] ?? $key;
			$trValue = $target[$key] ?? '';
			$isMissing = $trValue === '' || ($targetLang !== 'en' && $trValue === $key);

			if ($isMissing) {
				$missingCount++;
			}

			if ($filter === 'missing' && !$isMissing) {
				continue;
			}

			if ($q !== '') {
				$hay = mb_strtolower($key . ' ' . $enValue . ' ' . $trValue);

				if (mb_strpos($hay, $q) === false) {
					continue;
				}
			}

			$rows[] = [
				'key' => $key,
				'en' => $enValue,
				'translation' => $trValue,
				'missing' => $isMissing,
			];
		}

		return [
			'rows' => $rows,
			'total' => count($keys),
			'missing' => $missingCount,
		];
	}

	/** @return array<int, array{code: string, label: string}> */
	public static function getEditorLanguageList(): array
	{
		$list = [];

		foreach (self::getAvailable() as $code) {
			$list[] = [
				'code' => $code,
				'label' => self::label($code),
			];
		}

		return $list;
	}

	/** @return array{success:bool,message:string} */
	private static function ok(string $message): array
	{
		return ['success' => true, 'message' => $message];
	}

	/** @return array{success:bool,message:string} */
	private static function fail(string $message): array
	{
		return ['success' => false, 'message' => $message];
	}
}
