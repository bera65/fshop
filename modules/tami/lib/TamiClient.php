<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

/**
 * Tami Payment API istemcisi (3D Secure auth + complete-3ds).
 */
class TamiClient
{
	public const SANDBOX_BASE = 'https://sandbox-paymentapi.tami.com.tr';
	public const PROD_BASE = 'https://paymentapi.tami.com.tr';

	private string $merchantNumber;
	private string $terminalNumber;
	private string $secretKey;
	private string $kid;
	private string $kValue;
	private bool $testMode;

	public function __construct(
		string $merchantNumber,
		string $terminalNumber,
		string $secretKey,
		string $kid,
		string $kValue,
		bool $testMode
	) {
		$this->merchantNumber = trim($merchantNumber);
		$this->terminalNumber = trim($terminalNumber);
		$this->secretKey = trim($secretKey);
		$this->kid = trim($kid);
		$this->kValue = trim($kValue);
		$this->testMode = $testMode;
	}

	public function baseUrl(): string
	{
		return $this->testMode ? self::SANDBOX_BASE : self::PROD_BASE;
	}

	public function authToken(): string
	{
		$hash = base64_encode(hash(
			'sha256',
			$this->merchantNumber . $this->terminalNumber . $this->secretKey,
			true
		));

		return $this->merchantNumber . ':' . $this->terminalNumber . ':' . $hash;
	}

	/**
	 * securityHash = JWS Compact (HS512) over request body without securityHash itself.
	 * kid|k portal “Kid Değeri” + “K Değeri” (base64url JWK k).
	 *
	 * @param array<string, mixed> $body
	 */
	public function securityHash(array $body): string
	{
		unset($body['securityHash']);

		$bodyJson = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if ($bodyJson === false) {
			return '';
		}

		$headerJson = json_encode([
			'alg' => 'HS512',
			'typ' => 'JWT',
			'kid' => $this->kid !== '' ? $this->kid : ' ',
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		$headerB64 = self::base64UrlEncode((string) $headerJson);
		$payloadB64 = self::base64UrlEncode($bodyJson);
		$signingInput = $headerB64 . '.' . $payloadB64;

		$keyRaw = base64_decode(self::base64UrlNormalize($this->kValue), true);

		if ($keyRaw === false || $keyRaw === '') {
			// K değeri bozuksa ham string ile dene (bazı sandbox örnekleri)
			$keyRaw = $this->kValue;
		}

		$signatureB64 = self::base64UrlEncode(hash_hmac('sha512', $signingInput, $keyRaw, true));

		return $headerB64 . '.' . $payloadB64 . '.' . $signatureB64;
	}

	/**
	 * @param array<string, mixed> $body
	 * @return array{ok:bool,http:int,data?:array,error?:string,raw?:string}
	 */
	public function post(string $path, array $body): array
	{
		$body['securityHash'] = $this->securityHash($body);
		$json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if ($json === false) {
			return ['ok' => false, 'http' => 0, 'error' => 'JSON encode failed'];
		}

		if (!function_exists('curl_init')) {
			return ['ok' => false, 'http' => 0, 'error' => 'PHP curl eklentisi yok'];
		}

		$url = rtrim($this->baseUrl(), '/') . '/' . ltrim($path, '/');
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $json,
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json; charset=utf-8',
				'Accept: application/json',
				'PG-Auth-Token: ' . $this->authToken(),
			],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 8,
			CURLOPT_TIMEOUT => 45,
		]);
		$response = curl_exec($ch);
		$curlErr = curl_error($ch);
		$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($response === false) {
			return ['ok' => false, 'http' => $code, 'error' => $curlErr !== '' ? $curlErr : 'curl failed'];
		}

		$data = json_decode($response, true);

		if (!is_array($data)) {
			return [
				'ok' => false,
				'http' => $code,
				'error' => 'Geçersiz API yanıtı',
				'raw' => mb_substr((string) $response, 0, 800),
			];
		}

		$success = $data['success'] ?? false;
		$isOk = $code >= 200 && $code < 300 && (
			$success === true
			|| $success === 1
			|| $success === '1'
			|| $success === 'true'
			|| $success === 'True'
		);

		$error = '';

		if (!$isOk) {
			$error = trim((string) ($data['errorMessage'] ?? $data['mdErrorMessage'] ?? ''));

			if ($error === '' && !empty($data['errorCode'])) {
				$error = 'Hata kodu: ' . (string) $data['errorCode'];
			}

			if ($error === '') {
				$error = 'HTTP ' . $code;
			}
		}

		return [
			'ok' => $isOk,
			'http' => $code,
			'data' => $data,
			'error' => $error,
			'raw' => mb_substr((string) $response, 0, 800),
		];
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array{ok:bool,http:int,data?:array,error?:string,html?:string}
	 */
	public function start3dAuth(array $payload): array
	{
		$result = $this->post('/payment/auth', $payload);

		if (!$result['ok']) {
			return $result;
		}

		$content = (string) ($result['data']['threeDSHtmlContent'] ?? '');

		if ($content === '') {
			return [
				'ok' => false,
				'http' => $result['http'],
				'data' => $result['data'],
				'error' => '3D HTML içeriği gelmedi',
			];
		}

		$html = base64_decode($content, true);

		if ($html === false || trim($html) === '') {
			return [
				'ok' => false,
				'http' => $result['http'],
				'data' => $result['data'],
				'error' => '3D HTML decode edilemedi',
			];
		}

		$result['html'] = $html;

		return $result;
	}

	/**
	 * @return array{ok:bool,http:int,data?:array,error?:string}
	 */
	public function complete3ds(string $orderId): array
	{
		return $this->post('/payment/complete-3ds', [
			'orderId' => $orderId,
		]);
	}

	/** @param array<string, mixed> $callback */
	public function verifyCallbackHash(array $callback): bool
	{
		$received = (string) ($callback['hashedData'] ?? '');

		if ($received === '') {
			return false;
		}

		$defaultParams = 'cardOrganization+cardBrand+cardType+maskedNumber+installmentCount+currencyCode+txnAmount+orderId+systemTime+success';
		$params = (string) ($callback['hashParams'] ?? $defaultParams);
		$data = '';

		foreach (explode('+', $params) as $field) {
			$field = trim($field);

			if ($field === '') {
				continue;
			}

			$value = $callback[$field] ?? '';

			if ($field === 'success') {
				if ($value === true || $value === 1 || (string) $value === '1' || (string) $value === 'true') {
					$value = 'true';
				} elseif ($value === false || $value === 0 || (string) $value === '0' || (string) $value === 'false') {
					$value = 'false';
				}
			}

			$data .= (string) $value;
		}

		$expected = base64_encode(hash_hmac('sha256', $data, $this->secretKey, true));

		return hash_equals($expected, $received);
	}

	public static function base64UrlEncode(string $input): string
	{
		return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
	}

	public static function base64UrlNormalize(string $base64Url): string
	{
		$base64 = str_replace(['-', '_'], ['+', '/'], $base64Url);
		$mod = strlen($base64) % 4;

		if ($mod === 2) {
			$base64 .= '==';
		} elseif ($mod === 3) {
			$base64 .= '=';
		}

		return $base64;
	}
}
