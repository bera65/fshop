<?php

/**
 * Web Push (VAPID + aes128gcm) — PHP 7.4+, harici bağımlılık yok.
 * Composer veya sunucu komutu gerekmez.
 */
class WebPushNative
{
	private const CONTENT_ENCODING = 'aes128gcm';

	public static function createVapidKeys(): array
	{
		if (!function_exists('openssl_pkey_new')) {
			return ['publicKey' => '', 'privateKey' => ''];
		}

		$key = openssl_pkey_new([
			'curve_name' => 'prime256v1',
			'private_key_type' => OPENSSL_KEYTYPE_EC,
		]);

		if ($key === false) {
			return ['publicKey' => '', 'privateKey' => ''];
		}

		$details = openssl_pkey_get_details($key);

		if ($details === false || empty($details['ec'])) {
			return ['publicKey' => '', 'privateKey' => ''];
		}

		$x = str_pad((string) $details['ec']['x'], 32, "\0", STR_PAD_LEFT);
		$y = str_pad((string) $details['ec']['y'], 32, "\0", STR_PAD_LEFT);
		$d = str_pad((string) $details['ec']['d'], 32, "\0", STR_PAD_LEFT);
		$public = "\x04" . $x . $y;

		return [
			'publicKey' => self::base64UrlEncode($public),
			'privateKey' => self::base64UrlEncode($d),
		];
	}

