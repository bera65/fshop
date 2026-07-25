<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

/**
 * Tarayıcı bildirimleri:
 * - OneSignal Web Push (tarayıcı kapalıyken de çalışır)
 * - Yerel kuyruk + poll (OneSignal yoksa yedek)
 */
class CustomerNotifyPush
{
	public const SETTING_ENABLED = 'CUSTOMER_NOTIFY_BROWSER';
	public const SETTING_MODE = 'CUSTOMER_NOTIFY_BROWSER_MODE';
	public const SETTING_WEBHOOK = 'CUSTOMER_NOTIFY_BROWSER_WEBHOOK';
	public const SETTING_OS_APP_ID = 'CUSTOMER_NOTIFY_ONESIGNAL_APP_ID';
	public const SETTING_OS_SAFARI = 'CUSTOMER_NOTIFY_ONESIGNAL_SAFARI_WEB_ID';
	public const SETTING_OS_REST_KEY = 'CUSTOMER_NOTIFY_ONESIGNAL_REST_API_KEY';
	public const SETTING_OS_LAST_ERROR = 'CUSTOMER_NOTIFY_ONESIGNAL_LAST_ERROR';
	public const SETTING_OS_LAST_OK = 'CUSTOMER_NOTIFY_ONESIGNAL_LAST_OK';

	/** shipped | all_status | broadcast_only */
	public const MODE_SHIPPED = 'shipped';
	public const MODE_ALL_STATUS = 'all_status';
	public const MODE_BROADCAST = 'broadcast_only';

