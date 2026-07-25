<?php



if (!defined('IN_SCRIPT') && !defined('IN_ADMIN') && !defined('IN_PWA')) {

	exit;

}



require_once __DIR__ . '/PushSubscriptionService.php';

require_once __DIR__ . '/WebPushNative.php';



class WebPushService

{

	public static function isLibraryReady(): bool

	{

		return is_file(dirname(__DIR__) . '/vendor/autoload.php');

	}



	public static function loadLibrary(): bool

	{

		$autoload = dirname(__DIR__) . '/vendor/autoload.php';



		if (!is_file($autoload)) {

			return false;

		}



		require_once $autoload;



		return true;

	}



	public static function isConfigured(): bool

	{

		if (!MobilAppService::isPushEnabled()) {

			return false;

		}



		$keys = MobilAppService::getVapidKeys();



		return $keys['public'] !== '' && $keys['private'] !== '';

	}



	public static function isAvailable(): bool

	{

		if (!self::isConfigured()) {

			return false;

		}



		if (self::isLibraryReady()) {

			return function_exists('curl_init');

		}



		return function_exists('openssl_pkey_new') && function_exists('curl_init');

	}



	/** @return array{public: string, private: string} */

	public static function generateVapidKeys(): array

	{

		if (self::loadLibrary()) {

			$keys = \Minishlink\WebPush\VAPID::createVapidKeys();



			return [

				'public' => (string) ($keys['publicKey'] ?? ''),

				'private' => (string) ($keys['privateKey'] ?? ''),

			];

		}



		$keys = WebPushNative::createVapidKeys();



		return [

			'public' => (string) ($keys['publicKey'] ?? ''),

			'private' => (string) ($keys['privateKey'] ?? ''),

		];

	}



	public static function sendToUser(int $idUser, string $title, string $body, string $url = ''): int

	{

		if ($idUser <= 0 || !self::isAvailable()) {

			return 0;

		}



		$subscriptions = PushSubscriptionService::getForUser($idUser);



		if ($subscriptions === []) {

			return 0;

		}



		$sent = 0;



		foreach ($subscriptions as $row) {

			if (self::sendToRow($row, $title, $body, $url)) {

				$sent++;

			}

		}



		return $sent;

	}



	/**

	 * @param array<string, mixed> $row

	 */

	private static function sendToRow(array $row, string $title, string $body, string $url): bool

