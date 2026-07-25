<?php

namespace Trendyol;

/**
 * Trendyol Satıcı API — Ürün / Stok / Fiyat / Sipariş / Soru-Cevap
 * Doküman: https://developers.trendyol.com
 */
class TrendyolApi
{
	private string $supplierId;
	private string $apiKey;
	private string $apiSecret;
	private string $baseUri;

	public function __construct(string $supplierId, string $apiKey, string $apiSecret)
	{
		$this->supplierId = trim($supplierId);
		$this->apiKey = trim($apiKey);
		$this->apiSecret = trim($apiSecret);
		$this->baseUri = 'https://apigw.trendyol.com/integration/';
	}

	public function isConfigured(): bool
	{
		return $this->supplierId !== '' && $this->apiKey !== '' && $this->apiSecret !== '';
	}

	public function getSupplierId(): string
	{
		return $this->supplierId;
	}

	public function getOrders()
	{
		$status = 'Created,Picking,Invoiced,Shipped,Cancelled,Delivered,UnDelivered,Returned,AtCollectionPoint,UnPacked,UnSupplied';
		$endpoint = "order/sellers/{$this->supplierId}/orders?status={$status}&orderByField=PackageLastModifiedDate&orderByDirection=DESC&size=200";

		return $this->request('GET', $endpoint);
	}

	public function getOrderDetail($idOrder)
	{
		$endpoint = "order/sellers/{$this->supplierId}/orders?orderNumber=" . (int) $idOrder;

		return $this->request('GET', $endpoint);
	}

	public function getReturnOrders($claimItemStatus = null, $page = 0, $size = 50, $startDate = null, $endDate = null)
	{
		$params = [
			'page' => (int) $page,
			'size' => min(200, max(1, (int) $size)),
		];

		if ($claimItemStatus) {
			$params['claimItemStatus'] = $claimItemStatus;
		}

		if ($startDate !== null) {
			$params['startDate'] = (int) $startDate;
		}

		if ($endDate !== null) {
			$params['endDate'] = (int) $endDate;
		}

		$endpoint = "order/sellers/{$this->supplierId}/claims?" . http_build_query($params);

		return $this->request('GET', $endpoint);
	}

	public function getClaimById($claimId)
	{
		$endpoint = "order/sellers/{$this->supplierId}/claims?claimIds=" . urlencode((string) $claimId);

		return $this->request('GET', $endpoint);
	}

	public function approveClaimItems($claimId, array $claimLineItemIdList)
	{
		$endpoint = "order/sellers/{$this->supplierId}/claims/" . urlencode((string) $claimId) . '/items/approve';
		$data = [
			'claimLineItemIdList' => array_values($claimLineItemIdList),
			'params' => new \stdClass(),
		];

		return $this->request('PUT', $endpoint, $data);
	}

	public function getOrderInf($idOrder, $startDate, $endDate)
	{
		date_default_timezone_set('Europe/Istanbul');

		$start = (int) round(strtotime($startDate . ' 00:00:00') * 1000);
		$end = (int) round(strtotime($endDate . ' 23:59:59') * 1000);

		if ($idOrder) {
			$endpoint = "order/sellers/{$this->supplierId}/orders?orderNumber=" . (int) $idOrder;
		} else {
			$endpoint = "order/sellers/{$this->supplierId}/orders?startDate={$start}&endDate={$end}&orderByField=PackageLastModifiedDate&orderByDirection=DESC&size=200";
		}

		return $this->request('GET', $endpoint);
	}

	public function getBuybox($barcode)
	{
		return $this->getBuyboxInformation([(string) $barcode]);
	}

	public function getBuyboxInformation(array $barcodes): ?array
	{
		$barcodes = array_values(array_filter(array_map('strval', $barcodes), static function ($b) {
			return trim($b) !== '';
		}));
		$barcodes = array_slice($barcodes, 0, 10);

		if (empty($barcodes)) {
			return null;
		}

		$endpoint = "product/sellers/{$this->supplierId}/products/buybox-information";

		return $this->request('POST', $endpoint, ['barcodes' => $barcodes]);
	}

	public function getProducts($limit, $page = 0)
	{
		$endpoint = "product/sellers/{$this->supplierId}/products?approved=true&size=" . (int) $limit . '&page=' . (int) $page;

		return $this->request('GET', $endpoint);
	}

	public function createProduct($data)
	{
		$endpoint = "product/sellers/{$this->supplierId}/products";

		return $this->request('POST', $endpoint, $data);
	}

	public function updateProduct($data)
	{
		$endpoint = "product/sellers/{$this->supplierId}/products";

		return $this->request('PUT', $endpoint, $data);
	}

	public function getBatch($data)
	{
		$endpoint = "product/sellers/{$this->supplierId}/products/batch-requests/" . $data;

		return $this->request('GET', $endpoint);
	}

