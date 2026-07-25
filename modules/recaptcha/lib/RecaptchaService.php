<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

class RecaptchaService
{
	private const TABLE = 'recaptcha_settings';
	private const ROW_ID = 1;

	private const FORM_HOOKS = [
		'contact' => 'contact_form',
		'login' => 'auth_login',
		'register' => 'auth_register',
		'admin' => 'admin_login',
	];

	public static function ensureSchema(): void
	{
		DB::execute(
			'CREATE TABLE IF NOT EXISTS `' . self::TABLE . '` (
				`id` tinyint unsigned NOT NULL DEFAULT 1,
				`enabled` tinyint(1) NOT NULL DEFAULT 0,
				`version` varchar(8) NOT NULL DEFAULT \'v3\',
				`site_key` varchar(128) NOT NULL DEFAULT \'\',
				`secret_key` varchar(128) NOT NULL DEFAULT \'\',
				`score_threshold` decimal(3,2) NOT NULL DEFAULT 0.50,
				`enable_contact` tinyint(1) NOT NULL DEFAULT 1,
				`enable_login` tinyint(1) NOT NULL DEFAULT 1,
				`enable_register` tinyint(1) NOT NULL DEFAULT 1,
				`enable_admin` tinyint(1) NOT NULL DEFAULT 1,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		);

		if (!DB::getRowSafe(self::TABLE, 'id = ?', [self::ROW_ID])) {
			DB::insert(self::TABLE, [
				'id' => self::ROW_ID,
				'enabled' => 0,
				'version' => 'v3',
			]);
		}
	}

	/** @return array<string, mixed> */
	public static function getSettings(): array
	{
		self::ensureSchema();
		$row = DB::getRowSafe(self::TABLE, 'id = ?', [self::ROW_ID]);

		if (!$row) {
			return self::defaults();
		}

		return self::normalizeRow($row);
	}

	/** @param array<string, mixed> $input */
	public static function saveSettings(array $input): bool
	{
		self::ensureSchema();

		$row = [
			'enabled' => !empty($input['enabled']) ? 1 : 0,
			'version' => self::normalizeVersion((string) ($input['version'] ?? 'v3')),
			'site_key' => trim((string) ($input['site_key'] ?? '')),
			'secret_key' => trim((string) ($input['secret_key'] ?? '')),
			'score_threshold' => self::normalizeScore($input['score_threshold'] ?? 0.5),
			'enable_contact' => !empty($input['enable_contact']) ? 1 : 0,
			'enable_login' => !empty($input['enable_login']) ? 1 : 0,
			'enable_register' => !empty($input['enable_register']) ? 1 : 0,
			'enable_admin' => !empty($input['enable_admin']) ? 1 : 0,
		];

		$exists = DB::getRowSafe(self::TABLE, 'id = ?', [self::ROW_ID]);

		if ($exists) {
			return DB::update(self::TABLE, $row, 'id = :where_id', ['where_id' => self::ROW_ID]) !== false;
		}

		$row['id'] = self::ROW_ID;

		return DB::insert(self::TABLE, $row) !== false;
	}

	public static function isActive(): bool
	{
		$s = self::getSettings();

		return !empty($s['enabled']) && self::isConfigured($s);
	}

	/** @param array<string, mixed>|null $settings */
	public static function isConfigured(?array $settings = null): bool
	{
		$s = $settings ?? self::getSettings();

		return trim((string) ($s['site_key'] ?? '')) !== ''
			&& trim((string) ($s['secret_key'] ?? '')) !== '';
	}

	public static function isEnabledFor(string $form): bool
	{
		if (!self::isActive()) {
			return false;
		}

		$s = self::getSettings();
		$key = 'enable_' . $form;

		if ($form === 'admin') {
			$key = 'enable_admin';
		}

		return !empty($s[$key]);
	}

	public static function getDisplayHookForForm(string $form): ?string
	{
		return self::FORM_HOOKS[$form] ?? null;
	}