	{

		$keys = MobilAppService::getVapidKeys();

		$subject = MobilAppService::getVapidSubject();

		$icon = MobilAppService::resolvePushIconUrl();



		$payload = json_encode([

			'title' => mb_substr(trim($title), 0, 120),

			'body' => mb_substr(trim(strip_tags($body)), 0, 240),

			'url' => self::absoluteUrl($url),

			'icon' => $icon,

			'tag' => 'fshop-' . time(),

		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);



		if (!is_string($payload)) {

			return false;

		}



		if (self::loadLibrary()) {

			return self::sendWithMinishlink($row, $payload, $keys, $subject);

		}



		return self::sendWithNative($row, $payload, $keys, $subject);

	}



	/**

	 * @param array<string, mixed> $row

	 * @param array{public: string, private: string, subject?: string} $keys

	 */

	private static function sendWithMinishlink(array $row, string $payload, array $keys, string $subject): bool

	{

		try {

			$webPush = new \Minishlink\WebPush\WebPush([

				'VAPID' => [

					'subject' => $subject,

					'publicKey' => $keys['public'],

					'privateKey' => $keys['private'],

				],

			]);



			$subscription = \Minishlink\WebPush\Subscription::create([

				'endpoint' => (string) ($row['endpoint'] ?? ''),

				'keys' => [

					'p256dh' => (string) ($row['p256dh'] ?? ''),

					'auth' => (string) ($row['auth'] ?? ''),

				],

				'contentEncoding' => 'aes128gcm',

			]);



			$report = $webPush->sendOneNotification($subscription, $payload);



			if ($report->isSuccess()) {

				return true;

			}



			$reason = (string) $report->getReason();

			$response = $report->getResponse();

			$status = $response !== null ? (int) $response->getStatusCode() : 0;



			self::handleFailure($row, $status, $reason);



			return false;

		} catch (Throwable $e) {

			error_log('WebPushService::sendWithMinishlink: ' . $e->getMessage());



			return false;

		}

	}



	/**

	 * @param array<string, mixed> $row

	 * @param array{public: string, private: string} $keys

	 */

	private static function sendWithNative(array $row, string $payload, array $keys, string $subject): bool

	{

		try {

			$result = WebPushNative::send(

				(string) ($row['endpoint'] ?? ''),

				(string) ($row['p256dh'] ?? ''),

				(string) ($row['auth'] ?? ''),

				$payload,

				$subject,

				$keys['public'],

				$keys['private']

			);



			if (!empty($result['success'])) {

				return true;

			}



			self::handleFailure($row, (int) ($result['status'] ?? 0), (string) ($result['reason'] ?? ''));



			return false;

		} catch (Throwable $e) {

			error_log('WebPushService::sendWithNative: ' . $e->getMessage());



			return false;

		}

	}



	/** @param array<string, mixed> $row */

	private static function handleFailure(array $row, int $status, string $reason): void
	{
		$isStale = $status === 404 || $status === 410
			|| stripos($reason, '410') !== false
			|| stripos($reason, 'expired') !== false
			|| stripos($reason, 'unsubscribed') !== false;

		if ($isStale) {
			$id = (int) ($row['id_subscription'] ?? 0);
			$removed = PushSubscriptionService::removeById($id);

			if (!$removed) {
				$endpoint = trim((string) ($row['endpoint'] ?? ''));
				if ($endpoint !== '') {
					PushSubscriptionService::removeByEndpoint($endpoint);
				}
			}

			error_log('WebPushService: stale push subscription removed (HTTP ' . $status . ')');

			return;
		}

		if ($reason !== '') {
			error_log('WebPushService: HTTP ' . $status . ' — ' . $reason);
		}
	}



	public static function absoluteUrl(string $url): string

	{

		$url = trim($url);



		if ($url === '') {

			return rtrim(MobilAppService::getDomain(), '/') . MobilAppService::getScopePath();

		}



		if (preg_match('#^https?://#i', $url)) {

			return $url;

		}



		return rtrim(MobilAppService::getDomain(), '/') . '/' . ltrim($url, '/');

	}



	/** @return array{sent: int, failed: int, success: bool, message: string, details: list<string>} */

	public static function sendTestBroadcast(string $title = 'Test bildirimi', string $body = ''): array

	{

		if (!self::isAvailable()) {

			return [

				'sent' => 0,

				'failed' => 0,

				'success' => false,

				'message' => 'Push yapılandırılmamış veya kütüphane eksik (vendor klasörü)',

				'details' => [],

			];

		}



		$rows = DB::execute('SELECT * FROM mobil_app_push_subscriptions ORDER BY id_subscription DESC LIMIT 50') ?: [];



		if ($rows === []) {

			return [

				'sent' => 0,

				'failed' => 0,

				'success' => false,

				'message' => 'Kayıtlı cihaz aboneliği yok',

				'details' => [],

			];

		}



		if ($body === '') {

			$siteName = trim((string) Settings::get('SITE_NAME')) ?: 'Mağaza';

			$body = $siteName . ' push bildirimleri çalışıyor.';

		}



		$sent = 0;

		$failed = 0;

		$details = [];



		foreach ($rows as $row) {

			$idUser = (int) ($row['id_user'] ?? 0);

			$label = '#' . (int) ($row['id_subscription'] ?? 0) . ' (user ' . $idUser . ')';



			if (self::sendToRow($row, $title, $body, MobilAppService::getScopePath())) {

				$sent++;

				$details[] = $label . ': OK';

			} else {

				$failed++;

				$details[] = $label . ': başarısız (son hata sunucu logunda)';

			}

		}



		return [

			'sent' => $sent,

			'failed' => $failed,

			'success' => $sent > 0,

			'message' => $sent . ' cihaza gönderildi' . ($failed > 0 ? (', ' . $failed . ' başarısız') : ''),

			'details' => $details,

		];

	}

}

