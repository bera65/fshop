<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

class GoogleLoginModule extends ModuleBase
{
	public string $name = 'google-login';
	public string $title = 'Google Login';
	public string $version = '1.0.0';
	public string $description = 'Google hesabı ile giriş ve kayıt';
	public string $author = 'FShop';

	public array $displayHooks = [
		'auth_social' => 'Giriş / kayıt formlarına Google butonu',
	];

	public array $defaultDisplayHooks = ['auth_social'];

	public array $routes = [
		'google-login-callback' => 'front/callback.php',
	];

	public array $apiActions = [
		'start' => 'api/start.php',
	];

	public array $frontStylesheets = ['google-login.css'];

	public function install(): bool
	{
		return $this->runSqlFile('install.sql');
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken, $domain;

		$flash = '';
		$flashType = 'success';

		if (Tools::isSubmit('saveGoogleLogin')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} else {
				Settings::set('GOOGLE_CLIENT_ID', trim((string) Tools::getValue('client_id')));
				Settings::set('GOOGLE_CLIENT_SECRET', trim((string) Tools::getValue('client_secret')));
				$flash = 'Google Login ayarları kaydedildi';
			}
		}

		$smarty->assign([
			'googleClientId' => Settings::get('GOOGLE_CLIENT_ID'),
			'googleClientSecret' => Settings::get('GOOGLE_CLIENT_SECRET'),
			'googleRedirectUri' => self::redirectUri(),
			'googleConfigured' => self::isConfigured(),
			'flash' => $flash,
			'flashType' => $flashType,
		]);
	}

	public function boot(): void
	{
		if (!defined('IN_SCRIPT') || defined('IN_ADMIN')) {
			return;
		}

		global $smarty, $domain;

		if (!$smarty || !Module::isEnabled($this->name)) {
			return;
		}

		$assigned = Module::getAssignedDisplayHooks($this->name);

		if (!in_array('auth_social', $assigned, true)) {
			Module::setDisplayHooks($this->name, ['auth_social']);
		}

		$smarty->assign([
			'googleLoginUrl' => rtrim($domain, '/') . '/api/module.php?m=google-login&action=start',
			'googleLoginConfigured' => self::isConfigured(),
		]);
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'auth_social' || !Module::isEnabled($this->name)) {
			return null;
		}

		global $domain;

		$html = $this->renderFrontTemplate('auth_social', [
			'googleStartUrl' => rtrim($domain, '/') . '/api/module.php?m=google-login&action=start',
			'googleOrContinue' => translate('or continue with'),
			'googleButtonLabel' => translate('Sign in with Google'),
		]);

		return $html !== '' ? $html : null;
	}

	public static function isConfigured(): bool
	{
		return trim((string) Settings::get('GOOGLE_CLIENT_ID')) !== ''
			&& trim((string) Settings::get('GOOGLE_CLIENT_SECRET')) !== '';
	}

	public static function redirectUri(): string
	{
		return rtrim(Settings::get('DOMAIN') ?: '', '/') . '/google-login-callback';
	}

	public static function buildAuthUrl(string $state): string
	{
		$params = [
			'client_id' => trim((string) Settings::get('GOOGLE_CLIENT_ID')),
			'redirect_uri' => self::redirectUri(),
			'response_type' => 'code',
			'scope' => 'openid email profile',
			'state' => $state,
			'access_type' => 'online',
			'prompt' => 'select_account',
		];

		return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
	}

	/** @return array{success: bool, google_id?: string, email?: string, name?: string, message?: string} */
	public static function exchangeCode(string $code): array
	{
		if (!self::isConfigured()) {
			return ['success' => false, 'message' => 'Google Login yapılandırılmamış'];
		}

		$post = [
			'code' => $code,
			'client_id' => trim((string) Settings::get('GOOGLE_CLIENT_ID')),
			'client_secret' => trim((string) Settings::get('GOOGLE_CLIENT_SECRET')),
			'redirect_uri' => self::redirectUri(),
			'grant_type' => 'authorization_code',
		];

		$tokenResponse = self::httpPost('https://oauth2.googleapis.com/token', $post);

		if ($tokenResponse === null) {
			return ['success' => false, 'message' => 'Google bağlantı hatası'];
		}

		$tokenData = json_decode($tokenResponse, true);

		if (!is_array($tokenData) || empty($tokenData['access_token'])) {
			return ['success' => false, 'message' => 'Google token alınamadı'];
		}

		$userResponse = self::httpGet(
			'https://www.googleapis.com/oauth2/v3/userinfo',
			(string) $tokenData['access_token']
		);

		if ($userResponse === null) {
			return ['success' => false, 'message' => 'Google kullanıcı bilgisi alınamadı'];
		}

		$userData = json_decode($userResponse, true);

		if (!is_array($userData) || empty($userData['sub'])) {
			return ['success' => false, 'message' => 'Geçersiz Google yanıtı'];
		}

		$verified = $userData['email_verified'] ?? false;

		if ($verified !== true && $verified !== 'true' && $verified !== 1 && $verified !== '1') {
			return ['success' => false, 'message' => 'Google e-posta doğrulanmamış'];
		}

		return [
			'success' => true,
			'google_id' => (string) $userData['sub'],
			'email' => (string) ($userData['email'] ?? ''),
			'name' => (string) ($userData['name'] ?? ''),
			'email_verified' => true,
		];
	}

	/** @param array<string, scalar> $fields */
	private static function httpPost(string $url, array $fields): ?string
	{
		if (!function_exists('curl_init')) {
			return null;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		$result = curl_exec($ch);
		curl_close($ch);

		return is_string($result) ? $result : null;
	}

	private static function httpGet(string $url, string $accessToken): ?string
	{
		if (!function_exists('curl_init')) {
			return null;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		$result = curl_exec($ch);
		curl_close($ch);

		return is_string($result) ? $result : null;
	}
}
