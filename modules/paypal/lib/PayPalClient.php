<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

/**
 * PayPal Orders API v2 istemcisi (OAuth + create + capture).
 */
class PayPalClient
{
	public const SANDBOX_BASE = 'https://api-m.sandbox.paypal.com';
	public const LIVE_BASE = 'https://api-m.paypal.com';

	private string $clientId;
	private string $clientSecret;
	private bool $sandbox;
	/** @var string|null */
	private $accessToken;
	/** @var int */
	private $tokenExpiresAt = 0;

	public function __construct(string $clientId, string $clientSecret, bool $sandbox)
	{
		$this->clientId = trim($clientId);
		$this->clientSecret = trim($clientSecret);
		$this->sandbox = $sandbox;
	}

	public function baseUrl(): string
	{
		return $this->sandbox ? self::SANDBOX_BASE : self::LIVE_BASE;
	}

	/**
	 * @return array{ok:bool,token?:string,error?:string}
	 */
	public function getAccessToken(): array
	{
		if ($this->accessToken !== null && time() < $this->tokenExpiresAt - 30) {
			return ['ok' => true, 'token' => $this->accessToken];
		}

		if (!function_exists('curl_init')) {
			return ['ok' => false, 'error' => 'PHP curl eklentisi yok'];
		}

		$ch = curl_init(rtrim($this->baseUrl(), '/') . '/v1/oauth2/token');
		curl_setopt_array($ch, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
			CURLOPT_USERPWD => $this->clientId . ':' . $this->clientSecret,
			CURLOPT_HTTPHEADER => [
				'Accept: application/json',
				'Accept-Language: en_US',
			],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 8,
			CURLOPT_TIMEOUT => 30,
		]);
		$response = curl_exec($ch);
		$curlErr = curl_error($ch);
		$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($response === false) {
			return ['ok' => false, 'error' => $curlErr !== '' ? $curlErr : 'curl failed'];
		}

		$data = json_decode((string) $response, true);

		if (!is_array($data) || empty($data['access_token'])) {
			$msg = is_array($data) ? (string) ($data['error_description'] ?? $data['error'] ?? '') : '';

			return [
				'ok' => false,
				'error' => $msg !== '' ? $msg : ('OAuth HTTP ' . $code),
			];
		}

		$this->accessToken = (string) $data['access_token'];
		$this->tokenExpiresAt = time() + (int) ($data['expires_in'] ?? 300);