	public function getProduct($barcode)
	{
		$barcode = trim((string) $barcode);

		if ($barcode === '') {
			return [
				'success' => false,
				'httpCode' => 0,
				'message' => 'Barkod boş',
				'body' => null,
			];
		}

		$encoded = rawurlencode($barcode);
		$candidates = [
			"product/sellers/{$this->supplierId}/products?barcode={$encoded}&page=0&size=50",
			"product/sellers/{$this->supplierId}/products/approved?barcode={$encoded}&page=0&size=50",
			"product/sellers/{$this->supplierId}/products?stockCode={$encoded}&page=0&size=50",
		];

		$lastOk = null;
		$lastError = null;

		foreach ($candidates as $endpoint) {
			$result = $this->request('GET', $endpoint);

			if (!is_array($result)) {
				continue;
			}

			if (array_key_exists('success', $result) && $result['success'] === false) {
				$lastError = $result;
				continue;
			}

			$lastOk = $result;

			if ($this->filterResponseHasProduct($result)) {
				return $result;
			}
		}

		if (is_array($lastOk)) {
			return $lastOk;
		}

		return is_array($lastError) ? $lastError : [
			'success' => false,
			'httpCode' => 0,
			'message' => 'Trendyol ürün sorgusu başarısız',
			'body' => null,
		];
	}

	/** @param mixed $result */
	private function filterResponseHasProduct($result): bool
	{
		if (!is_array($result)) {
			return false;
		}

		if (array_key_exists('success', $result) && $result['success'] === false) {
			return false;
		}

		if (isset($result['content']) && is_array($result['content']) && $result['content'] !== []) {
			return isset($result['content'][0]) && is_array($result['content'][0]);
		}

		if (isset($result['barcode']) || isset($result['title']) || isset($result['productName']) || isset($result['variants'])) {
			return true;
		}

		if (isset($result[0]) && is_array($result[0]) && (isset($result[0]['barcode']) || isset($result[0]['title']))) {
			return true;
		}

		return false;
	}

	public function getCategories($name = null)
	{
		$endpoint = 'product/product-categories';

		if ($name !== null && trim((string) $name) !== '') {
			$endpoint .= '?name=' . rawurlencode(trim((string) $name));
		}

		return $this->request('GET', $endpoint);
	}

	public function getBrand($name)
	{
		$endpoint = 'product/brands/by-name?name=' . rawurlencode((string) $name);

		return $this->request('GET', $endpoint);
	}

	public function getAttirupes($id)
	{
		$endpoint = "product/product-categories/{$id}/attributes";

		return $this->request('GET', $endpoint);
	}

	public function getQuestion($page = 0, $size = 25, $status = null)
	{
		$params = [
			'orderByField' => 'CreatedDate',
			'orderByDirection' => 'DESC',
			'size' => min(50, max(1, (int) $size)),
			'page' => max(0, (int) $page),
		];

		if ($status !== null && $status !== '') {
			$params['status'] = $status;
		}

		$endpoint = "qna/sellers/{$this->supplierId}/questions/filter?" . http_build_query($params);

		return $this->request('GET', $endpoint);
	}

	public function postQuestion($id, $text)
	{
		$endpoint = "qna/sellers/{$this->supplierId}/questions/" . (int) $id . '/answers';
		$data = ['text' => $text];

		return $this->request('POST', $endpoint, $data);
	}

	public function updateStockPrice($barcode, $listPrice, $salePrice, $stock, $sku = null)
	{
		$item = [
			'barcode' => (string) $barcode,
			'quantity' => (int) $stock,
			'salePrice' => (float) $salePrice,
			'listPrice' => (float) $listPrice,
		];

		if ($sku !== null && trim((string) $sku) !== '') {
			$item['stockCode'] = (string) $sku;
		}

		$updateData = ['items' => [$item]];
		$endpoint = "inventory/sellers/{$this->supplierId}/products/price-and-inventory";

		return $this->request('POST', $endpoint, $updateData);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function request(string $method, string $endpoint, array $data = []): ?array
	{
		if (!$this->isConfigured()) {
			return [
				'success' => false,
				'httpCode' => 0,
				'message' => 'Trendyol API kimlik bilgileri eksik',
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

		$url = $this->baseUri . $endpoint;
		$auth = base64_encode($this->apiKey . ':' . $this->apiSecret);
		$userAgent = $this->supplierId . ' - SelfIntegration';

		$ch = curl_init();

		if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'], true) && !empty($data)) {
			$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
		}

		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_HTTPHEADER => [
				'Authorization: Basic ' . $auth,
				'Content-Type: application/json',
				'User-Agent: ' . $userAgent,
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
				return ['success' => true, 'httpStatus' => $httpCode];
			}

			return $decoded;
		}

		$message = $error ?: ('HTTP ' . $httpCode);

		if (is_array($decoded)) {
			if (!empty($decoded['message'])) {
				$message = (string) $decoded['message'];
			} elseif (!empty($decoded['errors']) && is_array($decoded['errors'])) {
				$parts = [];

				foreach ($decoded['errors'] as $err) {
					if (is_string($err)) {
						$parts[] = $err;
					} elseif (is_array($err) && isset($err['message'])) {
						$parts[] = (string) $err['message'];
					} elseif (is_array($err) && isset($err['key'])) {
						$parts[] = (string) $err['key'];
					}
				}

				if ($parts !== []) {
					$message = implode('; ', $parts);
				}
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
	 * @param array<int|string, mixed> $attirupe
	 * @return array<int, array<string, mixed>>
	 */
	public function convertAttributes($attirupe): array
	{
		$attributes = [];

		foreach ($attirupe as $attributeId => $attributeValue) {
			if ($attributeValue === '' || $attributeValue === null) {
				continue;
			}

			if (is_numeric($attributeValue)) {
				$attributes[] = [
					'attributeId' => (int) $attributeId,
					'attributeValueId' => (int) $attributeValue,
				];
			} else {
				$attributes[] = [
					'attributeId' => (int) $attributeId,
					'customAttributeValue' => (string) $attributeValue,
				];
			}
		}

		return $attributes;
	}
}
