<?php

namespace N11;

/**
 * N11 Satıcı API — REST ürün/sipariş + SOAP soru-cevap
 */
class N11Api
{
	private $apiKey;
	private $apiSecret;
	private $baseUri;

	public function __construct(string $apiKey, string $apiSecret)
	{
		$this->apiKey = trim($apiKey);
		$this->apiSecret = trim($apiSecret);
		$this->baseUri = 'https://api.n11.com/rest/';
	}

	public function isConfigured(): bool
	{
		return $this->apiKey !== '' && $this->apiSecret !== '';
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>|null
	 */
	public function request(string $method, string $endpoint, array $data = [], bool $fullUrl = false): ?array
	{
		if (!$this->isConfigured()) {
			return [
				'ok' => false,
				'success' => false,
				'message' => 'N11 API kimlik bilgileri eksik',
			];
		}

		if (!function_exists('curl_init')) {
			return [
				'ok' => false,
				'success' => false,
				'message' => 'cURL eklentisi yok',
			];
		}

		$url = $fullUrl ? $endpoint : ($this->baseUri . ltrim($endpoint, '/'));
		$method = strtoupper($method);
		$ch = curl_init();

		if (in_array($method, ['POST', 'PUT', 'PATCH'], true) && $data !== []) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
		}

		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_HTTPHEADER => [
				'appkey: ' . $this->apiKey,
				'appsecret: ' . $this->apiSecret,
				'Content-Type: application/json',
				'integrator: FShop',
				'Accept: application/json',
			],
			CURLOPT_TIMEOUT => 60,
		]);

		$response = curl_exec($ch);
		$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		$decoded = json_decode((string) $response, true);

		if ($httpCode >= 200 && $httpCode < 300) {
			if ($decoded === null) {
				return ['ok' => true, 'success' => true, 'httpStatus' => $httpCode];
			}

			return is_array($decoded) ? $decoded : ['ok' => true, 'success' => true, 'raw' => $response];
		}

		$message = $error ?: ('HTTP ' . $httpCode);

		if (is_array($decoded)) {
			if (!empty($decoded['message'])) {
				$message = (string) $decoded['message'];
			} elseif (!empty($decoded['errorMessage'])) {
				$message = (string) $decoded['errorMessage'];
			}
		}

		return [
			'ok' => false,
			'success' => false,
			'httpCode' => $httpCode,
			'message' => $message,
			'body' => is_array($decoded) ? $decoded : (string) $response,
		];
	}

	/**
	 * @return array<int, array<string, mixed>>|array<string, mixed>|null
	 */
	public function getOrders(): ?array
	{
		$statuses = [
			'Created',
			'Picking',
			'Shipped',
			'Delivered',
			'Cancelled',
			'UnDelivered',
			'Returned',
			'UnSupplied',
		];
		$allOrders = [];

		foreach ($statuses as $status) {
			$page = 0;

			do {
				$endpoint = 'delivery/v1/shipmentPackages?' . http_build_query([
					'status' => $status,
					'orderByField' => 'true',
					'orderByDirection' => 'ASC',
					'page' => $page,
					'size' => 50,
				]);

				$response = $this->request('GET', $endpoint);

				if (self::isApiError($response)) {
					// Bazı hesaplarda iptal/iade status’leri 400 dönebilir; diğerlerini çekmeye devam et.
					break;
				}

				$items = $response['content'] ?? [];

				if (!is_array($items)) {
					$items = [];
				}

				foreach ($items as &$item) {
					if (is_array($item) && (!isset($item['status']) || trim((string) $item['status']) === '')) {
						$item['status'] = $status;
					}
				}
				unset($item);

				$allOrders = array_merge($allOrders, $items);
				$totalPages = (int) ($response['totalPages'] ?? 1);
				$page++;
			} while ($page < $totalPages && $items !== []);
		}

		return $allOrders;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getOrderDetail(string $orderNumber): ?array
	{
		$endpoint = 'delivery/v1/shipmentPackages?orderNumber=' . rawurlencode($orderNumber);

		return $this->request('GET', $endpoint);
	}

	/**
	 * Ürün iptali (splitPackageByQuantity — cancelledItems)
	 *
	 * @param array<int, array{orderLineId: int|string, quantity: int, cancelReasonId?: int}> $cancelledItems
	 * @return array<string, mixed>|null
	 */
	public function cancelPackageItems(array $cancelledItems): ?array
	{
		if ($cancelledItems === []) {
			return [
				'ok' => false,
				'success' => false,
				'message' => 'İptal satırı yok',
			];
		}

		$payload = [
			'cancelledItems' => array_values($cancelledItems),
			'splitPackages' => [],
		];

		return $this->request('POST', 'delivery/v1/splitPackageByQuantity', $payload);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getProduct(string $stockCode): ?array
	{
		$stockCode = trim($stockCode);

		if ($stockCode === '') {
			return [
				'ok' => false,
				'success' => false,
				'message' => 'Stok kodu boş',
			];
		}

		$url = 'https://api.n11.com/ms/product-query?stockCode=' . rawurlencode($stockCode);

		return $this->request('GET', $url, [], true);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function updateStockPrice(string $stockCode, float $listPrice, float $salePrice, int $stock): ?array
	{
		$url = 'https://api.n11.com/ms/product/tasks/price-stock-update';
		$data = [
			'payload' => [
				'integrator' => 'FShop',
				'skus' => [
					[
						'stockCode' => $stockCode,
						'listPrice' => (float) $listPrice,
						'salePrice' => (float) $salePrice,
						'quantity' => (int) $stock,
						'currencyType' => 'TL',
					],
				],
			],
		];

		return $this->request('POST', $url, $data, true);
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>|null
	 */
	public function createProduct(array $payload): ?array
	{
		$url = 'https://api.n11.com/ms/product/tasks/product-create';

		return $this->request('POST', $url, $payload, true);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getCategories(): ?array
	{
		return $this->request('GET', 'https://api.n11.com/cdn/categories', [], true);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getAttributes(int $categoryId): ?array
	{
		$url = 'https://api.n11.com/cdn/category/' . (int) $categoryId . '/attribute';

		return $this->request('GET', $url, [], true);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getProductQuestionList(
		string $status = 'OPEN',
		int $currentPage = 0,
		int $pageSize = 100,
		?string $startDate = null,
		?string $endDate = null
	): ?array {
		$startDate = $startDate ?? date('d/m/Y', strtotime('-60 days'));
		$endDate = $endDate ?? date('d/m/Y');

		$body = '<sch:GetProductQuestionListRequest>'
			. '<auth>'
			. '<appKey>' . htmlspecialchars($this->apiKey, ENT_XML1) . '</appKey>'
			. '<appSecret>' . htmlspecialchars($this->apiSecret, ENT_XML1) . '</appSecret>'
			. '</auth>'
			. '<productQuestionSearch>'
			. '<productId></productId>'
			. '<buyerEmail></buyerEmail>'
			. '<subject></subject>'
			. '<status>' . htmlspecialchars($status, ENT_XML1) . '</status>'
			. '<startDate>' . htmlspecialchars($startDate, ENT_XML1) . '</startDate>'
			. '<endDate>' . htmlspecialchars($endDate, ENT_XML1) . '</endDate>'
			. '</productQuestionSearch>'
			. '<pagingData>'
			. '<currentPage>' . (int) $currentPage . '</currentPage>'
			. '<pageSize>' . (int) $pageSize . '</pageSize>'
			. '</pagingData>'
			. '</sch:GetProductQuestionListRequest>';

		return $this->soapRequest($body);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function saveProductAnswer(int $productQuestionId, string $answer): ?array
	{
		$answer = mb_substr(trim($answer), 0, 2048);
		$body = '<sch:SaveProductAnswerRequest>'
			. '<auth>'
			. '<appKey>' . htmlspecialchars($this->apiKey, ENT_XML1) . '</appKey>'
			. '<appSecret>' . htmlspecialchars($this->apiSecret, ENT_XML1) . '</appSecret>'
			. '</auth>'
			. '<productQuestionId>' . (int) $productQuestionId . '</productQuestionId>'
			. '<answer>' . htmlspecialchars($answer, ENT_XML1) . '</answer>'
			. '</sch:SaveProductAnswerRequest>';

		return $this->soapRequest($body);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getQuestion($productId = null, $page = 0, $pageSize = 100, $status = 'OPEN')
	{
		return $this->getProductQuestionList((string) $status, (int) $page, (int) $pageSize);
	}

	/**
	 * @return array{items: array<int, array<string, mixed>>, pageCount: int, error: string}
	 */
	public static function extractQuestionsFromListResponse(?array $response): array
	{
		if (!is_array($response)) {
			return ['items' => [], 'pageCount' => 0, 'error' => 'Yanıt alınamadı'];
		}

		$status = strtolower((string) ($response['result']['status'] ?? ''));

		if ($status !== 'success') {
			return ['items' => [], 'pageCount' => 0, 'error' => self::extractSoapError($response)];
		}

		$questions = $response['productQuestions']['productQuestion'] ?? null;

		if ($questions === null) {
			if (empty($response['productQuestions'])) {
				return [
					'items' => [],
					'pageCount' => (int) ($response['pagingData']['pageCount'] ?? 0),
					'error' => '',
				];
			}

			return ['items' => [], 'pageCount' => 0, 'error' => ''];
		}

		if (isset($questions['id'])) {
			$questions = [$questions];
		}

		$listStatus = (string) ($response['productQuestions']['@attributes']['status'] ?? 'OPEN');
		$items = [];

		foreach ($questions as $q) {
			if (!is_array($q)) {
				continue;
			}

			$items[] = self::normalizeQuestionRow($q, $listStatus);
		}

		return [
			'items' => $items,
			'pageCount' => (int) ($response['pagingData']['pageCount'] ?? 1),
			'error' => '',
		];
	}

	public static function isSoapSuccess(?array $response): bool
	{
		return is_array($response) && self::extractSoapError($response) === '';
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
	 * @return array<string, mixed>|null
	 */
	private function soapRequest(string $bodyXml): ?array
	{
		if (!$this->isConfigured()) {
			return [
				'result' => ['status' => 'failure'],
				'message' => 'N11 API kimlik bilgileri eksik',
			];
		}

		$url = 'https://api.n11.com/ws/productService.wsdl';
		$xml = '<?xml version="1.0" encoding="UTF-8"?>'
			. '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sch="http://www.n11.com/ws/schemas">'
			. '<soapenv:Header/>'
			. '<soapenv:Body>' . $bodyXml . '</soapenv:Body>'
			. '</soapenv:Envelope>';

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $xml,
			CURLOPT_HTTPHEADER => [
				'Content-Type: text/xml; charset=utf-8',
				'SOAPAction: ""',
				'Content-Length: ' . strlen($xml),
			],
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_TIMEOUT => 30,
		]);

		$response = curl_exec($ch);
		$error = curl_error($ch);
		$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($response === false) {
			return ['result' => ['status' => 'failure'], 'message' => 'Curl error: ' . $error];
		}

		if ($httpCode < 200 || $httpCode >= 300) {
			return ['result' => ['status' => 'failure'], 'message' => 'HTTP ' . $httpCode, 'raw' => $response];
		}

		return $this->parseSoapResponse((string) $response);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function parseSoapResponse(string $response): ?array
	{
		$response = trim($response);

		if ($response === '') {
			return ['result' => ['status' => 'failure'], 'message' => 'Boş yanıt'];
		}

		libxml_use_internal_errors(true);
		$dom = new \DOMDocument();

		if (!$dom->loadXML($response)) {
			$errors = libxml_get_errors();
			libxml_clear_errors();
			$msg = !empty($errors[0]->message) ? trim($errors[0]->message) : 'XML parse error';

			return ['result' => ['status' => 'failure'], 'message' => $msg, 'raw' => $response];
		}

		$xpath = new \DOMXPath($dom);
		$xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
		$nodes = $xpath->query('//soap:Body/*');

		if (!$nodes || $nodes->length === 0) {
			$body = $dom->getElementsByTagName('Body')->item(0);

			if (!$body || !$body->firstChild) {
				return ['result' => ['status' => 'failure'], 'message' => 'SOAP body bulunamadı', 'raw' => $response];
			}

			$payload = $dom->saveXML($body->firstChild);
		} else {
			$payload = $dom->saveXML($nodes->item(0));
		}

		$payloadXml = simplexml_load_string((string) $payload);

		if (!$payloadXml) {
			return ['result' => ['status' => 'failure'], 'message' => 'SOAP payload parse error', 'raw' => $response];
		}

		$decoded = json_decode(json_encode($payloadXml), true);

		return is_array($decoded) ? $decoded : ['result' => ['status' => 'failure'], 'message' => 'SOAP decode error'];
	}

	public static function mapQuestionStatus(string $status, string $answer = ''): string
	{
		$status = strtoupper(trim($status));

		if ($status === 'OPEN') {
			return 'Cevap Bekleniyor';
		}

		if ($status === 'CLOSED' || trim($answer) !== '') {
			return 'Cevaplandı';
		}

		return 'Cevaplanmadı';
	}

	public static function parseQuestionDate(?string $date): string
	{
		if ($date === null || $date === '') {
			return '';
		}

		$date = trim($date);

		if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
			$dt = \DateTime::createFromFormat('d/m/Y', $date);

			return $dt ? $dt->format('Y-m-d H:i:s') : '';
		}

		$ts = strtotime($date);

		return $ts ? date('Y-m-d H:i:s', $ts) : '';
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	public static function normalizeQuestionRow(array $row, string $listStatus = 'OPEN'): array
	{
		$id = (int) ($row['id'] ?? 0);
		$productId = (string) ($row['productId'] ?? '');
		$answer = trim((string) ($row['answer'] ?? ''));
		$statusRaw = (string) ($row['status'] ?? $listStatus);

		return [
			'id' => $id,
			'productId' => $productId,
			'productName' => (string) ($row['productTitle'] ?? 'Ürün'),
			'text' => (string) ($row['question'] ?? ''),
			'subject' => (string) ($row['questionSubject'] ?? ''),
			'answer' => $answer,
			'userName' => (string) ($row['fullName'] ?? ($row['email'] ?? '***')),
			'imageUrl' => (string) ($row['imageUrl'] ?? ''),
			'webUrl' => $productId !== '' ? 'https://www.n11.com/urun/paroner-p-' . $productId : '',
			'creationDate' => self::parseQuestionDate($row['questionDate'] ?? '') ?: date('Y-m-d H:i:s'),
			'answerDate' => self::parseQuestionDate($row['answeredDate'] ?? ''),
			'status' => self::mapQuestionStatus($statusRaw, $answer),
		];
	}

	public static function extractSoapError(?array $response): string
	{
		if (!is_array($response)) {
			return 'Bilinmeyen hata';
		}

		if (!empty($response['message'])) {
			return (string) $response['message'];
		}

		if (!empty($response['result']['errorMessage'])) {
			return (string) $response['result']['errorMessage'];
		}

		if (!empty($response['result']['status']) && strtolower((string) $response['result']['status']) !== 'success') {
			return 'N11 API işlemi başarısız';
		}

		return '';
	}
}
