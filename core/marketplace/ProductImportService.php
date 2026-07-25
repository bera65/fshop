<?php

namespace Trendyol;

class ProductImportService
{
	/**
	 * @param array<string, mixed> $options
	 * @return array{success: bool, message: string, id_product?: int, edit_url?: string}
	 */
	public static function import(string $source, string $barcode, array $options = []): array
	{
		$barcode = preg_replace('/\s+/', '', trim($barcode)) ?: '';

		if ($barcode === '') {
			return self::fail('Barkod zorunludur');
		}

		$existing = (int) \DB::getValue(
			'SELECT id_product FROM products WHERE barcode = ? LIMIT 1',
			[$barcode]
		);

		if ($existing > 0) {
			return self::fail('Bu barkoda sahip ürün zaten var (#' . $existing . ')');
		}

		$source = strtolower(trim($source));

		if ($source === 'trendyol') {
			$normalized = self::fetchTrendyol($barcode);
		} elseif ($source === 'fiyattrend') {
			$normalized = self::fetchFiyattrend($barcode);
		} else {
			return self::fail('Geçersiz kaynak');
		}

		if (!$normalized['success']) {
			return $normalized;
		}

		$data = $normalized['data'];

		return self::createProduct($data, $options);
	}

	/** @return array{success: bool, message: string, data?: array<string, mixed>} */
	private static function fetchTrendyol(string $barcode): array
	{
		if (!ProductSyncService::isConfigured()) {
			return self::fail('Trendyol API kimlik bilgileri tanımlı değil');
		}

		$result = ProductSyncService::api()->getProduct($barcode);

		if (ProductSyncService::isApiError($result)) {
			return self::fail((string) ($result['message'] ?? 'Trendyol ürünü bulunamadı'));
		}

		$data = self::parseTrendyolResponse($result, $barcode);

		if ($data === null) {
			$total = isset($result['totalElements']) ? (int) $result['totalElements'] : null;
			$hasEmptyContent = isset($result['content']) && is_array($result['content']) && $result['content'] === [];

			if ($hasEmptyContent || $total === 0) {
				return self::fail(
					'Bu barkod Trendyol satıcı hesabınızda bulunamadı. '
					. 'Yalnızca kendi Trendyol mağazanızdaki ürünler çekilebilir. '
					. 'Başka satıcı ürünleri için kaynak olarak FiyatTrend seçin.'
				);
			}

			return self::fail('Trendyol yanıtı işlenemedi (beklenmeyen JSON şeması)');
		}

		$data = self::enrichTrendyolNames($data);

		if (trim((string) ($data['product_name'] ?? '')) === '') {
			return self::fail('Trendyol ürün adı boş');
		}

		return ['success' => true, 'message' => '', 'data' => $data];
	}

