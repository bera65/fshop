<?php

namespace Hepsiburada;

/**
 * Hepsiburada Satıcı API — Listing / OMS / Ask / MPOP
 */
class HepsiburadaApi
{
	private $merchantId;
	private $apiKey;
	private $apiPass;

	public function __construct(string $merchantId, string $apiKey, string $apiPass)
	{
		$this->merchantId = trim($merchantId);
		$this->apiKey = trim($apiKey);
		$this->apiPass = trim($apiPass);
	}

	public function isConfigured(): bool
	{
		return $this->merchantId !== '' && $this->apiKey !== '' && $this->apiPass !== '';
	}

	public function getMerchantId(): string
	{
		return $this->merchantId;
	}

	/**
	 * @param mixed $body
	 * @return array<string, mixed>|null
	 */
	public function request(string $url, string $method = 'GET', $body = null): ?array
	{
		if (!$this->isConfigured()) {
			return [
				'success' => false,
				'httpCode' => 0,
				'message' => 'Hepsiburada API kimlik bilgileri eksik',
				'body' => null,
			];
		}

		if (!function_exists('curl_init')) {
			return [
				'success' => false,
				'httpCode' => 0,
				'message' => 'cURL eklentisi yok',
				'body' => null,
			];
		}

		$method = strtoupper($method);
		$ch = curl_init($url);

		$headers = [
			'Authorization: Basic ' . base64_encode($this->merchantId . ':' . $this->apiPass),
			'Content-Type: application/json',
			'Accept: application/json',
			'User-Agent: ' . $this->apiKey,
			'merchantId: ' . $this->merchantId,
		];

		if ($body !== null) {
			if (is_array($body) || is_object($body)) {
				$body = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}

		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_USERAGENT => $this->apiKey,
			CURLOPT_TIMEOUT => 60,
		]);

		$response = curl_exec($ch);
		$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($response === false) {
			return [
				'success' => false,
				'httpCode' => $httpCode,
				'message' => 'Curl error: ' . $error,
				'body' => null,
			];
		}

		$decoded = json_decode((string) $response, true);

		if ($httpCode >= 200 && $httpCode < 300) {
			if (trim((string) $response) === '') {
				return ['success' => true, 'httpStatus' => $httpCode];
			}

			if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
				return ['success' => true, 'httpStatus' => $httpCode, 'raw' => $response];
			}