		return ['ok' => true, 'token' => $this->accessToken];
	}

	/**
	 * @param array<string, mixed> $body
	 * @param array<string, string> $extraHeaders
	 * @return array{ok:bool,http:int,data?:array,error?:string,raw?:string}
	 */
	public function api(string $method, string $path, ?array $body = null, array $extraHeaders = []): array
	{
		$auth = $this->getAccessToken();

		if (!$auth['ok']) {
			return ['ok' => false, 'http' => 0, 'error' => $auth['error'] ?? 'token failed'];
		}

		if (!function_exists('curl_init')) {
			return ['ok' => false, 'http' => 0, 'error' => 'PHP curl eklentisi yok'];
		}

		$url = rtrim($this->baseUrl(), '/') . '/' . ltrim($path, '/');
		$headers = array_merge([
			'Content-Type: application/json',
			'Accept: application/json',
			'Authorization: Bearer ' . $auth['token'],
		], $extraHeaders);

		$ch = curl_init($url);
		$opts = [
			CURLOPT_CUSTOMREQUEST => strtoupper($method),
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 8,
			CURLOPT_TIMEOUT => 45,
		];

		if ($body !== null) {
			if ($body instanceof \stdClass) {
				$json = '{}';
			} else {
				$json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}

			if ($json === false) {
				return ['ok' => false, 'http' => 0, 'error' => 'JSON encode failed'];
			}

			$opts[CURLOPT_POSTFIELDS] = $json;
		} elseif (strtoupper($method) === 'POST') {
			$opts[CURLOPT_POSTFIELDS] = '{}';
		}

		curl_setopt_array($ch, $opts);
		$response = curl_exec($ch);
		$curlErr = curl_error($ch);
		$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($response === false) {
			return ['ok' => false, 'http' => $code, 'error' => $curlErr !== '' ? $curlErr : 'curl failed'];
		}

		$data = json_decode((string) $response, true);

		if (!is_array($data) && trim((string) $response) !== '') {
			return [
				'ok' => false,
				'http' => $code,
				'error' => 'Geçersiz API yanıtı',
				'raw' => mb_substr((string) $response, 0, 500),
			];
		}

		if (!is_array($data)) {
			$data = [];
		}

		$ok = $code >= 200 && $code < 300;

		if (!$ok) {
			$error = '';

			if (!empty($data['message'])) {
				$error = (string) $data['message'];
			} elseif (!empty($data['details'][0]['description'])) {
				$error = (string) $data['details'][0]['description'];
			} elseif (!empty($data['name'])) {
				$error = (string) $data['name'];
			} else {
				$error = 'HTTP ' . $code;
			}

			return [
				'ok' => false,
				'http' => $code,
				'data' => $data,
				'error' => $error,
				'raw' => mb_substr((string) $response, 0, 500),
			];
		}

		return ['ok' => true, 'http' => $code, 'data' => $data];
	}

	/**
	 * @param array<string, mixed> $orderBody
	 * @return array{ok:bool,id?:string,approve_url?:string,error?:string,data?:array}
	 */
	public function createOrder(array $orderBody): array
	{
		$result = $this->api('POST', '/v2/checkout/orders', $orderBody, [
			'Prefer: return=representation',
		]);

		if (!$result['ok']) {
			return [
				'ok' => false,
				'error' => $result['error'] ?? 'Order create failed',
				'data' => $result['data'] ?? null,
			];
		}

		$data = $result['data'] ?? [];
		$id = (string) ($data['id'] ?? '');
		$approve = '';

		foreach ($data['links'] ?? [] as $link) {
			if (!is_array($link)) {
				continue;
			}

			if (($link['rel'] ?? '') === 'approve' && !empty($link['href'])) {
				$approve = (string) $link['href'];
				break;
			}
		}

		if ($id === '' || $approve === '') {
			return [
				'ok' => false,
				'error' => 'PayPal onay bağlantısı alınamadı',
				'data' => $data,
			];
		}

		return [
			'ok' => true,
			'id' => $id,
			'approve_url' => $approve,
			'data' => $data,
		];
	}

	/**
	 * @return array{ok:bool,error?:string,data?:array,status?:string,capture_id?:string,amount?:float,currency?:string}
	 */
	public function captureOrder(string $orderId): array
	{
		$orderId = trim($orderId);

		if ($orderId === '') {
			return ['ok' => false, 'error' => 'PayPal order id boş'];
		}

		$result = $this->api('POST', '/v2/checkout/orders/' . rawurlencode($orderId) . '/capture', null, [
			'Prefer: return=representation',
		]);

		if (!$result['ok']) {
			return [
				'ok' => false,
				'error' => $result['error'] ?? 'Capture failed',
				'data' => $result['data'] ?? null,
			];
		}

		$data = $result['data'] ?? [];
		$status = (string) ($data['status'] ?? '');
		$captureId = '';
		$amount = 0.0;
		$currency = '';

		$units = $data['purchase_units'] ?? [];

		if (is_array($units) && isset($units[0]['payments']['captures'][0])) {
			$cap = $units[0]['payments']['captures'][0];
			$captureId = (string) ($cap['id'] ?? '');
			$amount = (float) ($cap['amount']['value'] ?? 0);
			$currency = (string) ($cap['amount']['currency_code'] ?? '');
		}

		$ok = in_array($status, ['COMPLETED', 'APPROVED'], true)
			|| ($captureId !== '' && strtoupper((string) (($units[0]['payments']['captures'][0]['status'] ?? '') ?: '')) === 'COMPLETED');

		if (!$ok && $status === 'COMPLETED') {
			$ok = true;
		}

		if ($status === 'COMPLETED') {
			$ok = true;
		}

		return [
			'ok' => $ok,
			'status' => $status,
			'capture_id' => $captureId,
			'amount' => $amount,
			'currency' => $currency,
			'data' => $data,
			'error' => $ok ? '' : ('Beklenmeyen durum: ' . ($status !== '' ? $status : 'bilinmiyor')),
		];
	}

	/**
	 * @return array{ok:bool,error?:string,data?:array,status?:string}
	 */
	public function getOrder(string $orderId): array
	{
		$result = $this->api('GET', '/v2/checkout/orders/' . rawurlencode($orderId));

		if (!$result['ok']) {
			return [
				'ok' => false,
				'error' => $result['error'] ?? 'Get order failed',
				'data' => $result['data'] ?? null,
			];
		}

		return [
			'ok' => true,
			'data' => $result['data'],
			'status' => (string) (($result['data']['status'] ?? '')),
		];
	}
}