	/** @return array{success: bool, message: string, data?: array<string, mixed>} */
	private static function fetchFiyattrend(string $barcode): array
	{
		$token = trim((string) \Settings::get('TRENDYOL_FIYATTREND_TOKEN'));

		if ($token === '') {
			return self::fail('FiyatTrend API token tanımlı değil (Trendyol modül ayarları)');
		}

		$url = 'https://fiyattrend.com/api.php?token=' . rawurlencode($token) . '&search=' . rawurlencode($barcode);

		if (!function_exists('curl_init')) {
			return self::fail('cURL eklentisi yok');
		}

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_TIMEOUT => 45,
			CURLOPT_HTTPHEADER => ['Accept: application/json'],
		]);
		$response = curl_exec($ch);
		$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($response === false) {
			return self::fail($error ?: 'FiyatTrend bağlantı hatası');
		}

		if ($httpCode < 200 || $httpCode >= 300) {
			return self::fail('FiyatTrend HTTP ' . $httpCode);
		}

		$decoded = json_decode((string) $response, true);

		if (!is_array($decoded) || empty($decoded['success'])) {
			return self::fail('FiyatTrend ürün bulunamadı');
		}

		$data = self::parseFiyattrendResponse($decoded, $barcode);

		if (trim((string) ($data['product_name'] ?? '')) === '') {
			return self::fail('FiyatTrend ürün adı boş');
		}

		return ['success' => true, 'message' => '', 'data' => $data];
	}

	/** @return array<string, mixed>|null */
	private static function parseTrendyolResponse(array $result, string $searchBarcode): ?array
	{
		$content = self::extractTrendyolContentItem($result, $searchBarcode);

		if ($content === null) {
			return null;
		}

		$variant = self::pickTrendyolVariant($content, $searchBarcode);
		$brandInfo = self::extractTrendyolBrandInfo($content);
		$categoryInfo = self::extractTrendyolCategoryInfo($content);

		$title = trim((string) ($content['title'] ?? $content['productName'] ?? ''));
		$description = trim((string) ($content['description'] ?? ''));

		$barcode = $searchBarcode;
		$stockCode = '';
		$price = 0.0;
		$oldPrice = 0.0;
		$stock = 0;

		if (is_array($variant)) {
			$barcode = trim((string) ($variant['barcode'] ?? $barcode));
			$stockCode = trim((string) ($variant['stockCode'] ?? ''));
			$price = (float) ($variant['price']['salePrice'] ?? $variant['salePrice'] ?? 0);
			$listPrice = (float) ($variant['price']['listPrice'] ?? $variant['listPrice'] ?? 0);
			$oldPrice = $listPrice > $price ? $listPrice : 0.0;
			$stock = (int) ($variant['stock']['quantity'] ?? $variant['quantity'] ?? 0);
		} else {
			$barcode = trim((string) ($content['barcode'] ?? $barcode));
			$stockCode = trim((string) ($content['stockCode'] ?? ''));
			$price = (float) ($content['salePrice'] ?? $content['price']['salePrice'] ?? 0);
			$listPrice = (float) ($content['listPrice'] ?? $content['price']['listPrice'] ?? 0);
			$oldPrice = $listPrice > $price ? $listPrice : 0.0;
			$stock = (int) ($content['quantity'] ?? $content['stock']['quantity'] ?? 0);
		}

		$images = [];

		foreach ($content['images'] ?? [] as $img) {
			$url = is_string($img) ? $img : (string) ($img['url'] ?? '');

			if ($url !== '') {
				$images[] = self::normalizeTrendyolImageUrl($url);
			}
		}

		return [
			'product_name' => $title,
			'description' => $description !== '' ? \Security::sanitizeHtml($description) : '',
			'short_description' => mb_substr(trim(strip_tags($description)), 0, 255),
			'brand_name' => $brandInfo['name'],
			'brand_id' => $brandInfo['id'],
			'category_name' => $categoryInfo['name'],
			'category_id' => $categoryInfo['id'],
			'barcode' => $barcode,
			'stock_code' => $stockCode,
			'price' => max(0, $price),
			'old_price' => max(0, $oldPrice),
			'stock' => max(0, $stock),
			'images' => array_values(array_filter($images)),
		];
	}

	/**
	 * V1 (düz alanlar) ve V2 approved (variants[]) yanıtlarından ürün satırını çıkarır.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function extractTrendyolContentItem(array $result, string $searchBarcode): ?array
	{
		$items = [];

		if (isset($result['content']) && is_array($result['content'])) {
			foreach ($result['content'] as $row) {
				if (is_array($row)) {
					$items[] = $row;
				}
			}
		} elseif (isset($result[0]) && is_array($result[0])) {
			foreach ($result as $row) {
				if (is_array($row) && (isset($row['title']) || isset($row['barcode']) || isset($row['variants']))) {
					$items[] = $row;
				}
			}
		} elseif (isset($result['title']) || isset($result['productName']) || isset($result['barcode']) || isset($result['variants'])) {
			$items[] = $result;
		}

		if ($items === []) {
			return null;
		}

		$searchBarcode = trim($searchBarcode);

		foreach ($items as $item) {
			if ($searchBarcode !== '' && trim((string) ($item['barcode'] ?? '')) === $searchBarcode) {
				return $item;
			}

			foreach ($item['variants'] ?? [] as $variant) {
				if (!is_array($variant)) {
					continue;
				}

				if ($searchBarcode !== '' && trim((string) ($variant['barcode'] ?? '')) === $searchBarcode) {
					return $item;
				}
			}
		}

		return $items[0];
	}

	/** @param array<string, mixed> $content */
	private static function pickTrendyolVariant(array $content, string $searchBarcode): ?array
	{
		$variants = $content['variants'] ?? null;

		if (!is_array($variants) || $variants === []) {
			return null;
		}

		foreach ($variants as $variant) {
			if (!is_array($variant)) {
				continue;
			}

			if (trim((string) ($variant['barcode'] ?? '')) === $searchBarcode) {
				return $variant;
			}
		}

		return is_array($variants[0] ?? null) ? $variants[0] : null;
	}

	/**
	 * Trendyol seller API yanıtlarında marka string, nesne veya yalnızca ID olabilir.
	 *
	 * @param array<string, mixed> $content
	 * @return array{id: int, name: string}
	 */
	private static function extractTrendyolBrandInfo(array $content): array
	{
		$id = 0;
		$name = '';

		if (isset($content['brand'])) {
			if (is_array($content['brand'])) {
				$id = (int) ($content['brand']['id'] ?? $content['brand']['brandId'] ?? 0);
				$name = trim((string) ($content['brand']['name'] ?? $content['brand']['brandName'] ?? ''));
			} elseif (is_string($content['brand'])) {
				$name = trim($content['brand']);
			} elseif (is_numeric($content['brand'])) {
				$id = (int) $content['brand'];
			}
		}

		if ($name === '') {
			$name = trim((string) ($content['brandName'] ?? ''));
		}

		if ($id <= 0) {
			$id = (int) ($content['brandId'] ?? 0);
		}

		return ['id' => max(0, $id), 'name' => $name];
	}

	/**
	 * @param array<string, mixed> $content
	 * @return array{id: int, name: string}
	 */
	private static function extractTrendyolCategoryInfo(array $content): array
	{
		$id = 0;
		$name = '';

		if (isset($content['category'])) {
			if (is_array($content['category'])) {
				$id = (int) ($content['category']['id'] ?? $content['category']['categoryId'] ?? 0);
				$name = trim((string) ($content['category']['name'] ?? $content['category']['categoryName'] ?? ''));
			} elseif (is_string($content['category'])) {
				$name = trim($content['category']);
			} elseif (is_numeric($content['category'])) {
				$id = (int) $content['category'];
			}
		}

		if ($name === '') {
			$name = trim((string) ($content['categoryName'] ?? ''));
		}

		if ($id <= 0) {
			$id = (int) ($content['pimCategoryId'] ?? $content['categoryId'] ?? 0);
		}

		return ['id' => max(0, $id), 'name' => $name];
	}

	/** @param array<string, mixed> $data */
	private static function enrichTrendyolNames(array $data): array
	{
		$brandName = trim((string) ($data['brand_name'] ?? ''));
		$categoryName = trim((string) ($data['category_name'] ?? ''));
		$brandId = (int) ($data['brand_id'] ?? 0);
		$categoryId = (int) ($data['category_id'] ?? 0);

		if ($categoryName === '' && $categoryId > 0) {
			$categoryName = self::resolveTrendyolCategoryNameById($categoryId);
		}

		if ($brandName === '' && $brandId > 0) {
			$brandName = self::resolveTrendyolBrandNameById($brandId);
		}

		$data['brand_name'] = $brandName;
		$data['category_name'] = $categoryName;

		return $data;
	}

	private static function resolveTrendyolCategoryNameById(int $categoryId): string
	{
		if ($categoryId <= 0) {
			return '';
		}

		static $cache = [];

		if (isset($cache[$categoryId])) {
			return $cache[$categoryId];
		}

		$result = ProductSyncService::api()->getCategories(null);

		if (ProductSyncService::isApiError($result)) {
			return $cache[$categoryId] = 'Trendyol Kategori #' . $categoryId;
		}

		$tree = [];

		if (isset($result['categories']) && is_array($result['categories'])) {
			$tree = $result['categories'];
		} elseif (isset($result[0]) && is_array($result[0])) {
			$tree = $result;
		} elseif (isset($result['id'])) {
			$tree = [$result];
		}

		$name = self::findTrendyolCategoryNameInTree($tree, $categoryId);

		return $cache[$categoryId] = $name !== '' ? $name : ('Trendyol Kategori #' . $categoryId);
	}

	/**
	 * @param array<int, mixed> $nodes
	 */
	private static function findTrendyolCategoryNameInTree(array $nodes, int $categoryId): string
	{
		foreach ($nodes as $node) {
			if (!is_array($node)) {
				continue;
			}

			$id = (int) ($node['id'] ?? 0);

			if ($id === $categoryId) {
				return trim((string) ($node['name'] ?? ''));
			}

			$subs = $node['subCategories'] ?? null;

			if (is_array($subs) && $subs !== []) {
				$found = self::findTrendyolCategoryNameInTree($subs, $categoryId);

				if ($found !== '') {
					return $found;
				}
			}
		}

		return '';
	}

	private static function resolveTrendyolBrandNameById(int $brandId): string
	{
		if ($brandId <= 0) {
			return '';
		}

		$mapped = trim((string) \Settings::get('TRENDYOL_DEFAULT_BRAND_NAME'));

		if ($mapped !== '' && (int) (\Settings::get('TRENDYOL_DEFAULT_BRAND_ID') ?: 0) === $brandId) {
			return $mapped;
		}

		return 'Trendyol Marka #' . $brandId;
	}

	/** @param array<string, mixed> $payload */
	private static function parseFiyattrendResponse(array $payload, string $barcode): array
	{
		$images = [];
		$imageUrl = trim((string) ($payload['images'] ?? ''));

		if ($imageUrl !== '') {
			$images[] = $imageUrl;
		}

		$detail = trim((string) ($payload['detail'] ?? ''));

		return [
			'product_name' => trim((string) ($payload['productName'] ?? '')),
			'description' => $detail !== '' ? \Security::sanitizeHtml($detail) : '',
			'short_description' => mb_substr(trim(strip_tags((string) ($payload['shortDetail'] ?? ''))), 0, 255),
			'brand_name' => trim((string) ($payload['brand'] ?? '')),
			'category_name' => trim((string) ($payload['category'] ?? '')),
			'barcode' => trim((string) ($payload['barcode'] ?? $barcode)),
			'stock_code' => trim((string) ($payload['stockCode'] ?? '')),
			'price' => 0.0,
			'old_price' => 0.0,
			'stock' => 0,
			'images' => $images,
		];
	}

	private static function normalizeTrendyolImageUrl(string $url): string
	{
		$url = trim($url);

		if ($url === '') {
			return '';
		}

		if (strpos($url, '//') === 0) {
			return 'https:' . $url;
		}

		if (preg_match('#^https?://#i', $url)) {
			return $url;
		}

		return 'https://cdn.dsmcdn.com/' . ltrim($url, '/');
	}

	/**
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $options
	 * @return array{success: bool, message: string, id_product?: int, edit_url?: string}
	 */
	private static function createProduct(array $data, array $options): array
	{
		$categoryMode = (string) ($options['category_mode'] ?? 'create');
		$brandMode = (string) ($options['brand_mode'] ?? 'create');
		$idCategory = (int) ($options['id_category'] ?? 0);
		$idBrand = (int) ($options['id_brand'] ?? 0);

		if ($categoryMode === 'create') {
			$categoryResult = self::resolveOrCreateCategoryId((string) ($data['category_name'] ?? ''));

			if (!$categoryResult['success']) {
				return self::fail((string) $categoryResult['message']);
			}

			$idCategory = (int) ($categoryResult['id'] ?? 0);
		}

		if ($brandMode === 'create') {
			$brandResult = self::resolveOrCreateBrandId((string) ($data['brand_name'] ?? ''));

			if (!$brandResult['success']) {
				return self::fail((string) $brandResult['message']);
			}

			$idBrand = (int) ($brandResult['id'] ?? 0);
		}

		if ($idCategory <= 0) {
			$categoryHint = trim((string) ($data['category_name'] ?? ''));

			if ($categoryHint === '' && !empty($data['category_id'])) {
				return self::fail('Trendyol kategori adı alınamadı (ID: ' . (int) $data['category_id'] . '). Mevcut bir kategori seçin.');
			}

			return self::fail('Geçerli bir kategori seçin veya Trendyol kategori adını kontrol edin');
		}

		if ($idBrand <= 0) {
			$brandHint = trim((string) ($data['brand_name'] ?? ''));

			if ($brandHint === '' && !empty($data['brand_id'])) {
				return self::fail('Trendyol marka adı alınamadı (ID: ' . (int) $data['brand_id'] . '). Mevcut bir marka seçin.');
			}

			return self::fail('Geçerli bir marka seçin veya Trendyol marka adını kontrol edin');
		}

		$saveData = [
			'product_name' => (string) ($data['product_name'] ?? ''),
			'short_description' => (string) ($data['short_description'] ?? ''),
			'description' => (string) ($data['description'] ?? ''),
			'id_category' => $idCategory,
			'id_brand' => $idBrand,
			'barcode' => (string) ($data['barcode'] ?? ''),
			'stock_code' => (string) ($data['stock_code'] ?? ''),
			'price' => (float) ($data['price'] ?? 0),
			'old_price' => (float) ($data['old_price'] ?? 0),
			'stock' => (int) ($data['stock'] ?? 0),
			'active' => 1,
		];

		$result = \Product::save($saveData, 0);

		if (empty($result['success'])) {
			return self::fail((string) ($result['message'] ?? 'Ürün kaydedilemedi'));
		}

		$idProduct = (int) ($result['id'] ?? 0);
		$imageErrors = [];

		foreach ($data['images'] ?? [] as $imageUrl) {
			$imageUrl = trim((string) $imageUrl);

			if ($imageUrl === '') {
				continue;
			}

			$imgResult = \Product::importImageFromUrl($idProduct, $imageUrl);

			if (empty($imgResult['success'])) {
				$imageErrors[] = (string) ($imgResult['message'] ?? $imageUrl);
			}
		}

		$message = 'Ürün oluşturuldu';

		if ($imageErrors !== []) {
			$message .= ' (bazı görseller yüklenemedi: ' . implode('; ', array_slice($imageErrors, 0, 2)) . ')';
		}

		return [
			'success' => true,
			'message' => $message,
			'id_product' => $idProduct,
			'edit_url' => \Admin::url('product?id=' . $idProduct),
		];
	}

	/** @return array{success: bool, id: int, message: string} */
	private static function resolveOrCreateCategoryId(string $name): array
	{
		$name = trim($name);

		if ($name === '') {
			return ['success' => false, 'id' => 0, 'message' => 'Trendyol kategori adı boş'];
		}

		$id = (int) \DB::getValue(
			'SELECT id_category FROM categories WHERE LOWER(category_name) = LOWER(?) LIMIT 1',
			[$name]
		);

		if ($id > 0) {
			return ['success' => true, 'id' => $id, 'message' => ''];
		}

		$result = \Category::save([
			'category_name' => $name,
			'id_parent' => self::getImportCategoryParentId(),
			'active' => 1,
		]);

		if (empty($result['success'])) {
			return [
				'success' => false,
				'id' => 0,
				'message' => (string) ($result['message'] ?? 'Kategori oluşturulamadı: ' . $name),
			];
		}

		return ['success' => true, 'id' => (int) ($result['id'] ?? 0), 'message' => ''];
	}

	/** @return array{success: bool, id: int, message: string} */
	private static function resolveOrCreateBrandId(string $name): array
	{
		$name = trim($name);

		if ($name === '') {
			return ['success' => false, 'id' => 0, 'message' => 'Trendyol marka adı boş'];
		}

		$id = (int) \DB::getValue(
			'SELECT id_brand FROM brands WHERE LOWER(brand_name) = LOWER(?) LIMIT 1',
			[$name]
		);

		if ($id > 0) {
			return ['success' => true, 'id' => $id, 'message' => ''];
		}

		$result = \Brand::save([
			'brand_name' => $name,
			'active' => 1,
		]);

		if (!empty($result['success'])) {
			return ['success' => true, 'id' => (int) ($result['id'] ?? 0), 'message' => ''];
		}

		$slug = \Tools::createSlug($name);

		if ($slug !== '') {
			$result = \Brand::save([
				'brand_name' => $name,
				'brand_link' => $slug . '-' . substr(sha1($name), 0, 6),
				'active' => 1,
			]);

			if (!empty($result['success'])) {
				return ['success' => true, 'id' => (int) ($result['id'] ?? 0), 'message' => ''];
			}
		}

		return [
			'success' => false,
			'id' => 0,
			'message' => (string) ($result['message'] ?? 'Marka oluşturulamadı: ' . $name),
		];
	}

	private static function getImportCategoryParentId(): int
	{
		static $parentId = null;

		if ($parentId !== null) {
			return $parentId;
		}

		$parentId = (int) \DB::getValue(
			'SELECT id_category FROM categories WHERE id_parent = 0 AND active = 1 ORDER BY id_category ASC LIMIT 1'
		);

		return max(0, $parentId);
	}

	/** @return array{success: bool, message: string} */
	private static function fail(string $message): array
	{
		return ['success' => false, 'message' => $message];
	}
}
