<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

/**
 * XML ürün içe aktarma / güncelleme servisi.
 * Örnek XML alanları: productCode, barcode, name, brand, category path, variants, image1…
 */
class XmlImportService
{
	public const MATCH_STOCK_CODE = 'stock_code';
	public const MATCH_BARCODE = 'barcode';
	public const MATCH_NAME = 'name';

	public const SETTING_MATCH_KEY = 'XML_IMPORT_MATCH_KEY';
	public const SETTING_FEED_URL = 'XML_IMPORT_FEED_URL';
	public const SETTING_UPDATE_IMAGES = 'XML_IMPORT_UPDATE_IMAGES';
	public const SETTING_LAST_CRON = 'XML_IMPORT_LAST_CRON';

	/**
	 * Cron: kayıtlı feed URL + eşleştirme ayarları ile tam aktarım.
	 *
	 * @return array{success:bool,message:string,stats?:array}
	 */
	public static function importFromSavedSettings(): array
	{
		$feedUrl = trim((string) Settings::get(self::SETTING_FEED_URL));
		$matchKey = self::normalizeMatchKey((string) Settings::get(self::SETTING_MATCH_KEY));
		$updateImages = Settings::get(self::SETTING_UPDATE_IMAGES) === '1';

		if ($feedUrl === '') {
			return self::fail(adminT('XML feed URL is not configured. Save a feed URL in the module settings first.'));
		}

		return self::importFromUrl($feedUrl, $matchKey, $updateImages, 0);
	}

	/** @return array{success:bool,message:string,stats?:array} */
	public static function importFromUpload(array $file, string $matchKey, bool $updateImages = false, int $limit = 0): array
	{
		$error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

		if ($error !== UPLOAD_ERR_OK) {
			return self::fail(adminT('Please upload a valid XML file'));
		}

		$tmp = (string) ($file['tmp_name'] ?? '');

		if ($tmp === '' || !is_uploaded_file($tmp)) {
			return self::fail(adminT('Please upload a valid XML file'));
		}

		$xml = @file_get_contents($tmp);

		if ($xml === false || trim($xml) === '') {
			return self::fail(adminT('XML file is empty or unreadable'));
		}

		return self::importFromString($xml, $matchKey, $updateImages, $limit);
	}

	/** @return array{success:bool,message:string,stats?:array} */
	public static function importFromUrl(string $url, string $matchKey, bool $updateImages = false, int $limit = 0): array
	{
		$url = trim($url);

		if ($url === '' || !preg_match('#^https?://#i', $url)) {
			return self::fail(adminT('Please enter a valid XML URL'));
		}

		if (!Security::isSafeOutboundUrl($url)) {
			return self::fail(adminT('XML URL is not allowed (private or local addresses are blocked)'));
		}

		$xml = self::fetchUrl($url);

		if ($xml === null) {
			return self::fail(adminT('Could not download XML feed'));
		}

		return self::importFromString($xml, $matchKey, $updateImages, $limit);
	}

	/** @return array{success:bool,message:string,stats?:array} */
	public static function importFromString(string $xml, string $matchKey, bool $updateImages = false, int $limit = 0): array
	{
		$matchKey = self::normalizeMatchKey($matchKey);
		$limit = max(0, $limit);
		libxml_use_internal_errors(true);
		$doc = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);

		if ($doc === false) {
			return self::fail(adminT('Invalid XML format'));
		}

		$products = self::extractProductNodes($doc);

		if ($products === []) {
			return self::fail(adminT('No products found in XML'));
		}

		$foundInFeed = count($products);

		if ($limit > 0 && $foundInFeed > $limit) {
			$products = array_slice($products, 0, $limit);
		}

		$stats = [
			'total' => count($products),
			'found_in_feed' => $foundInFeed,
			'limit' => $limit,
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
			'errors' => 0,
			'categories_created' => 0,
			'brands_created' => 0,
			'error_messages' => [],
		];

		$categoryCache = [];
		$brandCache = [];

		foreach ($products as $index => $node) {
			$lineNo = $index + 1;

			try {
				$result = self::importProductNode(
					$node,
					$matchKey,
					$updateImages,
					$categoryCache,
					$brandCache,
					$stats['categories_created'],
					$stats['brands_created'],
					$lineNo
				);

				if (!$result['success']) {
					$stats['errors']++;
					$stats['skipped']++;

					if (count($stats['error_messages']) < 25) {
						$stats['error_messages'][] = $result['message'];
					}

					continue;
				}

				if (!empty($result['created'])) {
					$stats['created']++;
				} else {
					$stats['updated']++;
				}
			} catch (Throwable $e) {
				$stats['errors']++;
				$stats['skipped']++;

				if (count($stats['error_messages']) < 25) {
					$stats['error_messages'][] = adminT('Row') . ' ' . $lineNo . ': ' . $e->getMessage();
				}
			}
		}