	/** @return array<string, mixed> */
	public static function getClientConfig(): array
	{
		$s = self::getSettings();

		return [
			'active' => self::isActive(),
			'version' => (string) ($s['version'] ?? 'v3'),
			'siteKey' => (string) ($s['site_key'] ?? ''),
			'forms' => [
				'contact' => self::isEnabledFor('contact'),
				'login' => self::isEnabledFor('login'),
				'register' => self::isEnabledFor('register'),
				'admin' => self::isEnabledFor('admin'),
			],
		];
	}

	public static function getRequestToken(): string
	{
		$token = Tools::getValue('recaptcha_token');

		if ($token !== '') {
			return trim((string) $token);
		}

		return trim((string) Tools::getValue('g-recaptcha-response'));
	}

	/** @return array{success: bool, message: string, score?: float} */
	public static function verify(string $token, string $form): array
	{
		if (!self::isEnabledFor($form)) {
			return ['success' => true, 'message' => ''];
		}

		if (!self::isConfigured()) {
			return [
				'success' => false,
				'message' => self::translate('Captcha is not configured'),
			];
		}

		if ($token === '') {
			return [
				'success' => false,
				'message' => self::translate('Please complete the captcha verification'),
			];
		}

		$s = self::getSettings();
		$payload = http_build_query([
			'secret' => (string) $s['secret_key'],
			'response' => $token,
			'remoteip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
		]);

		$context = stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
				'content' => $payload,
				'timeout' => 8,
			],
		]);

		$raw = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
		$data = is_string($raw) ? json_decode($raw, true) : null;

		if (!is_array($data) || empty($data['success'])) {
			return [
				'success' => false,
				'message' => self::translate('Captcha verification failed'),
			];
		}

		if (($s['version'] ?? 'v3') === 'v3') {
			$score = isset($data['score']) ? (float) $data['score'] : 0.0;
			$threshold = (float) ($s['score_threshold'] ?? 0.5);

			if ($score < $threshold) {
				return [
					'success' => false,
					'message' => self::translate('Captcha score too low, please try again'),
					'score' => $score,
				];
			}
		}

		return ['success' => true, 'message' => ''];
	}

	public static function validateForm(string $form, &$error): void
	{
		$result = self::verify(self::getRequestToken(), $form);

		if (!$result['success']) {
			$error = $result['message'];
		}
	}

	private static function normalizeVersion(string $version): string
	{
		return $version === 'v2' ? 'v2' : 'v3';
	}

	private static function normalizeScore($score): string
	{
		$value = max(0.1, min(0.9, (float) $score));

		return number_format($value, 2, '.', '');
	}

	/** @return array<string, mixed> */
	private static function defaults(): array
	{
		return [
			'enabled' => 0,
			'version' => 'v3',
			'site_key' => '',
			'secret_key' => '',
			'score_threshold' => '0.50',
			'enable_contact' => 1,
			'enable_login' => 1,
			'enable_register' => 1,
			'enable_admin' => 1,
		];
	}

	/** @param array<string, mixed> $row */
	private static function normalizeRow(array $row): array
	{
		return [
			'enabled' => (int) ($row['enabled'] ?? 0),
			'version' => self::normalizeVersion((string) ($row['version'] ?? 'v3')),
			'site_key' => (string) ($row['site_key'] ?? ''),
			'secret_key' => (string) ($row['secret_key'] ?? ''),
			'score_threshold' => (string) ($row['score_threshold'] ?? '0.50'),
			'enable_contact' => (int) ($row['enable_contact'] ?? 1),
			'enable_login' => (int) ($row['enable_login'] ?? 1),
			'enable_register' => (int) ($row['enable_register'] ?? 1),
			'enable_admin' => (int) ($row['enable_admin'] ?? 1),
		];
	}

	private static function translate(string $message): string
	{
		return function_exists('translate') ? translate($message) : $message;
	}
}