	public static function ensureSchema(): void
	{
		DB::execute(
			'CREATE TABLE IF NOT EXISTS `customer_notify_push_queue` (
				`id_queue` int(11) NOT NULL AUTO_INCREMENT,
				`id_user` int(11) NOT NULL,
				`title` varchar(255) NOT NULL DEFAULT \'\',
				`body` text NOT NULL,
				`url` varchar(512) NOT NULL DEFAULT \'\',
				`type` varchar(64) NOT NULL DEFAULT \'\',
				`delivered` tinyint(1) NOT NULL DEFAULT 0,
				`date_add` datetime NOT NULL DEFAULT current_timestamp(),
				`date_delivered` datetime DEFAULT NULL,
				PRIMARY KEY (`id_queue`),
				KEY `idx_user_delivered` (`id_user`, `delivered`, `id_queue`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		);

		DB::execute(
			'CREATE TABLE IF NOT EXISTS `customer_notify_push_devices` (
				`id_device` int(11) NOT NULL AUTO_INCREMENT,
				`id_user` int(11) NOT NULL,
				`device_key` varchar(64) NOT NULL DEFAULT \'\',
				`endpoint` varchar(512) NOT NULL DEFAULT \'\',
				`p256dh` varchar(255) NOT NULL DEFAULT \'\',
				`auth` varchar(255) NOT NULL DEFAULT \'\',
				`user_agent` varchar(255) NOT NULL DEFAULT \'\',
				`enabled` tinyint(1) NOT NULL DEFAULT 1,
				`date_add` datetime NOT NULL DEFAULT current_timestamp(),
				`date_upd` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
				PRIMARY KEY (`id_device`),
				UNIQUE KEY `uniq_user_device` (`id_user`, `device_key`),
				KEY `idx_user_enabled` (`id_user`, `enabled`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		);

		$defaults = [
			self::SETTING_ENABLED => '1',
			self::SETTING_MODE => self::MODE_SHIPPED,
			self::SETTING_WEBHOOK => '',
			self::SETTING_OS_APP_ID => '14286a83-29d0-47c5-8f0b-480cae25945e',
			self::SETTING_OS_SAFARI => 'web.onesignal.auto.1f13959d-363e-4480-ae37-ce4b59dbb72b',
			self::SETTING_OS_REST_KEY => '',
		];

		foreach ($defaults as $key => $value) {
			if (Settings::get($key) === '') {
				Settings::set($key, $value);
			}
		}
	}

	public static function isEnabled(): bool
	{
		return Settings::get(self::SETTING_ENABLED) === '1';
	}

	public static function getMode(): string
	{
		$mode = (string) Settings::get(self::SETTING_MODE);

		if (in_array($mode, [self::MODE_SHIPPED, self::MODE_ALL_STATUS, self::MODE_BROADCAST], true)) {
			return $mode;
		}

		return self::MODE_SHIPPED;
	}

	public static function getOneSignalAppId(): string
	{
		return trim((string) Settings::get(self::SETTING_OS_APP_ID));
	}

	public static function getOneSignalSafariWebId(): string
	{
		return trim((string) Settings::get(self::SETTING_OS_SAFARI));
	}

	public static function getOneSignalRestKey(): string
	{
		return trim((string) Settings::get(self::SETTING_OS_REST_KEY));
	}

	public static function isOneSignalConfigured(): bool
	{
		return self::getOneSignalAppId() !== '' && self::getOneSignalRestKey() !== '';
	}

	public static function isOneSignalClientReady(): bool
	{
		return self::getOneSignalAppId() !== '';
	}

	/** @param array{type?:string,new_status?:int,old_status?:int} $meta */
	public static function shouldDispatch(string $type, array $meta = []): bool
	{
		if (!self::isEnabled()) {
			return false;
		}

		$mode = self::getMode();

		if ($type === 'admin_broadcast') {
			return true;
		}

		if ($type === 'order_status') {
			if ($mode === self::MODE_BROADCAST) {
				return false;
			}

			if ($mode === self::MODE_ALL_STATUS) {
				return true;
			}

			return (int) ($meta['new_status'] ?? 0) === Order::STATUS_SHIPPED;
		}

		return false;
	}

	/**
	 * @param array{type?:string,new_status?:int,old_status?:int} $meta
	 */
	public static function dispatch(int $idUser, string $title, string $message, string $link = '', array $meta = []): void
	{
		self::ensureSchema();

		if ($idUser <= 0) {
			return;
		}

		$type = (string) ($meta['type'] ?? '');

		if (!self::shouldDispatch($type, $meta)) {
			return;
		}

		$title = mb_substr(trim($title), 0, 255);
		$body = trim($message);
		$url = self::absoluteUrl($link);

		if ($title === '') {
			$title = 'Bildirim';
		}

		// Yerel yedek kuyruk (sitedeyken poll)
		DB::insert('customer_notify_push_queue', [
			'id_user' => $idUser,
			'title' => $title,
			'body' => $body,
			'url' => mb_substr($url, 0, 512),
			'type' => mb_substr($type, 0, 64),
			'delivered' => 0,
		]);

		// Asıl kanal: OneSignal (kapalı tarayıcıda da çalışır)
		if (self::isOneSignalConfigured()) {
			self::sendOneSignal($idUser, $title, $body, $url);
		}

		self::callWebhook([
			'event' => 'customer_browser_notify',
			'id_user' => $idUser,
			'title' => $title,
			'body' => $body,
			'url' => $url,
			'type' => $type,
			'meta' => $meta,
		]);
	}

	/**
	 * @return array{ok:bool,http:int,id?:string,recipients?:int,error?:string,raw?:string}
	 */
	public static function sendOneSignal(int $idUser, string $title, string $body, string $url): array
	{
		$appId = self::getOneSignalAppId();
		$restKey = self::getOneSignalRestKey();

		if ($appId === '' || $restKey === '' || $idUser <= 0) {
			$result = [
				'ok' => false,
				'http' => 0,
				'error' => 'OneSignal App ID veya REST API Key eksik',
			];
			self::storeOneSignalResult($result);

			return $result;
		}

		if (!function_exists('curl_init')) {
			$result = [
				'ok' => false,
				'http' => 0,
				'error' => 'PHP curl eklentisi yok',
			];
			self::storeOneSignalResult($result);

			return $result;
		}

		$icon = self::iconUrl();
		$payload = [
			'app_id' => $appId,
			'target_channel' => 'push',
			'include_aliases' => [
				'external_id' => [(string) $idUser],
			],
			'headings' => [
				'en' => $title,
				'tr' => $title,
			],
			'contents' => [
				'en' => $body,
				'tr' => $body,
			],
			'url' => $url,
			'chrome_web_icon' => $icon,
			'firefox_icon' => $icon,
			'chrome_web_badge' => $icon,
		];

		$result = self::postOneSignalNotification($restKey, $payload);

		// Eski API yedeği (bazı uygulamalarda external_user_ids hâlâ geçerli)
		if (!$result['ok'] || (int) ($result['recipients'] ?? 0) === 0) {
			$legacy = $payload;
			unset($legacy['include_aliases'], $legacy['target_channel']);
			$legacy['include_external_user_ids'] = [(string) $idUser];
			$legacyResult = self::postOneSignalNotification($restKey, $legacy);

			if ($legacyResult['ok'] && (int) ($legacyResult['recipients'] ?? 0) > 0) {
				$result = $legacyResult;
			} elseif (!$result['ok'] && $legacyResult['ok']) {
				$result = $legacyResult;
			} elseif (!$result['ok'] && !empty($legacyResult['error'])) {
				$result['error'] = trim(($result['error'] ?? '') . ' | legacy: ' . $legacyResult['error']);
			}
		}

		if ($result['ok'] && (int) ($result['recipients'] ?? 0) === 0) {
			$result['ok'] = false;
			$result['error'] = 'OneSignal kabul etti ama alıcı 0. Müşteri sitede “Evet” demiş ve OneSignal.login(userId) bağlı mı? '
				. 'OneSignal panelinde Audience → Users içinde external_id=' . $idUser . ' arayın.';
		}

		self::storeOneSignalResult($result);

		return $result;
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array{ok:bool,http:int,id?:string,recipients?:int,error?:string,raw?:string}
	 */
	private static function postOneSignalNotification(string $restKey, array $payload): array
	{
		$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if ($json === false) {
			return ['ok' => false, 'http' => 0, 'error' => 'JSON encode failed'];
		}

		$ch = curl_init('https://api.onesignal.com/notifications');
		curl_setopt_array($ch, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $json,
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json; charset=utf-8',
				'Authorization: Key ' . $restKey,
			],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 15,
		]);
		$response = curl_exec($ch);
		$curlErr = curl_error($ch);
		$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($response === false) {
			return [
				'ok' => false,
				'http' => $code,
				'error' => $curlErr !== '' ? $curlErr : 'curl failed',
			];
		}

		$data = json_decode($response, true);

		if (!is_array($data)) {
			return [
				'ok' => false,
				'http' => $code,
				'error' => 'Geçersiz API yanıtı',
				'raw' => mb_substr($response, 0, 500),
			];
		}

		$errors = $data['errors'] ?? null;
		$errorText = self::flattenOneSignalErrors($errors);

		$id = isset($data['id']) ? (string) $data['id'] : '';
		$recipients = isset($data['recipients']) ? (int) $data['recipients'] : null;
		$ok = $code >= 200 && $code < 300 && $id !== '' && $errorText === '';

		return [
			'ok' => $ok,
			'http' => $code,
			'id' => $id,
			'recipients' => $recipients,
			'error' => $errorText !== '' ? $errorText : ($ok ? '' : ('HTTP ' . $code)),
			'raw' => mb_substr($response, 0, 500),
		];
	}

	/** @param mixed $errors */
	private static function flattenOneSignalErrors($errors): string
	{
		if ($errors === null || $errors === '') {
			return '';
		}

		if (is_string($errors)) {
			return $errors;
		}

		if (!is_array($errors)) {
			return (string) $errors;
		}

		$parts = [];

		foreach ($errors as $key => $value) {
			$prefix = is_string($key) ? ($key . ': ') : '';

			if (is_array($value)) {
				$nested = self::flattenOneSignalErrors($value);

				if ($nested !== '') {
					$parts[] = $prefix . $nested;
				}
			} else {
				$parts[] = $prefix . (string) $value;
			}
		}

		return implode('; ', $parts);
	}

	/** @param array{ok:bool,http?:int,id?:string,recipients?:int,error?:string} $result */
	private static function storeOneSignalResult(array $result): void
	{
		if (!empty($result['ok'])) {
			Settings::set(self::SETTING_OS_LAST_ERROR, '');
			Settings::set(
				self::SETTING_OS_LAST_OK,
				date('Y-m-d H:i:s')
				. ' id=' . ($result['id'] ?? '')
				. ' recipients=' . (string) ($result['recipients'] ?? '?')
			);

			return;
		}

		$msg = trim((string) ($result['error'] ?? 'Bilinmeyen hata'));

		if ($msg === '') {
			$msg = 'HTTP ' . (int) ($result['http'] ?? 0);
		}

		Settings::set(self::SETTING_OS_LAST_ERROR, date('Y-m-d H:i:s') . ' — ' . mb_substr($msg, 0, 900));
	}

	public static function getLastOneSignalError(): string
	{
		return trim((string) Settings::get(self::SETTING_OS_LAST_ERROR));
	}

	public static function getLastOneSignalOk(): string
	{
		return trim((string) Settings::get(self::SETTING_OS_LAST_OK));
	}

	/**
	 * @return list<array{id:int,title:string,body:string,url:string,type:string}>
	 */
	public static function claimPending(int $idUser, int $limit = 10): array
	{
		self::ensureSchema();

		if ($idUser <= 0) {
			return [];
		}

		$limit = max(1, min(20, $limit));
		$rows = DB::execute(
			'SELECT id_queue, title, body, url, type
			 FROM customer_notify_push_queue
			 WHERE id_user = ? AND delivered = 0
			 ORDER BY id_queue ASC
			 LIMIT ' . $limit,
			[$idUser]
		) ?: [];

		$out = [];

		foreach ($rows as $row) {
			$id = (int) ($row['id_queue'] ?? 0);

			if ($id <= 0) {
				continue;
			}

			DB::update(
				'customer_notify_push_queue',
				[
					'delivered' => 1,
					'date_delivered' => date('Y-m-d H:i:s'),
				],
				'id_queue = :id_queue AND id_user = :id_user AND delivered = 0',
				['id_queue' => $id, 'id_user' => $idUser]
			);

			$out[] = [
				'id' => $id,
				'title' => (string) ($row['title'] ?? ''),
				'body' => (string) ($row['body'] ?? ''),
				'url' => (string) ($row['url'] ?? ''),
				'type' => (string) ($row['type'] ?? ''),
			];
		}

		return $out;
	}

	public static function saveDevice(int $idUser, string $deviceKey, array $subscription = []): bool
	{
		self::ensureSchema();

		$deviceKey = preg_replace('/[^a-zA-Z0-9_-]/', '', $deviceKey) ?? '';
		$deviceKey = mb_substr($deviceKey, 0, 64);

		if ($idUser <= 0 || $deviceKey === '') {
			return false;
		}

		$endpoint = mb_substr(trim((string) ($subscription['endpoint'] ?? '')), 0, 512);
		$p256dh = mb_substr(trim((string) ($subscription['keys']['p256dh'] ?? $subscription['p256dh'] ?? '')), 0, 255);
		$auth = mb_substr(trim((string) ($subscription['keys']['auth'] ?? $subscription['auth'] ?? '')), 0, 255);
		$ua = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

		$existing = (int) DB::getValue(
			'SELECT id_device FROM customer_notify_push_devices WHERE id_user = ? AND device_key = ? LIMIT 1',
			[$idUser, $deviceKey]
		);

		if ($existing > 0) {
			DB::update(
				'customer_notify_push_devices',
				[
					'endpoint' => $endpoint,
					'p256dh' => $p256dh,
					'auth' => $auth,
					'user_agent' => $ua,
					'enabled' => 1,
				],
				'id_device = :id_device',
				['id_device' => $existing]
			);

			return true;
		}

		$id = DB::insert('customer_notify_push_devices', [
			'id_user' => $idUser,
			'device_key' => $deviceKey,
			'endpoint' => $endpoint,
			'p256dh' => $p256dh,
			'auth' => $auth,
			'user_agent' => $ua,
			'enabled' => 1,
		]);

		return $id > 0;
	}

	public static function disableDevice(int $idUser, string $deviceKey): void
	{
		$deviceKey = preg_replace('/[^a-zA-Z0-9_-]/', '', $deviceKey) ?? '';

		if ($idUser <= 0 || $deviceKey === '') {
			return;
		}

		DB::update(
			'customer_notify_push_devices',
			['enabled' => 0],
			'id_user = :id_user AND device_key = :device_key',
			['id_user' => $idUser, 'device_key' => $deviceKey]
		);
	}

	public static function countDevices(): int
	{
		self::ensureSchema();

		return (int) DB::getValue('SELECT COUNT(*) FROM customer_notify_push_devices WHERE enabled = 1');
	}

	/** Site köküne göre OneSignal worker yolu (leading slash yok). */
	public static function oneSignalWorkerPath(): string
	{
		global $domain;
		$path = (string) (parse_url(rtrim((string) $domain, '/') . '/', PHP_URL_PATH) ?: '/');
		$path = trim($path, '/');

		if ($path === '') {
			return 'onesignal/OneSignalSDKWorker.js';
		}

		return $path . '/onesignal/OneSignalSDKWorker.js';
	}

	public static function oneSignalWorkerScope(): string
	{
		global $domain;
		$path = (string) (parse_url(rtrim((string) $domain, '/') . '/', PHP_URL_PATH) ?: '/');
		$base = rtrim($path, '/');

		if ($base === '' || $base === '/') {
			return '/onesignal/';
		}

		return $base . '/onesignal/';
	}

	private static function iconUrl(): string
	{
		global $domain;
		$base = rtrim((string) $domain, '/');
		$file = dirname(__DIR__, 2) . '/img/favicon.ico';
		$url = $base . '/img/favicon.ico';

		if (is_file($file)) {
			$url .= '?v=' . filemtime($file);
		}

		return $url;
	}

	/** @param array<string, mixed> $payload */
	private static function callWebhook(array $payload): void
	{
		$url = trim((string) Settings::get(self::SETTING_WEBHOOK));

		if ($url === '' || !preg_match('#^https?://#i', $url)) {
			return;
		}

		$json = json_encode($payload, JSON_UNESCAPED_UNICODE);

		if ($json === false) {
			return;
		}

		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt_array($ch, [
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $json,
				CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CONNECTTIMEOUT => 3,
				CURLOPT_TIMEOUT => 6,
			]);
			curl_exec($ch);
			curl_close($ch);
		}
	}

	private static function absoluteUrl(string $link): string
	{
		$link = trim($link);

		if ($link === '') {
			global $domain;

			return rtrim((string) $domain, '/') . '/my-account#notifications';
		}

		if (preg_match('#^https?://#i', $link)) {
			return $link;
		}

		global $domain;

		return rtrim((string) $domain, '/') . '/' . ltrim($link, '/');
	}
}