	/**
	 * @return array{success: bool, status: int, reason: string}
	 */
	public static function send(
		string $endpoint,
		string $userPublicKey,
		string $userAuthToken,
		string $payload,
		string $vapidSubject,
		string $vapidPublicKey,
		string $vapidPrivateKey
	): array {
		if (!function_exists('curl_init')) {
			return ['success' => false, 'status' => 0, 'reason' => 'cURL extension missing'];
		}

		try {
			$publicBin = self::base64UrlDecode($vapidPublicKey);
			$privateBin = self::base64UrlDecode($vapidPrivateKey);

			if (strlen($publicBin) !== 65 || strlen($privateBin) !== 32) {
				return ['success' => false, 'status' => 0, 'reason' => 'Invalid VAPID keys'];
			}

			$paddedPayload = $payload . chr(2);
			$encrypted = self::encryptPayload($paddedPayload, $userPublicKey, $userAuthToken);
			$content = self::getContentCodingHeader($encrypted['salt'], $encrypted['localPublicKey']) . $encrypted['cipherText'];

			$audience = parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);
			$authHeader = self::buildVapidAuthorizationHeader($audience, $vapidSubject, $publicBin, $privateBin);

			$headers = [
				'Content-Type: application/octet-stream',
				'Content-Encoding: ' . self::CONTENT_ENCODING,
				'Content-Length: ' . strlen($content),
				'TTL: 2419200',
				'Authorization: ' . $authHeader,
			];

			$ch = curl_init($endpoint);
			curl_setopt_array($ch, [
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $content,
				CURLOPT_HTTPHEADER => $headers,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER => true,
				CURLOPT_TIMEOUT => 30,
			]);

			$response = curl_exec($ch);
			$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$error = curl_error($ch);
			curl_close($ch);

			if ($response === false) {
				return ['success' => false, 'status' => 0, 'reason' => $error !== '' ? $error : 'cURL failed'];
			}

			$success = $status >= 200 && $status < 300;

			return [
				'success' => $success,
				'status' => $status,
				'reason' => $success ? 'OK' : ('HTTP ' . $status),
			];
		} catch (Throwable $e) {
			return ['success' => false, 'status' => 0, 'reason' => $e->getMessage()];
		}
	}

	private static function encryptPayload(string $payload, string $userPublicKeyB64, string $userAuthTokenB64): array
	{
		$userPublicKey = self::base64UrlDecode($userPublicKeyB64);
		$userAuthToken = self::base64UrlDecode($userAuthTokenB64);

		if (strlen($userPublicKey) === 64) {
			$userPublicKey = "\x04" . $userPublicKey;
		}

		if (strlen($userPublicKey) !== 65) {
			throw new RuntimeException('Invalid subscription public key length: ' . strlen($userPublicKey));
		}

		$localKey = openssl_pkey_new([
			'curve_name' => 'prime256v1',
			'private_key_type' => OPENSSL_KEYTYPE_EC,
		]);

		if ($localKey === false) {
			throw new RuntimeException('Unable to create local EC key');
		}

		$localDetails = openssl_pkey_get_details($localKey);

		if ($localDetails === false || empty($localDetails['ec'])) {
			throw new RuntimeException('Unable to read local EC key');
		}

		$localPublicKey = "\x04"
			. str_pad((string) $localDetails['ec']['x'], 32, "\0", STR_PAD_LEFT)
			. str_pad((string) $localDetails['ec']['y'], 32, "\0", STR_PAD_LEFT);

		$userPublicPem = self::publicKeyToPem($userPublicKey);
		$sharedSecret = openssl_pkey_derive($userPublicPem, $localKey, 256);

		if ($sharedSecret === false) {
			throw new RuntimeException('Unable to derive shared secret');
		}

		$sharedSecret = str_pad($sharedSecret, 32, "\0", STR_PAD_LEFT);
		$salt = random_bytes(16);

		$ikm = self::hkdf(
			$userAuthToken,
			$sharedSecret,
			'WebPush: info' . "\0" . $userPublicKey . $localPublicKey,
			32
		);

		$cekInfo = 'Content-Encoding: aes128gcm' . "\0";
		$contentEncryptionKey = self::hkdf($salt, $ikm, $cekInfo, 16);

		$nonceInfo = 'Content-Encoding: nonce' . "\0";
		$nonce = self::hkdf($salt, $ikm, $nonceInfo, 12);

		$tag = '';
		$cipherText = openssl_encrypt($payload, 'aes-128-gcm', $contentEncryptionKey, OPENSSL_RAW_DATA, $nonce, $tag);

		if ($cipherText === false) {
			throw new RuntimeException('Payload encryption failed');
		}

		return [
			'localPublicKey' => $localPublicKey,
			'salt' => $salt,
			'cipherText' => $cipherText . $tag,
		];
	}

	private static function getContentCodingHeader(string $salt, string $localPublicKey): string
	{
		return $salt . pack('N', 4096) . chr(strlen($localPublicKey)) . $localPublicKey;
	}

	private static function buildVapidAuthorizationHeader(
		string $audience,
		string $subject,
		string $publicKeyBin,
		string $privateKeyBin
	): string {
		$header = self::base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
		$claims = self::base64UrlEncode(json_encode([
			'aud' => $audience,
			'exp' => time() + 43200,
			'sub' => $subject,
		], JSON_UNESCAPED_SLASHES));

		$unsigned = $header . '.' . $claims;
		$privatePem = self::privateKeyToPem($privateKeyBin);
		$pkey = openssl_pkey_get_private($privatePem);

		if ($pkey === false) {
			throw new RuntimeException('Invalid VAPID private key');
		}

		$derSignature = '';
		$signed = openssl_sign($unsigned, $derSignature, $pkey, OPENSSL_ALGO_SHA256);

		if (PHP_MAJOR_VERSION < 8 && is_resource($pkey)) {
			openssl_pkey_free($pkey);
		}

		if (!$signed) {
			throw new RuntimeException('VAPID JWT signing failed');
		}

		$joseSignature = self::derSignatureToJose($derSignature);
		$jwt = $unsigned . '.' . self::base64UrlEncode($joseSignature);
		$encodedPublicKey = self::base64UrlEncode($publicKeyBin);

		return 'vapid t=' . $jwt . ', k=' . $encodedPublicKey;
	}

	private static function hkdf(string $salt, string $ikm, string $info, int $length): string
	{
		$prk = hash_hmac('sha256', $ikm, $salt, true);

		return substr(hash_hmac('sha256', $info . chr(1), $prk, true), 0, $length);
	}

	private static function publicKeyToPem(string $publicKeyUncompressed): string
	{
		$prefix = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200');
		$der = $prefix . $publicKeyUncompressed;

		return "-----BEGIN PUBLIC KEY-----\n"
			. chunk_split(base64_encode($der), 64, "\n")
			. "-----END PUBLIC KEY-----\n";
	}

	private static function privateKeyToPem(string $privateKey32): string
	{
		$privateKey32 = str_pad($privateKey32, 32, "\0", STR_PAD_LEFT);
		$oid = hex2bin('06082a8648ce3d030107');
		$body = chr(0x02) . chr(0x01) . chr(0x01)
			. self::asn1OctetString($privateKey32)
			. "\xa0\x0a" . $oid;
		$der = self::asn1Sequence($body);

		return "-----BEGIN EC PRIVATE KEY-----\n"
			. chunk_split(base64_encode($der), 64, "\n")
			. "-----END EC PRIVATE KEY-----\n";
	}

	private static function asn1OctetString(string $value): string
	{
		return chr(0x04) . self::asn1Length(strlen($value)) . $value;
	}

	private static function asn1Sequence(string $value): string
	{
		return chr(0x30) . self::asn1Length(strlen($value)) . $value;
	}

	private static function asn1Length(int $length): string
	{
		if ($length < 128) {
			return chr($length);
		}

		$bytes = ltrim(pack('N', $length), "\0");

		return chr(0x80 | strlen($bytes)) . $bytes;
	}

	private static function derSignatureToJose(string $der): string
	{
		$offset = 0;

		if (ord($der[$offset++]) !== 0x30) {
			throw new RuntimeException('Invalid DER signature');
		}

		self::readAsn1Length($der, $offset);

		if (ord($der[$offset++]) !== 0x02) {
			throw new RuntimeException('Invalid DER signature (r)');
		}

		$rLen = self::readAsn1Length($der, $offset);
		$r = substr($der, $offset, $rLen);
		$offset += $rLen;

		if (ord($der[$offset++]) !== 0x02) {
			throw new RuntimeException('Invalid DER signature (s)');
		}

		$sLen = self::readAsn1Length($der, $offset);
		$s = substr($der, $offset, $sLen);

		$r = ltrim($r, "\0");
		$s = ltrim($s, "\0");

		return str_pad($r, 32, "\0", STR_PAD_LEFT) . str_pad($s, 32, "\0", STR_PAD_LEFT);
	}

	private static function readAsn1Length(string $der, int &$offset): int
	{
		$byte = ord($der[$offset++]);

		if ($byte < 128) {
			return $byte;
		}

		$numBytes = $byte & 0x7f;
		$lengthBytes = substr($der, $offset, $numBytes);
		$offset += $numBytes;

		return unpack('N', str_pad($lengthBytes, 4, "\0", STR_PAD_LEFT))[1];
	}

	private static function base64UrlEncode(string $data): string
	{
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	private static function base64UrlDecode(string $data): string
	{
		$remainder = strlen($data) % 4;

		if ($remainder > 0) {
			$data .= str_repeat('=', 4 - $remainder);
		}

		$decoded = base64_decode(strtr($data, '-_', '+/'), true);

		return $decoded === false ? '' : $decoded;
	}
}