		$message = sprintf(
			adminT('XML import finished: %d created, %d updated, %d skipped (%d errors). Categories +%d, brands +%d.'),
			$stats['created'],
			$stats['updated'],
			$stats['skipped'],
			$stats['errors'],
			$stats['categories_created'],
			$stats['brands_created']
		);

		if ($limit > 0) {
			$message .= ' ' . sprintf(
				adminT('Test mode: first %d of %d products.'),
				$stats['total'],
				$foundInFeed
			);
		}

		return [
			'success' => $stats['errors'] === 0 || ($stats['created'] + $stats['updated']) > 0,
			'message' => $message,
			'stats' => $stats,
		];
	}

	public static function normalizeMatchKey(string $key): string
	{
		$key = trim($key);

		if (in_array($key, [self::MATCH_STOCK_CODE, self::MATCH_BARCODE, self::MATCH_NAME], true)) {
			return $key;
		}

		return self::MATCH_STOCK_CODE;
	}

	/** @return list<SimpleXMLElement> */
	private static function extractProductNodes(SimpleXMLElement $doc): array
	{
		$list = [];

		if (isset($doc->product)) {
			foreach ($doc->product as $product) {
				$list[] = $product;
			}

			return $list;
		}

		if (isset($doc->products->product)) {
			foreach ($doc->products->product as $product) {
				$list[] = $product;
			}

			return $list;
		}

		// Kök düğüm tek product olabilir
		if (strtolower($doc->getName()) === 'product') {
			$list[] = $doc;
		}

		return $list;
	}

	/**
	 * @param array<string,int> $categoryCache
	 * @param array<string,int> $brandCache
	 * @return array{success:bool,message:string,created?:bool,id?:int}
	 */
	private static function importProductNode(
		SimpleXMLElement $node,
		string $matchKey,
		bool $updateImages,
		array &$categoryCache,
		array &$brandCache,
		int &$categoriesCreated,
		int &$brandsCreated,
		int $lineNo
	): array {
		$name = self::xmlText($node, 'name');
		$stockCode = self::xmlText($node, 'productCode');

		if ($stockCode === '') {
			$stockCode = self::xmlText($node, 'stock_code');
		}

		$barcode = self::xmlText($node, 'barcode');
		$matchValue = self::matchValue($matchKey, $name, $stockCode, $barcode);

		if ($matchValue === '') {
			return self::fail(adminT('Row') . ' ' . $lineNo . ': ' . adminT('Match field is empty'));
		}

		$idProduct = self::findProductId($matchKey, $matchValue);
		$isNew = $idProduct <= 0;

		$idCategory = self::resolveCategoryTree($node, $categoryCache, $categoriesCreated);
		$idBrand = self::resolveBrand(self::xmlText($node, 'brand'), $brandCache, $brandsCreated);

		if ($idCategory <= 0) {
			return self::fail(adminT('Row') . ' ' . $lineNo . ': ' . adminT('Category could not be resolved'));
		}

		if ($idBrand <= 0) {
			return self::fail(adminT('Row') . ' ' . $lineNo . ': ' . adminT('Brand could not be resolved'));
		}

		$description = self::xmlText($node, 'description');
		$detail = html_entity_decode(self::xmlText($node, 'detail'), ENT_QUOTES | ENT_HTML5, 'UTF-8');

		if ($detail !== '') {
			$description = trim($description . "\n\n" . $detail);
		}

		$price = self::toFloat(self::xmlText($node, 'price'));
		$listPrice = self::toFloat(self::xmlText($node, 'listPrice'));
		$taxRaw = self::toFloat(self::xmlText($node, 'tax'));
		$vat = $taxRaw > 0 && $taxRaw <= 1 ? (int) round($taxRaw * 100) : (int) round($taxRaw);
		$stock = (int) self::toFloat(self::xmlText($node, 'quantity'));
		$desi = self::toFloat(self::xmlText($node, 'desi'));
		$active = self::parseActive(self::xmlText($node, 'active'));

		$variations = self::mapVariants($node);

		if ($variations !== []) {
			$stock = 0;

			foreach ($variations as $variation) {
				$stock += (int) ($variation['stock'] ?? 0);
			}
		}

		$defaultLang = Lang::getDefault();
		$langFields = [
			'product_name' => $name !== '' ? $name : $matchValue,
			'product_link' => '',
			'short_description' => self::xmlText($node, 'description'),
			'description' => $description,
			'meta_title' => $name,
			'meta_description' => mb_substr(strip_tags($description), 0, 255),
		];

		$data = [
			'product_name' => $langFields['product_name'],
			'barcode' => $barcode,
			'stock_code' => $stockCode !== '' ? $stockCode : $matchValue,
			'desi' => $desi,
			'price' => $price,
			'old_price' => $listPrice > $price ? $listPrice : 0,
			'vat' => $vat > 0 ? $vat : 20,
			'stock' => $stock,
			'short_description' => $langFields['short_description'],
			'description' => $langFields['description'],
			'meta_title' => $langFields['meta_title'],
			'meta_description' => $langFields['meta_description'],
			'id_category' => $idCategory,
			'id_brand' => $idBrand,
			'active' => $active,
			'langs' => [
				$defaultLang => $langFields,
			],
		];

		if ($variations !== []) {
			$data['variations'] = $variations;
			$data['has_variations'] = '1';
		} elseif ($isNew) {
			$data['has_variations'] = '0';
		}

		$result = Product::save($data, $idProduct);

		if (empty($result['success'])) {
			return self::fail(adminT('Row') . ' ' . $lineNo . ': ' . ($result['message'] ?? adminT('Save failed')));
		}

		$idSaved = (int) ($result['id'] ?? $idProduct);
		$shouldImportImages = $isNew || $updateImages;

		if ($shouldImportImages && $idSaved > 0) {
			$urls = self::collectImageUrls($node);

			if ($urls !== []) {
				// importImagesFromExcel mevcut görselleri silip yeniden yükler
				Product::importImagesFromExcel($idSaved, implode("\n", $urls));
			}
		}

		$result['created'] = $isNew;

		return $result;
	}

	private static function matchValue(string $matchKey, string $name, string $stockCode, string $barcode): string
	{
		if ($matchKey === self::MATCH_BARCODE) {
			return $barcode;
		}

		if ($matchKey === self::MATCH_NAME) {
			return $name;
		}

		return $stockCode;
	}

	private static function findProductId(string $matchKey, string $value): int
	{
		$value = trim($value);

		if ($value === '') {
			return 0;
		}

		if ($matchKey === self::MATCH_BARCODE) {
			$id = (int) DB::getValue(
				'SELECT id_product FROM products WHERE barcode = ? LIMIT 1',
				[$value]
			);

			if ($id > 0) {
				return $id;
			}

			return (int) DB::getValue(
				'SELECT id_product FROM product_variations WHERE barcode = ? LIMIT 1',
				[$value]
			);
		}

		if ($matchKey === self::MATCH_NAME) {
			return (int) DB::getValue(
				'SELECT id_product FROM products WHERE LOWER(product_name) = LOWER(?) LIMIT 1',
				[$value]
			);
		}

		return (int) DB::getValue(
			'SELECT id_product FROM products WHERE stock_code = ? LIMIT 1',
			[$value]
		);
	}

	/**
	 * @param array<string,int> $cache
	 */
	private static function resolveBrand(string $name, array &$cache, int &$createdCount): int
	{
		$name = trim($name);

		if ($name === '') {
			$name = 'Genel';
		}

		$key = mb_strtolower($name);

		if (isset($cache[$key])) {
			return $cache[$key];
		}

		$id = (int) DB::getValue(
			'SELECT id_brand FROM brands WHERE LOWER(brand_name) = LOWER(?) LIMIT 1',
			[$name]
		);

		if ($id > 0) {
			$cache[$key] = $id;

			return $id;
		}

		$result = Brand::save([
			'brand_name' => $name,
			'active' => 1,
		]);

		if (empty($result['success'])) {
			return 0;
		}

		$id = (int) $result['id'];
		$cache[$key] = $id;
		$createdCount++;

		return $id;
	}

	/**
	 * @param array<string,int> $cache
	 */
	private static function resolveCategoryTree(
		SimpleXMLElement $node,
		array &$cache,
		int &$createdCount
	): int {
		$parts = [];

		foreach (['main_category', 'top_category', 'sub_category'] as $field) {
			$value = self::xmlText($node, $field);

			if ($value !== '') {
				$parts[] = $value;
			}
		}

		if ($parts === []) {
			$raw = self::xmlText($node, 'category');

			if ($raw !== '') {
				$parts = preg_split('/\s*>>>\s*/u', $raw) ?: [];
				$parts = array_values(array_filter(array_map('trim', $parts)));
			}
		}

		if ($parts === []) {
			$parts = ['Genel'];
		}

		$parentId = 0;
		$leafId = 0;

		foreach ($parts as $part) {
			$leafId = self::resolveCategoryUnderParent($part, $parentId, $cache, $createdCount);

			if ($leafId <= 0) {
				return 0;
			}

			$parentId = $leafId;
		}

		return $leafId;
	}

	/**
	 * @param array<string,int> $cache
	 */
	private static function resolveCategoryUnderParent(
		string $name,
		int $parentId,
		array &$cache,
		int &$createdCount
	): int {
		$name = trim($name);

		if ($name === '') {
			return 0;
		}

		$cacheKey = mb_strtolower($parentId . '|' . $name);

		if (isset($cache[$cacheKey])) {
			return $cache[$cacheKey];
		}

		$id = (int) DB::getValue(
			'SELECT id_category FROM categories
			 WHERE LOWER(category_name) = LOWER(?) AND id_parent = ?
			 LIMIT 1',
			[$name, $parentId]
		);

		if ($id > 0) {
			$cache[$cacheKey] = $id;

			return $id;
		}

		$result = Category::save([
			'category_name' => $name,
			'id_parent' => max(0, $parentId),
			'active' => 1,
		]);

		if (empty($result['success'])) {
			return 0;
		}

		$id = (int) $result['id'];
		$cache[$cacheKey] = $id;
		$createdCount++;

		return $id;
	}

	/** @return list<array<string,mixed>> */
	private static function mapVariants(SimpleXMLElement $node): array
	{
		if (!isset($node->variants->variant)) {
			return [];
		}

		$rows = [];
		$i = 0;

		foreach ($node->variants->variant as $variant) {
			$i++;
			$options = [];

			for ($n = 1; $n <= 5; $n++) {
				$optName = self::xmlText($variant, 'name' . $n);
				$optValue = self::xmlText($variant, 'value' . $n);

				if ($optName !== '' && $optValue !== '') {
					$options[$optName] = $optValue;
				}
			}

			if ($options === []) {
				continue;
			}

			$skuParts = array_values($options);
			$barcode = self::xmlText($variant, 'barcode');
			$qty = (int) self::toFloat(self::xmlText($variant, 'quantity'));

			$rows[] = [
				'id' => 0,
				'sku' => mb_substr(implode('-', $skuParts), 0, 64),
				'barcode' => $barcode,
				'options' => $options,
				'stock' => max(0, $qty),
				'active' => true,
			];
		}

		return $rows;
	}

	/** @return list<string> */
	private static function collectImageUrls(SimpleXMLElement $node): array
	{
		$urls = [];

		for ($i = 1; $i <= 12; $i++) {
			$url = self::xmlText($node, 'image' . $i);

			if ($url !== '' && preg_match('#^https?://#i', $url)) {
				$urls[] = $url;
			}
		}

		return array_values(array_unique($urls));
	}

	private static function xmlText(SimpleXMLElement $node, string $field): string
	{
		if (!isset($node->{$field})) {
			return '';
		}

		return trim((string) $node->{$field});
	}

	private static function toFloat(string $value): float
	{
		$value = trim(str_replace([' ', ','], ['', '.'], $value));

		if ($value === '' || !is_numeric($value)) {
			return 0.0;
		}

		return (float) $value;
	}

	private static function parseActive(string $value): int
	{
		$value = trim(mb_strtolower($value));

		if ($value === '' || $value === '0' || $value === 'false' || $value === 'hayir' || $value === 'hayır' || $value === 'passive' || $value === 'pasif') {
			return 0;
		}

		return 1;
	}

	private static function fetchUrl(string $url): ?string
	{
		if (!Security::isSafeOutboundUrl($url)) {
			return null;
		}

		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt_array($ch, [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_MAXREDIRS => 3,
				CURLOPT_CONNECTTIMEOUT => 15,
				CURLOPT_TIMEOUT => 60,
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_USERAGENT => 'FShop-XmlImport/1.0',
				CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
				CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
			]);

			$body = curl_exec($ch);
			$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$finalUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
			curl_close($ch);

			if ($finalUrl !== '' && !Security::isSafeOutboundUrl($finalUrl)) {
				return null;
			}

			if ($body !== false && $code >= 200 && $code < 300) {
				return (string) $body;
			}

			return null;
		}

		$ctx = stream_context_create([
			'http' => [
				'timeout' => 60,
				'header' => "User-Agent: FShop-XmlImport/1.0\r\n",
				'follow_location' => 0,
			],
		]);
		$body = @file_get_contents($url, false, $ctx);

		return $body === false ? null : $body;
	}

	/** @return array{success:bool,message:string} */
	private static function fail(string $message): array
	{
		return ['success' => false, 'message' => $message];
	}
}