			return is_array($decoded) ? $decoded : ['success' => true, 'httpStatus' => $httpCode, 'raw' => $response];
		}

		$message = $error ?: ('HTTP ' . $httpCode);

		if (is_array($decoded)) {
			if (!empty($decoded['message'])) {
				$message = (string) $decoded['message'];
			} elseif (!empty($decoded['errorMessage'])) {
				$message = (string) $decoded['errorMessage'];
			} elseif (!empty($decoded['title'])) {
				$message = (string) $decoded['title'];
			}
		}

		return [
			'success' => false,
			'httpCode' => $httpCode,
			'message' => $message,
			'body' => is_array($decoded) ? $decoded : (string) $response,
		];
	}

	/**
	 * HB OMS paket/sipariş listeleri:
	 * - packages/...            → bekleyen / açık paketler
	 * - packages/.../shipped    → kargolananlar
	 * - packages/.../delivered  → teslim edilenler
	 * - packages/.../undelivered
	 * - orders/.../cancelled    → iptaller
	 *
	 * @return array<string, mixed>|null
	 */
	public function getOrders(?string $startDate = null, ?string $endDate = null): ?array
	{
		if ($startDate !== null && $endDate !== null && $startDate !== '' && $endDate !== '') {
			$begin = date('Y-m-d', strtotime($startDate));
			$end = date('Y-m-d', strtotime($endDate));
		} else {
			$begin = date('Y-m-d', strtotime('-30 days'));
			$end = date('Y-m-d');
		}

		$all = [];
		$seen = [];

		// Açık / hazırlanan paketler (ham dizi döner)
		$this->mergePackageRows(
			$all,
			$seen,
			$this->fetchAllPackageEndpoint('', $begin, $end),
			'Open'
		);

		$this->mergePackageRows(
			$all,
			$seen,
			$this->fetchAllPackageEndpoint('/shipped', $begin, $end),
			'Shipped'
		);

		$this->mergePackageRows(
			$all,
			$seen,
			$this->fetchAllPackageEndpoint('/delivered', $begin, $end),
			'Delivered'
		);

		$this->mergePackageRows(
			$all,
			$seen,
			$this->fetchAllPackageEndpoint('/undelivered', $begin, $end),
			'UnDelivered'
		);

		$this->mergePackageRows(
			$all,
			$seen,
			$this->fetchCancelledOrders($begin, $end),
			'Cancelled'
		);

		return ['items' => $all];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function fetchAllPackageEndpoint(string $pathSuffix, string $begin, string $end): array
	{
		$rows = $this->fetchPackagePages($pathSuffix, $begin, $end);

		// Tarih filtresi boş dönerse tarihsiz dene (HB bazı uçlarda period zorunlu değil)
		if ($rows === [] && ($begin !== '' || $end !== '')) {
			$rows = $this->fetchPackagePages($pathSuffix, '', '');
		}

		return $rows;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function fetchPackagePages(string $pathSuffix, string $begin, string $end): array
	{
		$base = 'https://oms-external.hepsiburada.com/packages/merchantid/'
			. rawurlencode($this->merchantId)
			. $pathSuffix;

		$limit = 100;
		$offset = 0;
		$out = [];
		$maxPages = 50;

		for ($page = 0; $page < $maxPages; $page++) {
			$params = [
				'offset' => $offset,
				'limit' => $limit,
			];

			if ($begin !== '') {
				$params['beginDate'] = $begin;
			}

			if ($end !== '') {
				$params['endDate'] = $end;
			}

			$result = $this->request($base . '?' . http_build_query($params), 'GET');

			if ($result === null || (isset($result['success']) && $result['success'] === false)) {
				break;
			}

			$items = $this->extractListItems($result);

			if ($items === []) {
				break;
			}

			foreach ($items as $item) {
				if (is_array($item)) {
					$out[] = $item;
				}
			}

			$totalCount = (int) ($result['totalCount'] ?? 0);
			$offset += $limit;

			// Ham dizi cevabı (açık paketler): genelde tek sayfa
			if (!isset($result['totalCount']) && !isset($result['items']) && !isset($result['pageCount'])) {
				break;
			}

			if ($totalCount > 0 && $offset >= $totalCount) {
				break;
			}

			if (count($items) < $limit) {
				break;
			}
		}

		return $out;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function fetchCancelledOrders(string $begin, string $end): array
	{
		$rows = $this->fetchCancelledPages($begin, $end);

		if ($rows === [] && ($begin !== '' || $end !== '')) {
			$rows = $this->fetchCancelledPages('', '');
		}

		return $rows;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function fetchCancelledPages(string $begin, string $end): array
	{
		$base = 'https://oms-external.hepsiburada.com/orders/merchantid/'
			. rawurlencode($this->merchantId)
			. '/cancelled';

		$limit = 100;
		$offset = 0;
		$out = [];
		$maxPages = 50;

		for ($page = 0; $page < $maxPages; $page++) {
			$params = [
				'offset' => $offset,
				'limit' => $limit,
			];

			if ($begin !== '') {
				$params['beginDate'] = $begin;
			}

			if ($end !== '') {
				$params['endDate'] = $end;
			}

			$result = $this->request($base . '?' . http_build_query($params), 'GET');

			if ($result === null || (isset($result['success']) && $result['success'] === false)) {
				break;
			}

			$items = $this->extractListItems($result);

			if ($items === []) {
				break;
			}

			foreach ($items as $item) {
				if (!is_array($item)) {
					continue;
				}

				if (!isset($item['status']) || trim((string) $item['status']) === '') {
					$item['status'] = 'Cancelled';
				}

				if (empty($item['items']) && !empty($item['lineItems']) && is_array($item['lineItems'])) {
					$item['items'] = $item['lineItems'];
				}

				$out[] = $item;
			}

			$totalCount = (int) ($result['totalCount'] ?? 0);
			$offset += $limit;

			if ($totalCount > 0 && $offset >= $totalCount) {
				break;
			}

			if (count($items) < $limit) {
				break;
			}
		}

		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $all
	 * @param array<string, true> $seen
	 * @param array<int, array<string, mixed>> $rows
	 */
	private function mergePackageRows(array &$all, array &$seen, array $rows, string $fallbackStatus): void
	{
		foreach ($rows as $pkg) {
			if (!is_array($pkg)) {
				continue;
			}

			$orderNumber = trim((string) ($pkg['orderNumber'] ?? ''));

			if ($orderNumber === '' && !empty($pkg['items'][0]['orderNumber'])) {
				$orderNumber = trim((string) $pkg['items'][0]['orderNumber']);
				$pkg['orderNumber'] = $orderNumber;
			}

			$packageKey = trim((string) ($pkg['packageNumber'] ?? ($pkg['id'] ?? ($pkg['barcode'] ?? ''))));
			$key = $packageKey . '|' . $orderNumber;

			if ($key === '|' || isset($seen[$key])) {
				continue;
			}

			// Aynı sipariş numarası farklı paket anahtarıyla gelmiş olabilir; status güncellemesi için order bazlı da dedupe etme
			$seen[$key] = true;

			if (!isset($pkg['status']) || trim((string) $pkg['status']) === '') {
				$pkg['status'] = $fallbackStatus;
			}

			$all[] = $pkg;
		}
	}

	/**
	 * @param array<string, mixed> $result
	 * @return array<int, mixed>
	 */
	private function extractListItems(array $result): array
	{
		if (isset($result['items']) && is_array($result['items'])) {
			return $result['items'];
		}

		if (isset($result['content']) && is_array($result['content'])) {
			return $result['content'];
		}

		// Ham dizi: [0 => [...], 1 => [...]] — meta anahtarları yok
		if (isset($result[0]) && is_array($result[0])) {
			$items = [];

			foreach ($result as $k => $v) {
				if (is_int($k) && is_array($v)) {
					$items[] = $v;
				}
			}

			return $items;
		}

		return [];
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getOrderDetail(string $orderNumber): ?array
	{
		$url = 'https://oms-external.hepsiburada.com/orders/merchantid/' . rawurlencode($this->merchantId)
			. '/ordernumber/' . rawurlencode($orderNumber);

		return $this->request($url, 'GET');
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function cancelLineItem(string $lineId, string $reason = 'out-of-stock'): ?array
	{
		$lineId = trim($lineId);

		if ($lineId === '') {
			return [
				'success' => false,
				'message' => 'Line item id boş',
			];
		}

		$url = 'https://oms-external.hepsiburada.com/lineitems/merchantid/'
			. rawurlencode($this->merchantId)
			. '/id/' . rawurlencode($lineId)
			. '/cancelbymerchant';

		return $this->request($url, 'POST', ['reason' => $reason]);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getProduct(string $merchantSku): ?array
	{
		$merchantSku = trim($merchantSku);

		if ($merchantSku === '') {
			return [
				'success' => false,
				'httpCode' => 0,
				'message' => 'Merchant SKU boş',
				'body' => null,
			];
		}

		$url = 'https://listing-external.hepsiburada.com/listings/merchantid/' . rawurlencode($this->merchantId)
			. '?offset=0&limit=10&merchantSkuList=' . rawurlencode($merchantSku);

		return $this->request($url, 'GET');
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function updateStock(string $merchantSku, int $stock, ?string $hepsiburadaSku = null): ?array
	{
		$url = 'https://listing-external.hepsiburada.com/listings/merchantid/' . rawurlencode($this->merchantId)
			. '/stock-uploads';

		$item = ['availableStock' => (int) $stock];

		if ($hepsiburadaSku !== null && trim($hepsiburadaSku) !== '') {
			$item['hepsiburadaSku'] = trim($hepsiburadaSku);
		} else {
			$item['merchantSku'] = $merchantSku;
		}

		return $this->request($url, 'POST', [$item]);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function updatePrice(string $merchantSku, float $price, ?string $hepsiburadaSku = null): ?array
	{
		$url = 'https://listing-external.hepsiburada.com/listings/merchantid/' . rawurlencode($this->merchantId)
			. '/price-uploads';

		$item = ['price' => (float) $price];

		if ($hepsiburadaSku !== null && trim($hepsiburadaSku) !== '') {
			$item['hepsiburadaSku'] = trim($hepsiburadaSku);
		} else {
			$item['merchantSku'] = $merchantSku;
		}

		return $this->request($url, 'POST', [$item]);
	}

	/**
	 * @return array{price: mixed, stock: mixed}
	 */
	public function updateStockPrice(string $merchantSku, float $price, int $stock, ?string $hepsiburadaSku = null): array
	{
		return [
			'price' => $this->updatePrice($merchantSku, $price, $hepsiburadaSku),
			'stock' => $this->updateStock($merchantSku, $stock, $hepsiburadaSku),
		];
	}

	/**
	 * Satıcıya Sor — Soru listesi
	 * Prod: https://api-asktoseller-merchant.hepsiburada.com/api/v1.0/issues
	 * Test: https://api-asktoseller-merchant-sit.hepsiburada.com/api/v1.0/issues
	 *
	 * @param array<string, mixed> $params page, size, desc, status
	 * @return array<string, mixed>|null
	 */
	public function getQuestions(array $params = []): ?array
	{
		$query = [
			'desc' => isset($params['desc']) ? (string) $params['desc'] : 'true',
			'page' => max(1, (int) ($params['page'] ?? 1)),
			'size' => min(100, max(1, (int) ($params['size'] ?? 25))),
		];

		if (isset($params['status']) && trim((string) $params['status']) !== '') {
			$query['status'] = trim((string) $params['status']);
		}

		return $this->askRequest('/issues', 'GET', $query);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getIssueDetail(string $number): ?array
	{
		$number = trim($number);

		if ($number === '') {
			return [
				'success' => false,
				'ok' => false,
				'httpCode' => 0,
				'message' => 'Soru numarası boş',
			];
		}

		return $this->askRequest('/issues/' . rawurlencode($number), 'GET');
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function answerIssue(string $number, string $message): ?array
	{
		$number = trim($number);
		$message = mb_substr(trim($message), 0, 2000);

		if ($number === '') {
			return [
				'success' => false,
				'ok' => false,
				'httpCode' => 0,
				'message' => 'Soru numarası boş',
			];
		}

		// Dokümantasyon: JSON body { "Answer": "..." }
		$jsonResult = $this->askRequest(
			'/issues/' . rawurlencode($number) . '/answer',
			'POST',
			[],
			['Answer' => $message],
			'application/json'
		);

		if (!self::isApiError($jsonResult)) {
			return $jsonResult;
		}

		// Bazı ortamlarda küçük harf "answer" beklenir
		$alt = $this->askRequest(
			'/issues/' . rawurlencode($number) . '/answer',
			'POST',
			[],
			['answer' => $message],
			'application/json'
		);

		if (!self::isApiError($alt)) {
			return $alt;
		}

		$boundary = '----HbAsk' . uniqid();
		$eol = "\r\n";
		$body = '';
		$body .= '--' . $boundary . $eol;
		$body .= 'Content-Disposition: form-data; name="Answer"' . $eol . $eol;
		$body .= $message . $eol;
		$body .= '--' . $boundary . '--' . $eol;

		return $this->askRequest(
			'/issues/' . rawurlencode($number) . '/answer',
			'POST',
			[],
			$body,
			'multipart/form-data; boundary=' . $boundary
		);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getCategoriesAttributes(int $categoryId): ?array
	{
		$url = 'https://mpop.hepsiburada.com/product/api/categories/' . (int) $categoryId . '/attributes?version=2';

		return $this->request($url, 'GET');
	}

	/**
	 * @param array<string, mixed> $item
	 * @return array<string, mixed>|null
	 */
	public function createProduct(array $item): ?array
	{
		$url = 'https://mpop.hepsiburada.com/product/api/products/fastlisting';

		return $this->request($url, 'POST', [$item]);
	}

	/**
	 * @param mixed $result
	 */
	public static function isApiError($result): bool
	{
		if ($result === null) {
			return true;
		}

		if (!is_array($result)) {
			return true;
		}

		if (array_key_exists('success', $result) && $result['success'] === false) {
			return true;
		}

		if (array_key_exists('ok', $result) && $result['ok'] === false) {
			return true;
		}

		return false;
	}

	/**
	 * Satıcıya Sor API — production host (sit sadece test).
	 *
	 * @param array<string, mixed> $query
	 * @param mixed $body
	 * @return array<string, mixed>|null
	 */
	private function askRequest(
		string $path,
		string $method = 'GET',
		array $query = [],
		$body = null,
		string $contentType = 'application/json'
	): ?array {
		if (!$this->isConfigured()) {
			return [
				'success' => false,
				'ok' => false,
				'httpCode' => 0,
				'message' => 'Hepsiburada API kimlik bilgileri eksik',
			];
		}

		$url = 'https://api-asktoseller-merchant.hepsiburada.com/api/v1.0' . $path;

		if ($query !== []) {
			$url .= '?' . http_build_query($query);
		}

		$method = strtoupper($method);
		$ch = curl_init($url);

		$headers = [
			'Accept: application/json',
			'Content-Type: ' . $contentType,
			'merchantId: ' . $this->merchantId,
			'User-Agent: ' . $this->apiKey,
			'Authorization: Basic ' . base64_encode($this->merchantId . ':' . $this->apiPass),
		];

		if ($body !== null) {
			if (is_array($body) || is_object($body)) {
				$body = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}

		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_USERAGENT => $this->apiKey,
			CURLOPT_TIMEOUT => 60,
		]);

		$response = curl_exec($ch);
		$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($response === false) {
			return [
				'success' => false,
				'ok' => false,
				'httpCode' => $httpCode,
				'message' => 'Curl error: ' . $error,
			];
		}

		if ($httpCode >= 200 && $httpCode < 300 && trim((string) $response) === '') {
			return ['ok' => true, 'success' => true];
		}

		$decoded = json_decode((string) $response, true);

		if ($httpCode >= 200 && $httpCode < 300) {
			if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
				return ['ok' => true, 'success' => true, 'raw' => $response];
			}

			if (is_array($decoded)) {
				$decoded['_httpOk'] = true;
				return $decoded;
			}

			return ['ok' => true, 'success' => true, 'raw' => $response];
		}

		$message = $error ?: ('HTTP ' . $httpCode);

		if (is_array($decoded)) {
			if (!empty($decoded['message'])) {
				$message = (string) $decoded['message'];
			} elseif (!empty($decoded['errorMessage'])) {
				$message = (string) $decoded['errorMessage'];
			} elseif (!empty($decoded['title'])) {
				$message = (string) $decoded['title'];
			} elseif (!empty($decoded['errors']) && is_array($decoded['errors'])) {
				$first = reset($decoded['errors']);
				if (is_string($first)) {
					$message = $first;
				} elseif (is_array($first) && !empty($first['message'])) {
					$message = (string) $first['message'];
				}
			}
		}

		return [
			'success' => false,
			'ok' => false,
			'httpCode' => $httpCode,
			'message' => $message,
			'body' => is_array($decoded) ? $decoded : (string) $response,
		];
	}
}
