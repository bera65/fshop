<?php

class Product
{
	private static bool $schemaReady = false;

	public static function ensureSchema(): void
	{
		if (self::$schemaReady) {
			return;
		}

		self::$schemaReady = true;

		$col = DB::execute("SHOW FULL COLUMNS FROM `products` LIKE 'short_description'");
		$col = $col[0] ?? null;

		if (!$col) {
			DB::execute(
				"ALTER TABLE `products` ADD COLUMN `short_description` varchar(512)
				 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
				 AFTER `product_name`"
			);
		} else {
			$collation = (string) ($col['Collation'] ?? '');

			if ($collation !== '' && stripos($collation, 'utf8mb4') === false) {
				DB::execute(
					"ALTER TABLE `products` MODIFY COLUMN `short_description` varchar(512)
					 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''"
				);
			}
		}

		$metaTitle = DB::execute("SHOW COLUMNS FROM `products` LIKE 'meta_title'");
		if (empty($metaTitle)) {
			DB::execute(
				"ALTER TABLE `products`
				 ADD COLUMN `meta_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' AFTER `short_description`,
				 ADD COLUMN `meta_description` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' AFTER `meta_title`"
			);
		}

		$productVideo = DB::execute("SHOW COLUMNS FROM `products` LIKE 'product_video'");
		if (empty($productVideo)) {
			DB::execute(
				"ALTER TABLE `products` ADD COLUMN `product_video` varchar(256)
				 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
				 AFTER `stock`"
			);
		}

		$dovizCol = DB::execute("SHOW COLUMNS FROM `products` LIKE 'doviz'");
		if (empty($dovizCol)) {
			DB::execute(
				"ALTER TABLE `products` ADD COLUMN `doviz` varchar(16)
				 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'try'
				 AFTER `price`"
			);
		}

		$dovizPrice = DB::execute("SHOW COLUMNS FROM `products` LIKE 'doviz_price'");
		if (empty($dovizPrice)) {
			DB::execute(
				"ALTER TABLE `products` ADD COLUMN `doviz_price` decimal(20,2) NOT NULL DEFAULT 0.00 AFTER `doviz`"
			);
		}

		$dovizOldPrice = DB::execute("SHOW COLUMNS FROM `products` LIKE 'doviz_old_price'");
		if (empty($dovizOldPrice)) {
			DB::execute(
				"ALTER TABLE `products` ADD COLUMN `doviz_old_price` decimal(20,2) NOT NULL DEFAULT 0.00 AFTER `doviz_price`"
			);
		}

		$dovizCost = DB::execute("SHOW COLUMNS FROM `products` LIKE 'doviz_cost'");
		if (empty($dovizCost)) {
			DB::execute(
				"ALTER TABLE `products` ADD COLUMN `doviz_cost` decimal(20,2) NOT NULL DEFAULT 0.00 AFTER `doviz_old_price`"
			);
		}

		$cargoDay = DB::execute("SHOW COLUMNS FROM `products` LIKE 'cargo_day'");
		if (empty($cargoDay)) {
			DB::execute(
				"ALTER TABLE `products` ADD COLUMN `cargo_day` int(3) NOT NULL DEFAULT 0 AFTER `stock`"
			);
		}

		$stockEmptyAt = DB::execute("SHOW COLUMNS FROM `products` LIKE 'stock_empty_at'");
		if (empty($stockEmptyAt)) {
			DB::execute(
				'ALTER TABLE `products` ADD COLUMN `stock_empty_at` datetime DEFAULT NULL AFTER `stock`'
			);
		}

		$label = DB::execute("SHOW COLUMNS FROM `products` LIKE 'label'");
		if (empty($label)) {
			DB::execute(
				"ALTER TABLE `products` ADD COLUMN `label` varchar(128)
				 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
				 AFTER `cargo_day`"
			);
		}

		$packOverride = DB::execute("SHOW COLUMNS FROM `products` LIKE 'pack_price_override'");
		if (empty($packOverride)) {
			DB::execute(
				"ALTER TABLE `products` ADD COLUMN `pack_price_override` decimal(20,2) NULL DEFAULT NULL AFTER `price`"
			);
		}

		$saleUnit = DB::execute("SHOW COLUMNS FROM `products` LIKE 'sale_unit'");
		if (empty($saleUnit)) {
			DB::execute(
				"ALTER TABLE `products`
				 ADD COLUMN `sale_unit` varchar(8) NOT NULL DEFAULT 'piece' AFTER `stock`,
				 ADD COLUMN `sale_qty_min` decimal(12,3) NOT NULL DEFAULT 1.000 AFTER `sale_unit`,
				 ADD COLUMN `sale_qty_step` decimal(12,3) NOT NULL DEFAULT 1.000 AFTER `sale_qty_min`"
			);
		}

		$stockCol = DB::execute("SHOW COLUMNS FROM `products` LIKE 'stock'");
		$stockType = strtolower((string) ($stockCol[0]['Type'] ?? ''));
		if ($stockType !== '' && strpos($stockType, 'decimal') === false) {
			DB::execute(
				'ALTER TABLE `products` MODIFY COLUMN `stock` decimal(12,3) NOT NULL DEFAULT 100.000'
			);
		}

		VirtualProduct::ensureSchema();
		ProductVariation::ensureSchema();

		if (!class_exists('Tax', false)) {
			require_once dirname(__DIR__) . '/core/Tax.php';
		}

		Tax::ensureSchema();
		Supplier::ensureSchema();
	}

	public static function isPackProduct(array $product): bool
	{
		return ($product['product_type'] ?? 'physical') === 'pack';
	}

	/** @return class-string|null */
	private static function productSetServiceClass(): ?string
	{
		$file = dirname(__DIR__) . '/modules/product-set/lib/ProductSetService.php';

		if (!is_file($file)) {
			return null;
		}

		require_once $file;

		return class_exists('ProductSetService', false) ? 'ProductSetService' : null;
	}

	public static function getLink(array $row): string
	{
		global $domain;

		return $domain
			. $row['category_link'] . '/'
			. $row['product_link'].'-'
			. (int) $row['id_product'];
	}

	public static function getImageUrl(?int $idImage): string
	{
		global $domain;

		if ($idImage) {
			$relative = 'img/products/' . $idImage . '.jpg';
			if (file_exists(dirname(__DIR__) . '/' . $relative)) {
				return $domain . $relative;
			}
		}

		return $domain . 'templates/default/img/favicon.png';
	}

	public static function getYoutubeEmbedUrl(string $url): string
	{
		$videoId = self::extractYoutubeId($url);

		if ($videoId === '') {
			return '';
		}

		return 'https://www.youtube-nocookie.com/embed/' . $videoId;
	}

	public static function extractYoutubeId(string $url): string
	{
		$url = trim($url);

		if ($url === '') {
			return '';
		}

		if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([\w-]{11})~i', $url, $matches)) {
			return $matches[1];
		}

		if (preg_match('/^[\w-]{11}$/', $url)) {
			return $url;
		}

		return '';
	}

	public static function getStock(array $product, int $idVariation = 0): float
	{
		$idProduct = (int) ($product['id_product'] ?? 0);

		if (self::isPackProduct($product)) {
			$svc = self::productSetServiceClass();

			if ($svc === null || !class_exists('Module', false) || !Module::isEnabled('product-set')) {
				return 0.0;
			}

			return max(0.0, (float) $svc::getAvailableStock($idProduct));
		}

		if ($idVariation > 0) {
			$variation = ProductVariation::getById($idVariation);

			if (!$variation || (int) $variation['id_product'] !== $idProduct || (int) $variation['active'] !== 1) {
				return 0.0;
			}

			return max(0.0, (float) $variation['stock']);
		}

		if ($idProduct > 0 && ProductVariation::hasVariations($idProduct)) {
			return (float) ProductVariation::getTotalStock($idProduct);
		}

		if (VirtualProduct::isVirtualProduct($product)) {
			$kind = VirtualProduct::getKind($product);

			if ($kind === 'license') {
				return (float) VirtualProduct::countAvailableLicenses((int) ($product['id_product'] ?? 0));
			}

			$stock = (float) ($product['stock'] ?? 0);

			return $stock > 0 ? $stock : 999999.0;
		}

		return max(0.0, (float) ($product['stock'] ?? 0));
	}

	public static function isInStock(array $product, float $qty = 1, int $idVariation = 0): bool
	{
		$need = max(0.001, $qty);

		return self::getStock($product, $idVariation) + 0.0001 >= $need;
	}

	public static function decreaseStock(int $idProduct, float $qty, int $idVariation = 0): bool
	{
		global $db;

		$qty = round($qty, 3);

		if ($qty <= 0) {
			return false;
		}

		$product = self::getByIdAdmin($idProduct);

		if ($product && self::isPackProduct($product)) {
			// Set ürünü sepete parçalanır; stok alt ürünlerden düşülür.
			return true;
		}

		if ($idVariation > 0) {
			return ProductVariation::decreaseStock($idVariation, $qty, $idProduct);
		}

		if (ProductVariation::hasVariations($idProduct)) {
			return false;
		}

		if ($product && VirtualProduct::isVirtualProduct($product)) {
			$kind = VirtualProduct::getKind($product);

			if ($kind === 'license') {
				return VirtualProduct::countAvailableLicenses($idProduct) >= $qty;
			}

			$stock = (float) ($product['stock'] ?? 0);
			if ($stock <= 0) {
				return true;
			}
		}

		$stmt = $db->prepare(
			'UPDATE products SET stock = stock - ? WHERE id_product = ? AND stock >= ?'
		);
		$stmt->execute([$qty, $idProduct, $qty]);

		return $stmt->rowCount() > 0;
	}

	public static function increaseStock(int $idProduct, float $qty, int $idVariation = 0): void
	{
		$qty = round($qty, 3);

		if ($qty <= 0) {
			return;
		}

		$product = self::getByIdAdmin($idProduct);
		if ($product && self::isPackProduct($product)) {
			return;
		}

		if ($idVariation > 0) {
			ProductVariation::increaseStock($idVariation, $qty, $idProduct);

			return;
		}

		DB::execute(
			'UPDATE products SET stock = stock + ? WHERE id_product = ?',
			[$qty, $idProduct]
		);
	}

	private static ?bool $reviewsEnabled = null;

	private static function reviewsEnabled(): bool
	{
		if (self::$reviewsEnabled === null) {
			$active = DB::getValue(
				"SELECT active FROM modules WHERE name = 'reviews' AND installed = 1 LIMIT 1"
			);

			self::$reviewsEnabled = $active !== false
				&& (int) $active === 1
				&& !empty(DB::execute("SHOW TABLES LIKE 'product_reviews'"));
		}

		return self::$reviewsEnabled;
	}

	/** Liste sayfaları için: enrich + toplu yorum puanı (tek sorgu, N+1 yok) */
	public static function enrichList(array $rows): array
	{
		$rows = array_map([self::class, 'enrich'], $rows);

		if (!$rows || !self::reviewsEnabled()) {
			return self::attachVariationFlags($rows ?: []);
		}

		$ids = array_map('intval', array_column($rows, 'id_product'));
		$placeholders = implode(',', array_fill(0, count($ids), '?'));

		$stats = DB::execute(
			"SELECT id_product, AVG(rating) AS avg_rating, COUNT(*) AS review_count
			 FROM product_reviews
			 WHERE active = 1 AND id_product IN ({$placeholders})
			 GROUP BY id_product",
			$ids
		) ?: [];

		$map = [];
		foreach ($stats as $stat) {
			$map[(int) $stat['id_product']] = $stat;
		}

		foreach ($rows as &$row) {
			$stat = $map[(int) $row['id_product']] ?? null;
			$row['rating'] = $stat ? round((float) $stat['avg_rating'], 1) : 0.0;
			$row['rating_label'] = number_format($row['rating'], 1, ',', '');
			$row['review_count'] = $stat ? (int) $stat['review_count'] : 0;
		}
		unset($row);

		return self::attachVariationFlags($rows);
	}

	/** Liste kartlarında varyasyonlu ürün bayrağı (tek sorgu) */
	public static function attachVariationFlags(array $rows): array
	{
		if ($rows === []) {
			return $rows;
		}

		$ids = array_values(array_unique(array_map('intval', array_column($rows, 'id_product'))));

		if ($ids === []) {
			return $rows;
		}

		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		$withVariations = DB::execute(
			'SELECT DISTINCT id_product FROM product_variations WHERE active = 1 AND id_product IN (' . $placeholders . ')',
			$ids
		) ?: [];
		$map = [];

		foreach ($withVariations as $entry) {
			$map[(int) $entry['id_product']] = true;
		}

		foreach ($rows as &$row) {
			$row['has_variations'] = !empty($map[(int) ($row['id_product'] ?? 0)]);
		}
		unset($row);

		return $rows;
	}

	public static function getQuickView(int $idProduct): ?array
	{
		$product = self::getById($idProduct);

		if (!$product) {
			return null;
		}

		$variationData = ProductVariation::getForStorefront($idProduct, (float) $product['price']);
		$optionData = ProductOption::getForStorefront($idProduct);
		$shortDescription = trim(strip_tags((string) ($product['short_description'] ?? '')));

		if ($shortDescription === '') {
			$shortDescription = trim(strip_tags((string) ($product['description'] ?? '')));
			if (Tools::strlen($shortDescription) > 200) {
				$shortDescription = mb_substr($shortDescription, 0, 197, 'UTF-8') . '...';
			}
		}

		return [
			'id_product' => (int) $product['id_product'],
			'product_name' => (string) $product['product_name'],
			'url' => (string) $product['url'],
			'image_url' => (string) $product['image_url'],
			'short_description' => $shortDescription,
			'price' => (float) $product['price'],
			'old_price' => (float) ($product['old_price'] ?? 0),
			'price_formatted' => (string) $product['price_formatted'],
			'old_price_formatted' => (string) ($product['old_price_formatted'] ?? ''),
			'has_discount' => !empty($product['has_discount']),
			'in_stock' => !empty($product['in_stock']),
			'stock' => (float) ($product['stock'] ?? 0),
			'category_name' => (string) ($product['category_name'] ?? ''),
			'has_variations' => !empty($variationData['has_variations']),
			'variation_groups' => $variationData['groups'],
			'variation_items' => $variationData['items'],
			'has_options' => !empty($optionData['has_options']),
			'option_groups' => $optionData['groups'],
			'sale_unit' => SaleUnit::normalize((string) ($product['sale_unit'] ?? SaleUnit::PIECE)),
		];
	}

	public static function enrich(array $row): array
	{
		$row['url'] = self::getLink($row);
		$row['image_url'] = self::getImageUrl(isset($row['id_image']) ? (int) $row['id_image'] : null);
		$row['is_pack'] = self::isPackProduct($row);
		$row['rating'] = (float) ($row['rating'] ?? 0);
		$row['review_count'] = (int) ($row['review_count'] ?? 0);
		$row['label'] = trim((string) ($row['label'] ?? ''));
		$row['is_virtual'] = VirtualProduct::isVirtualProduct($row);
		$row['virtual_kind'] = VirtualProduct::getKind($row);
		$row['virtual_kind_label'] = VirtualProduct::getKindLabel($row['virtual_kind']);
		$row['sale_unit'] = SaleUnit::normalize((string) ($row['sale_unit'] ?? SaleUnit::PIECE));
		$row['sale_qty_min'] = SaleUnit::getMin($row);
		$row['sale_qty_step'] = SaleUnit::getStep($row);
		$row['is_m2'] = SaleUnit::isM2($row);
		$row['price_unit_suffix'] = SaleUnit::priceSuffix($row['sale_unit']);

		if (!empty($row['is_pack'])) {
			$svc = self::productSetServiceClass();
			$idPack = (int) ($row['id_product'] ?? 0);

			if ($svc !== null && class_exists('Module', false) && Module::isEnabled('product-set') && $idPack > 0) {
				$pricing = $svc::getPricing($idPack, $row);
				$row['price'] = (float) $pricing['price'];
				$row['old_price'] = (float) $pricing['old_price'];
				$row['pack_components_total'] = (float) $pricing['components_total'];
				$row['pack_has_override'] = !empty($pricing['has_override']);
				$row['stock'] = (float) $svc::getAvailableStock($idPack);
			} else {
				$row['price'] = 0.0;
				$row['stock'] = 0.0;
			}
		} else {
			$row['stock'] = (float) ($row['stock'] ?? 0);
		}

		$row['in_stock'] = self::isInStock($row);
		$row['price'] = class_exists('GroupPricing', false)
			? GroupPricing::apply((float) $row['price'])
			: (float) $row['price'];
		$row['price_formatted'] = Tools::displayPrice((float) $row['price']) . $row['price_unit_suffix'];
		$row['old_price'] = (float) ($row['old_price'] ?? 0);
		$row['has_discount'] = $row['old_price'] > (float) $row['price'];

		if ($row['has_discount']) {
			$row['old_price_formatted'] = Tools::displayPrice($row['old_price']) . $row['price_unit_suffix'];
		}

		$listExcerpt = trim(strip_tags((string) ($row['short_description'] ?? '')));
		if ($listExcerpt === '') {
			$listExcerpt = trim((string) ($row['category_name'] ?? ''));
		}
		$row['list_excerpt'] = $listExcerpt;

		if (class_exists('Lang', false)) {
			$row = Lang::applyProduct($row);
			if (!empty($row['product_name'])) {
				$row['url'] = self::getLink($row);
			}
		}

		return $row;
	}

	public static function getImages(int $idProduct): array
	{
		$rows = DB::execute(
			'SELECT id_image, cover FROM images WHERE id_product = ? ORDER BY cover DESC, id_image ASC',
			[$idProduct]
		);

		if (!$rows) {
			return [];
		}

		$images = [];

		foreach ($rows as $row) {
			$images[] = [
				'id_image' => (int) $row['id_image'],
				'url' => self::getImageUrl((int) $row['id_image']),
				'cover' => (int) $row['cover'],
			];
		}

		return $images;
	}

	public static function getById(int $id): ?array
	{
		$rows = DB::execute(
			'SELECT p.*, b.brand_name, b.brand_link, c.category_name, c.category_link, i.id_image
			FROM products p
			INNER JOIN brands b ON p.id_brand = b.id_brand
			INNER JOIN categories c ON p.id_category = c.id_category
			LEFT JOIN images i ON p.id_product = i.id_product AND i.cover = 1
			WHERE p.id_product = ? AND p.active = 1
			LIMIT 1',
			[$id]
		);

		if (!$rows || !isset($rows[0])) {
			return null;
		}

		return self::enrich($rows[0]);
	}

	public static function getActiveList(
		?int $idCategory = null,
		int $limit = 24,
		int $offset = 0,
		string $sort = 'newest',
		?int $idBrand = null
	): array {
		$sql = 'SELECT p.*, b.brand_name, b.brand_link, c.category_name, c.category_link, i.id_image
			FROM products p
			INNER JOIN brands b ON p.id_brand = b.id_brand
			INNER JOIN categories c ON p.id_category = c.id_category
			LEFT JOIN images i ON p.id_product = i.id_product AND i.cover = 1
			WHERE p.active = 1';
		$params = [];

		if ($idCategory) {
			$sql .= ' AND p.id_category = ?';
			$params[] = $idCategory;
		}

		if ($idBrand) {
			$sql .= ' AND p.id_brand = ?';
			$params[] = $idBrand;
		}

		$sql .= ' ORDER BY ' . Pagination::resolveSort($sort);
		$sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

		$rows = DB::execute($sql, $params);

		if (!$rows) {
			return [];
		}

		return self::enrichList($rows);
	}

	public static function getDiscountedList(int $limit = 24, int $offset = 0, string $sort = 'discount'): array
	{
		$sql = 'SELECT p.*, b.brand_name, b.brand_link, c.category_name, c.category_link, i.id_image
			FROM products p
			INNER JOIN brands b ON p.id_brand = b.id_brand
			INNER JOIN categories c ON p.id_category = c.id_category
			LEFT JOIN images i ON p.id_product = i.id_product AND i.cover = 1
			WHERE p.active = 1 AND p.old_price > p.price
			ORDER BY ' . Pagination::resolveSort($sort) . '
			LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

		$rows = DB::execute($sql);

		if (!$rows) {
			return [];
		}

		return self::enrichList($rows);
	}

	/** Yorum puanına göre öne çıkan ürünler; yorum yoksa en yeniler */
	public static function getTopRatedList(int $limit = 8, int $offset = 0): array
	{
		self::ensureSchema();

		$limit = max(1, min(48, $limit));
		$offset = max(0, $offset);

		if (self::reviewsEnabled()) {
			$sql = 'SELECT p.*, b.brand_name, b.brand_link, c.category_name, c.category_link, i.id_image,
				COALESCE(rs.avg_rating, 0) AS avg_rating, COALESCE(rs.review_count, 0) AS review_count
				FROM products p
				INNER JOIN brands b ON p.id_brand = b.id_brand
				INNER JOIN categories c ON p.id_category = c.id_category
				LEFT JOIN images i ON p.id_product = i.id_product AND i.cover = 1
				LEFT JOIN (
					SELECT id_product, AVG(rating) AS avg_rating, COUNT(*) AS review_count
					FROM product_reviews WHERE active = 1 GROUP BY id_product
				) rs ON rs.id_product = p.id_product
				WHERE p.active = 1
				ORDER BY (COALESCE(rs.review_count, 0) > 0) DESC, rs.avg_rating DESC, rs.review_count DESC, p.id_product DESC
				LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

			$rows = DB::execute($sql) ?: [];

			return self::enrichList($rows);
		}

		return self::getActiveList(null, $limit, $offset, 'newest');
	}

	/**
	 * @param int[] $categoryIds
	 * @param int[] $excludeProductIds
	 */
	public static function getListInCategories(
		array $categoryIds,
		array $excludeProductIds = [],
		int $limit = 12,
		string $sort = 'newest'
	): array {
		$categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
		if ($categoryIds === []) {
			return [];
		}

		$limit = max(1, min(48, $limit));
		$excludeProductIds = array_values(array_unique(array_filter(array_map('intval', $excludeProductIds))));

		$catPlaceholders = implode(',', array_fill(0, count($categoryIds), '?'));
		$sql = 'SELECT p.*, b.brand_name, b.brand_link, c.category_name, c.category_link, i.id_image
			FROM products p
			INNER JOIN brands b ON p.id_brand = b.id_brand
			INNER JOIN categories c ON p.id_category = c.id_category
			LEFT JOIN images i ON p.id_product = i.id_product AND i.cover = 1
			WHERE p.active = 1 AND p.id_category IN (' . $catPlaceholders . ')';
		$params = $categoryIds;

		if ($excludeProductIds !== []) {
			$excludePlaceholders = implode(',', array_fill(0, count($excludeProductIds), '?'));
			$sql .= ' AND p.id_product NOT IN (' . $excludePlaceholders . ')';
			$params = array_merge($params, $excludeProductIds);
		}

		$sql .= ' ORDER BY ' . Pagination::resolveSort($sort);
		$sql .= ' LIMIT ' . (int) $limit;

		$rows = DB::execute($sql, $params);

		if (!$rows) {
			return [];
		}

		return self::enrichList($rows);
	}

	/**
	 * @return array{products: array<int, array>, title: string, source: string}
	 */
	public static function getRelatedForProduct(array $product, int $limit = 4): array
	{
		$idProduct = (int) ($product['id_product'] ?? 0);
		$idCategory = (int) ($product['id_category'] ?? 0);
		$idBrand = (int) ($product['id_brand'] ?? 0);
		$limit = max(1, min(12, $limit));
		$exclude = [$idProduct];
		$found = [];

		if ($idCategory > 0) {
			$items = self::getListInCategories([$idCategory], $exclude, $limit);

			foreach ($items as $item) {
				$found[] = $item;
				$exclude[] = (int) $item['id_product'];

				if (count($found) >= $limit) {
					return [
						'products' => $found,
						'title' => translate('Other Products') . ' — ' . (string) ($product['category_name'] ?? ''),
						'source' => 'category',
					];
				}
			}
		}

		if ($idBrand > 0 && count($found) < $limit) {
			$need = $limit - count($found);
			$items = self::getActiveList(null, $need + count($exclude) + 2, 0, 'newest', $idBrand);

			foreach ($items as $item) {
				$itemId = (int) ($item['id_product'] ?? 0);

				if ($itemId <= 0 || in_array($itemId, $exclude, true)) {
					continue;
				}

				$found[] = $item;
				$exclude[] = $itemId;

				if (count($found) >= $limit) {
					return [
						'products' => $found,
						'title' => translate('Other Products') . ' — ' . (string) ($product['brand_name'] ?? ''),
						'source' => 'brand',
					];
				}
			}
		}

		if (count($found) < $limit) {
			$items = self::getActiveList(null, $limit + count($exclude) + 4, 0, 'newest');

			foreach ($items as $item) {
				$itemId = (int) ($item['id_product'] ?? 0);

				if ($itemId <= 0 || in_array($itemId, $exclude, true)) {
					continue;
				}

				$found[] = $item;

				if (count($found) >= $limit) {
					break;
				}
			}
		}

		$title = translate('Other Products');

		if (count($found) > 0) {
			if ($idCategory > 0 && ($found[0]['id_category'] ?? 0) == $idCategory) {
				$title .= ' — ' . (string) ($product['category_name'] ?? '');
			} elseif ($idBrand > 0) {
				$title .= ' — ' . (string) ($product['brand_name'] ?? '');
			} else {
				$title = translate('Recommended products');
			}
		}

		return [
			'products' => $found,
			'title' => $title,
			'source' => count($found) > 0 ? 'store' : 'none',
		];
	}

	public static function search(string $query, int $limit = 24, int $offset = 0, string $sort = 'newest'): array
	{
		$query = trim($query);

		if ($query === '' || Tools::strlen($query) < 2) {
			return [];
		}

		$like = '%' . $query . '%';

		$rows = DB::execute(
			'SELECT p.*, b.brand_name, b.brand_link, c.category_name, c.category_link, i.id_image
			FROM products p
			INNER JOIN brands b ON p.id_brand = b.id_brand
			INNER JOIN categories c ON p.id_category = c.id_category
			LEFT JOIN images i ON p.id_product = i.id_product AND i.cover = 1
			WHERE p.active = 1
			AND (p.product_name LIKE ? OR p.short_description LIKE ? OR p.description LIKE ? OR b.brand_name LIKE ?)
			ORDER BY ' . Pagination::resolveSort($sort) . '
			LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
			[$like, $like, $like, $like]
		);

		if (!$rows) {
			return [];
		}

		return self::enrichList($rows);
	}

	public static function countActive(?int $idCategory = null, ?int $idBrand = null): int
	{
		if ($idCategory) {
			$filter = CatalogFilter::forCategory($idCategory);
		} else {
			$filter = new CatalogFilter();
		}

		$filter->brandId = max(0, (int) $idBrand);

		return self::countFiltered($filter);
	}

	public static function countFiltered(CatalogFilter $filter): int
	{
		$sql = 'SELECT COUNT(*) FROM products p WHERE p.active = 1';
		$params = [];
		$filter->appendProductSql($sql, $params);
		self::runFilterHook($sql, $params, $filter);

		return (int) DB::getValue($sql, $params);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function getFilteredList(
		CatalogFilter $filter,
		int $limit = 24,
		int $offset = 0,
		string $sort = 'newest'
	): array {
		$sql = 'SELECT p.*, b.brand_name, b.brand_link, c.category_name, c.category_link, i.id_image
			FROM products p
			INNER JOIN brands b ON p.id_brand = b.id_brand
			INNER JOIN categories c ON p.id_category = c.id_category
			LEFT JOIN images i ON p.id_product = i.id_product AND i.cover = 1
			WHERE p.active = 1';
		$params = [];
		$filter->appendProductSql($sql, $params);
		self::runFilterHook($sql, $params, $filter);

		$sql .= ' ORDER BY ' . Pagination::resolveSort($sort);
		$sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

		$rows = DB::execute($sql, $params);

		if (!$rows) {
			return [];
		}

		return self::enrichList($rows);
	}

	/** @param array<int, scalar> $params */
	private static function runFilterHook(string &$sql, array &$params, CatalogFilter $filter): void
	{
		if (!class_exists('Module', false)) {
			return;
		}

		Module::runHook('product.filter.sql', [&$sql, &$params, [
			'filter' => $filter,
			'categoryIds' => $filter->getCategoryIds(),
			'idBrand' => $filter->brandId > 0 ? $filter->brandId : null,
			'priceMin' => $filter->priceMin,
			'priceMax' => $filter->priceMax,
		]]);
	}

	/**
	 * @param int[] $categoryIds
	 * @return array{min: float, max: float}
	 */
	public static function getPriceRangeForCategories(array $categoryIds): array
	{
		$categoryIds = array_values(array_filter(array_map('intval', $categoryIds)));

		if ($categoryIds === []) {
			return ['min' => 0.0, 'max' => 0.0];
		}

		$placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
		$row = DB::execute(
			'SELECT MIN(p.price) AS min_price, MAX(p.price) AS max_price
			 FROM products p
			 WHERE p.active = 1 AND p.id_category IN (' . $placeholders . ')',
			$categoryIds
		);

		$stats = is_array($row) && isset($row[0]) ? $row[0] : null;

		return [
			'min' => (float) ($stats['min_price'] ?? 0),
			'max' => (float) ($stats['max_price'] ?? 0),
		];
	}

	public static function countDiscounted(): int
	{
		return (int) DB::getValue(
			'SELECT COUNT(*) FROM products WHERE active = 1 AND old_price > price'
		);
	}

	public static function countSearch(string $query): int
	{
		$query = trim($query);

		if ($query === '' || Tools::strlen($query) < 2) {
			return 0;
		}

		$like = '%' . $query . '%';

		return (int) DB::getValue(
			'SELECT COUNT(*)
			FROM products p
			INNER JOIN brands b ON p.id_brand = b.id_brand
			WHERE p.active = 1
			AND (p.product_name LIKE ? OR p.short_description LIKE ? OR p.description LIKE ? OR b.brand_name LIKE ?)',
			[$like, $like, $like, $like]
		);
	}

	public static function getAdminList(
		string $query = '',
		int $idCategory = 0,
		int $idBrand = 0,
		int $activeFilter = -1,
		int $limit = 30,
		int $offset = 0,
		int $idSupplier = 0
	): array {
		Supplier::ensureSchema();

		$sql = 'SELECT p.*, b.brand_name, c.category_name, s.supplier_name, i.id_image
			FROM products p
			INNER JOIN brands b ON p.id_brand = b.id_brand
			INNER JOIN categories c ON p.id_category = c.id_category
			LEFT JOIN suppliers s ON p.id_supplier = s.id_supplier
			LEFT JOIN images i ON p.id_product = i.id_product AND i.cover = 1
			WHERE 1=1';
		$params = [];

		if ($query !== '') {
			$like = '%' . $query . '%';
			$sql .= ' AND (p.product_name LIKE ? OR p.product_link LIKE ?)';
			$params[] = $like;
			$params[] = $like;
		}

		if ($idCategory > 0) {
			$sql .= ' AND p.id_category = ?';
			$params[] = $idCategory;
		}

		if ($idBrand > 0) {
			$sql .= ' AND p.id_brand = ?';
			$params[] = $idBrand;
		}

		if ($idSupplier > 0) {
			$sql .= ' AND p.id_supplier = ?';
			$params[] = $idSupplier;
		}

		if ($activeFilter >= 0) {
			$sql .= ' AND p.active = ?';
			$params[] = $activeFilter;
		}

		$sql .= ' ORDER BY p.id_product DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

		$rows = DB::execute($sql, $params) ?: [];

		foreach ($rows as &$row) {
			$row['price_formatted'] = Tools::displayPrice((float) $row['price']);
			$row['image_url'] = self::getImageUrl(isset($row['id_image']) ? (int) $row['id_image'] : null);
			$row['active_label'] = (int) $row['active'] === 1 ? 'Aktif' : 'Pasif';
			$row['supplier_name'] = (string) ($row['supplier_name'] ?? '');
		}
		unset($row);

		return $rows;
	}

	public static function countAdmin(
		string $query = '',
		int $idCategory = 0,
		int $idBrand = 0,
		int $activeFilter = -1,
		int $idSupplier = 0
	): int {
		Supplier::ensureSchema();

		$sql = 'SELECT COUNT(*) FROM products p WHERE 1=1';
		$params = [];

		if ($query !== '') {
			$like = '%' . $query . '%';
			$sql .= ' AND (p.product_name LIKE ? OR p.product_link LIKE ?)';
			$params[] = $like;
			$params[] = $like;
		}

		if ($idCategory > 0) {
			$sql .= ' AND p.id_category = ?';
			$params[] = $idCategory;
		}

		if ($idBrand > 0) {
			$sql .= ' AND p.id_brand = ?';
			$params[] = $idBrand;
		}

		if ($idSupplier > 0) {
			$sql .= ' AND p.id_supplier = ?';
			$params[] = $idSupplier;
		}

		if ($activeFilter >= 0) {
			$sql .= ' AND p.active = ?';
			$params[] = $activeFilter;
		}

		return (int) DB::getValue($sql, $params);
	}

	public static function getByIdAdmin(int $id): ?array
	{
		$rows = DB::execute(
			'SELECT p.*, b.brand_name, b.brand_link, c.category_name, c.category_link
			FROM products p
			INNER JOIN brands b ON p.id_brand = b.id_brand
			INNER JOIN categories c ON p.id_category = c.id_category
			WHERE p.id_product = ?
			LIMIT 1',
			[$id]
		);

		if (!$rows || !isset($rows[0])) {
			return null;
		}

		$product = $rows[0];
		$product['images'] = self::getImages($id);

		return $product;
	}

	public static function isLinkUnique(string $link, int $excludeId = 0, ?string $lang = null): bool
	{
		$lang = $lang ?: Lang::getDefault();

		if ($lang === Lang::getDefault()) {
			$sql = 'SELECT COUNT(*) FROM products WHERE product_link = ?';
			$params = [$link];

			if ($excludeId > 0) {
				$sql .= ' AND id_product != ?';
				$params[] = $excludeId;
			}

			if ((int) DB::getValue($sql, $params) > 0) {
				return false;
			}
		}

		$sql = 'SELECT COUNT(*) FROM product_lang WHERE product_link = ? AND lang = ?';
		$params = [$link, $lang];

		if ($excludeId > 0) {
			$sql .= ' AND id_product != ?';
			$params[] = $excludeId;
		}

		return (int) DB::getValue($sql, $params) === 0;
	}

	public static function getLangRows(int $idProduct): array
	{
		Lang::ensureSchema();

		return Lang::getLangRowsMap('product_lang', 'id_product', $idProduct);
	}

	private static function saveLangRows(int $idProduct, array $langData): ?array
	{
		Lang::ensureSchema();

		$defaultLang = Lang::getDefault();

		foreach (Lang::getAvailable() as $lang) {
			if (!array_key_exists($lang, $langData)) {
				continue;
			}

			$entry = is_array($langData[$lang]) ? $langData[$lang] : [];
			$name = trim((string) ($entry['product_name'] ?? ''));
			$link = trim((string) ($entry['product_link'] ?? ''));
			$shortDescription = trim(strip_tags((string) ($entry['short_description'] ?? '')));
			$description = Security::sanitizeHtml((string) ($entry['description'] ?? ''));
			$metaTitle = trim(strip_tags((string) ($entry['meta_title'] ?? '')));
			$metaDescription = trim(strip_tags((string) ($entry['meta_description'] ?? '')));

			if ($lang !== $defaultLang) {
				$hasContent = $name !== '' || $link !== '' || $shortDescription !== ''
					|| trim(strip_tags($description)) !== '' || $metaTitle !== '' || $metaDescription !== '';

				if (!$hasContent) {
					continue;
				}
			}

			if ($link === '' && $name !== '') {
				$link = Tools::createSlug($name);
			} elseif ($link !== '') {
				$link = Tools::createSlug($link);
			}

			if ($link !== '' && !self::isLinkUnique($link, $idProduct, $lang)) {
				return self::fail('Bu URL slug zaten kullanılıyor (' . Lang::label($lang) . ')');
			}

			if ($link === '') {
				continue;
			}

			Lang::saveLangRow('product_lang', 'id_product', $idProduct, $lang, [
				'product_name' => mb_substr($name, 0, 128),
				'product_link' => mb_substr($link, 0, 128),
				'short_description' => mb_substr($shortDescription, 0, 512),
				'description' => $description,
				'meta_title' => mb_substr($metaTitle, 0, 255),
				'meta_description' => mb_substr($metaDescription, 0, 512),
			]);
		}

		return null;
	}

	public static function save(array $data, int $id = 0): array
	{
		self::ensureSchema();
		Lang::ensureSchema();

		$langData = is_array($data['langs'] ?? null) ? $data['langs'] : [];
		$defaultLang = Lang::getDefault();
		$defaultEntry = is_array($langData[$defaultLang] ?? null) ? $langData[$defaultLang] : $data;

		if (!empty($data['variations']) && is_array($data['variations'])) {
			$data['variations'] = ProductVariation::parseFormRows($data['variations']);
		} elseif (!empty($data['variations_json']) && empty($data['variations'])) {
			$decoded = json_decode((string) $data['variations_json'], true);

			if (is_array($decoded)) {
				$data['variations'] = $decoded;
			}
		}

		if (array_key_exists('has_variations', $data) && (string) $data['has_variations'] !== '1') {
			$data['variations'] = [];
		}

		if (!empty($data['option_groups']) && is_array($data['option_groups'])) {
			$data['option_groups'] = ProductOption::parseFormRows($data['option_groups']);
		} elseif (!empty($data['option_groups_present'])) {
			$data['option_groups'] = [];
		}

		$name 			= trim((string) ($defaultEntry['product_name'] ?? $data['product_name'] ?? ''));
		$link 			= trim((string) ($defaultEntry['product_link'] ?? $data['product_link'] ?? ''));
		$idCategory 	= (int) ($data['id_category'] ?? 0);
		$idBrand 		= (int) ($data['id_brand'] ?? 0);
		$idSupplier 	= (int) ($data['id_supplier'] ?? 0);
		$stockCode 		= trim((string) ($data['stock_code'] ?? ''));
		$barcode 		= trim((string) ($data['barcode'] ?? ''));
		$desi 			= (int) ($data['desi'] ?? 0);
		self::ensureSchema();

		$shortDescription 	= trim(strip_tags((string) ($defaultEntry['short_description'] ?? $data['short_description'] ?? '')));
		$metaTitle 			= trim(strip_tags((string) ($defaultEntry['meta_title'] ?? $data['meta_title'] ?? '')));
		$metaDescription 	= trim(strip_tags((string) ($defaultEntry['meta_description'] ?? $data['meta_description'] ?? '')));
		$description 		= Security::sanitizeHtml((string) ($defaultEntry['description'] ?? $data['description'] ?? ''));
		$price 				= (float) str_replace(',', '.', (string) ($data['price'] ?? 0));
		$cost 				= (float) str_replace(',', '.', (string) ($data['cost'] ?? 0));
		$oldPrice 			= (float) str_replace(',', '.', (string) ($data['old_price'] ?? 0));
		$vat 				= Tax::sanitizeRate((float) str_replace(',', '.', (string) ($data['vat'] ?? Tax::getDefaultRate())));
		$stock 				= (float) str_replace(',', '.', (string) ($data['stock'] ?? 0));
		$active 			= isset($data['active']) ? (int) $data['active'] : 0;
		$productVideo 		= mb_substr(trim((string) ($data['product_video'] ?? '')), 0, 256);
		$shopCurrency 		= Currency::getShopCurrency();

		$cargoDay = max(0, (int) ($data['cargo_day'] ?? $data['day'] ?? 0));
		$label = mb_substr(trim(strip_tags((string) ($data['label'] ?? $data['tag'] ?? ''))), 0, 128);
		$productType = (string) ($data['product_type'] ?? 'physical');
		if (!in_array($productType, ['physical', 'virtual', 'pack'], true)) {
			$productType = 'physical';
		}
		$saleUnit = SaleUnit::normalize((string) ($data['sale_unit'] ?? SaleUnit::PIECE));
		if ($productType !== 'physical') {
			$saleUnit = SaleUnit::PIECE;
		}
		$saleQtyMin = (float) str_replace(',', '.', (string) ($data['sale_qty_min'] ?? ($saleUnit === SaleUnit::M2 ? '0.01' : '1')));
		$saleQtyStep = (float) str_replace(',', '.', (string) ($data['sale_qty_step'] ?? ($saleUnit === SaleUnit::M2 ? '0.01' : '1')));
		if ($saleQtyMin <= 0) {
			$saleQtyMin = $saleUnit === SaleUnit::M2 ? 0.01 : 1.0;
		}
		if ($saleQtyStep <= 0) {
			$saleQtyStep = $saleUnit === SaleUnit::M2 ? 0.01 : 1.0;
		}
		$saleQtyMin = round($saleQtyMin, 3);
		$saleQtyStep = round($saleQtyStep, 3);
		$virtualKind = trim((string) ($data['virtual_kind'] ?? ''));
		$allowedKinds = ['download', 'license', 'text'];

		if ($productType !== 'virtual') {
			$virtualKind = '';
		} elseif (!in_array($virtualKind, $allowedKinds, true)) {
			return self::fail('Sanal ürün için teslimat türü seçin');
		}

		$packPriceOverride = null;
		if ($productType === 'pack') {
			if (array_key_exists('pack_price_override', $data)) {
				$rawOverride = trim((string) $data['pack_price_override']);
				if ($rawOverride !== '') {
					$packPriceOverride = max(0, (float) str_replace(',', '.', $rawOverride));
				}
			} elseif ($id > 0) {
				$existingPack = self::getByIdAdmin($id);
				if ($existingPack && array_key_exists('pack_price_override', $existingPack)
					&& $existingPack['pack_price_override'] !== null
					&& $existingPack['pack_price_override'] !== '') {
					$packPriceOverride = (float) $existingPack['pack_price_override'];
				}
			}
		}

		$virtualText = trim((string) ($data['virtual_text'] ?? ''));

		if ($productType === 'pack') {
			$data['variations'] = [];
			$data['has_variations'] = '0';
		}
		$existing = null;
		if ($id > 0) {
			$existing = self::getByIdAdmin($id);

			if ($existing) {
				if ($name === '') {
					$name = (string) ($existing['product_name'] ?? '');
				}

				if ($link === '') {
					$link = (string) ($existing['product_link'] ?? '');
				}

				if ($shortDescription === '') {
					$shortDescription = (string) ($existing['short_description'] ?? '');
				}

				if ($metaTitle === '') {
					$metaTitle = (string) ($existing['meta_title'] ?? '');
				}

				if ($metaDescription === '') {
					$metaDescription = (string) ($existing['meta_description'] ?? '');
				}

				if ($description === '') {
					$description = (string) ($existing['description'] ?? '');
				}

				if ($idCategory <= 0) {
					$idCategory = (int) ($existing['id_category'] ?? 0);
				}

				if ($idBrand <= 0) {
					$idBrand = (int) ($existing['id_brand'] ?? 0);
				}
			}
		}

		if ($name === '') {
			return self::fail('Ürün adı zorunludur');
		}

		if ($idCategory <= 0 || $idBrand <= 0) {
			return self::fail('Kategori ve marka seçin');
		}

		if ($link === '') {
			$link = Tools::createSlug($name);
		} else {
			$link = Tools::createSlug($link);
		}

		if ($link === '') {
			return self::fail('Geçerli bir URL slug girin');
		}

		if (!self::isLinkUnique($link, $id, $defaultLang)) {
			return self::fail('Bu URL slug zaten kullanılıyor');
		}

		if ($productVideo !== '' && self::extractYoutubeId($productVideo) === '') {
			return self::fail('Geçerli bir YouTube video linki girin');
		}

		$langData[$defaultLang] = array_merge([
			'product_name' => $name,
			'product_link' => $link,
			'short_description' => $shortDescription,
			'description' => $description,
			'meta_title' => $metaTitle,
			'meta_description' => $metaDescription,
		], is_array($langData[$defaultLang] ?? null) ? $langData[$defaultLang] : []);

		$row = [
			'id_category' 		=> $idCategory,
			'id_brand' 			=> $idBrand,
			'id_supplier' 		=> max(0, $idSupplier),
			'product_name' 		=> $name,
			'short_description' => mb_substr($shortDescription, 0, 512),
			'meta_title' => mb_substr($metaTitle, 0, 255),
			'meta_description' => mb_substr($metaDescription, 0, 512),
			'description' 		=> $description,
			'product_link' 		=> $link,
			'price' 			=> max(0, $price),
			'pack_price_override' => $productType === 'pack' ? $packPriceOverride : null,
			'cost' 				=> max(0, $cost),
			'doviz' 			=> $shopCurrency,
			'doviz_price'		=> max(0, $price),
			'doviz_old_price'	=> max(0, $oldPrice),
			'old_price' 		=> max(0, $oldPrice),
			'vat' 				=> max(0, $vat),
			'stock' 			=> $productType === 'pack' ? 0 : max(0, round($stock, 3)),
			'sale_unit' 		=> $saleUnit,
			'sale_qty_min' 		=> $saleQtyMin,
			'sale_qty_step' 	=> $saleQtyStep,
			'cargo_day' 		=> $cargoDay,
			'label' 			=> $label,
			'product_video' 	=> $productVideo,
			'stock_code' 		=> $stockCode,
			'barcode' 			=> $barcode,
			'desi' 				=> (int)$desi,
			'product_type' 		=> $productType,
			'virtual_kind' 		=> $virtualKind,
			'virtual_text' 		=> $virtualText,
			'active' 			=> $active,
		];

		if ($id > 0) {
			$oldStock = isset($existing) ? (float) ($existing['stock'] ?? 0) : 0;
			$ok = DB::update('products', $row, 'id_product = :where_id', ['where_id' => $id]);

			if ($ok === false) {
				return self::fail('Ürün güncellenemedi');
			}

			if (class_exists('StockAnalysis', false)) {
				StockAnalysis::touchStockEmptyAt($id, $oldStock, (float) $row['stock']);
			}

			$langError = self::saveLangRows($id, $langData);

			if ($langError) {
				return $langError;
			}

			if ($productType === 'virtual' && $virtualKind === 'license' && isset($data['license_keys'])) {
				VirtualProduct::saveLicenseKeys($id, (string) $data['license_keys']);
			}

			if (array_key_exists('variations', $data) && is_array($data['variations'])) {
				$variationError = ProductVariation::saveForProduct($id, $data['variations'], (float) $row['price']);

				if ($variationError) {
					return $variationError;
				}
			}

			if (!empty($data['option_groups_present']) || array_key_exists('option_groups', $data)) {
				$optionError = ProductOption::saveForProduct($id, $data['option_groups'] ?? []);

				if ($optionError) {
					return $optionError;
				}
			}

			self::fireUpdatedHook($id, false);

			if (class_exists('ProductLog', false)) {
				ProductLog::logSaveDiff($existing ?? null, $row, $id, false);
			}

			return ['success' => true, 'message' => 'Ürün güncellendi', 'id' => $id];
		}

		$newId = DB::insert('products', $row);

		if (!$newId) {
			return self::fail('Ürün eklenemedi');
		}

		$newId = (int) $newId;

		$langError = self::saveLangRows($newId, $langData);

		if ($langError) {
			return $langError;
		}

		if ($productType === 'virtual' && $virtualKind === 'license' && isset($data['license_keys'])) {
			VirtualProduct::saveLicenseKeys($newId, (string) $data['license_keys']);
		}

		if (array_key_exists('variations', $data) && is_array($data['variations'])) {
			$variationError = ProductVariation::saveForProduct($newId, $data['variations'], (float) $row['price']);

			if ($variationError) {
				ProductVariation::deleteByProduct($newId);
				DB::execute('DELETE FROM products WHERE id_product = ?', [$newId]);

				return $variationError;
			}
		}

		if (!empty($data['option_groups_present']) || array_key_exists('option_groups', $data)) {
			$optionError = ProductOption::saveForProduct($newId, $data['option_groups'] ?? []);

			if ($optionError) {
				ProductOption::deleteByProduct($newId);
				ProductVariation::deleteByProduct($newId);
				DB::execute('DELETE FROM products WHERE id_product = ?', [$newId]);

				return $optionError;
			}
		}

		self::fireUpdatedHook($newId, true);

		if (class_exists('ProductLog', false)) {
			ProductLog::logSaveDiff(null, $row, $newId, true);
		}

		return ['success' => true, 'message' => 'Ürün eklendi', 'id' => $newId];
	}

	private static function fireUpdatedHook(int $idProduct, bool $isNew): void
	{
		if (!class_exists('Module', false)) {
			return;
		}

		$product = self::getByIdAdmin($idProduct) ?: [];

		Module::runHook('product.updated', [$idProduct, $product, $isNew]);
	}

	public static function uploadImage(int $idProduct, array $file): array
	{
		if ($idProduct <= 0 || !self::getByIdAdmin($idProduct)) {
			return self::fail('Ürün bulunamadı');
		}

		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
			return self::fail('Geçerli bir görsel seçin');
		}

		$binary = file_get_contents($file['tmp_name']);

		if (!is_string($binary) || $binary === '') {
			return self::fail('Görsel okunamadı');
		}

		return self::importImageBinary($idProduct, $binary);
	}

	public static function importImageBinary(int $idProduct, string $binary): array
	{
		if ($idProduct <= 0 || !self::getByIdAdmin($idProduct)) {
			return self::fail('Ürün bulunamadı');
		}

		if ($binary === '') {
			return self::fail('Geçerli bir görsel seçin');
		}

		$info = @getimagesizefromstring($binary);

		if (!$info) {
			return self::fail('Dosya bir görsel değil');
		}

		$allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
		if (!in_array($info[2], $allowed, true)) {
			return self::fail('Sadece JPG, PNG veya WEBP yükleyebilirsiniz');
		}

		$hasCover = (int) DB::getValue(
			'SELECT COUNT(*) FROM images WHERE id_product = ? AND cover = 1',
			[$idProduct]
		);

		$idImage = DB::insert('images', [
			'id_product' => $idProduct,
			'cover' => $hasCover > 0 ? 0 : 1,
		]);

		if (!$idImage) {
			return self::fail('Görsel kaydedilemedi');
		}

		$dest = dirname(__DIR__) . '/img/products/' . (int) $idImage . '.jpg';
		$dir = dirname($dest);

		if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
			return self::fail('Görsel klasörü oluşturulamadı');
		}

		$source = imagecreatefromstring($binary);

		if (!$source) {
			return self::fail('Görsel işlenemedi');
		}

		imagejpeg($source, $dest, 88);
		imagedestroy($source);

		return [
			'success' => true,
			'message' => 'Görsel yüklendi',
			'id' => (int) $idImage,
			'id_image' => (int) $idImage,
			'url' => self::getImageUrl((int) $idImage),
			'cover' => $hasCover > 0 ? 0 : 1,
		];
	}

	public static function importImageFromUrl(int $idProduct, string $url): array
	{
		if ($idProduct <= 0 || !self::getByIdAdmin($idProduct)) {
			return self::fail('Ürün bulunamadı');
		}

		$fetch = self::fetchImageBinaryFromUrl($url);

		if (empty($fetch['success'])) {
			return self::fail((string) ($fetch['message'] ?? 'Görsel indirilemedi'));
		}

		return self::importImageBinary($idProduct, (string) ($fetch['binary'] ?? ''));
	}

	/** @return string[] */
	public static function parseImportImageUrls(string $raw): array
	{
		$raw = trim($raw);

		if ($raw === '') {
			return [];
		}

		$raw = str_replace(["\r\n", "\r", "\n"], ';', $raw);
		$parts = preg_split('/[;|]+/', $raw) ?: [];
		$urls = [];

		foreach ($parts as $part) {
			$url = trim($part, " \t,");

			if ($url !== '') {
				$urls[] = $url;
			}
		}

		return array_values(array_unique($urls));
	}

	public static function getExportImageUrls(int $idProduct): string
	{
		if ($idProduct <= 0) {
			return '';
		}

		$urls = [];

		foreach (self::getImages($idProduct) as $image) {
			$url = trim((string) ($image['url'] ?? ''));

			if ($url !== '') {
				$urls[] = $url;
			}
		}

		return implode('; ', $urls);
	}

	/** @return array{success: bool, message: string, imported: int, errors: string[]} */
	public static function importImagesFromExcel(int $idProduct, string $rawUrls): array
	{
		$urls = self::parseImportImageUrls($rawUrls);

		if ($urls === []) {
			return ['success' => true, 'message' => '', 'imported' => 0, 'errors' => []];
		}

		if ($idProduct <= 0 || !self::getByIdAdmin($idProduct)) {
			return ['success' => false, 'message' => 'Ürün bulunamadı', 'imported' => 0, 'errors' => ['Ürün bulunamadı']];
		}

		$downloaded = [];
		$errors = [];

		foreach ($urls as $url) {
			$fetch = self::fetchImageBinaryFromUrl($url);

			if (!empty($fetch['success'])) {
				$downloaded[] = [
					'url' => $url,
					'binary' => (string) ($fetch['binary'] ?? ''),
				];
				continue;
			}

			$errors[] = $url . ': ' . (string) ($fetch['message'] ?? 'Görsel indirilemedi');
		}

		if ($downloaded === []) {
			return [
				'success' => false,
				'message' => 'Görsel yüklenemedi',
				'imported' => 0,
				'errors' => $errors,
			];
		}

		foreach (self::getImages($idProduct) as $image) {
			self::deleteImage((int) $image['id_image']);
		}

		$imported = 0;
		$firstImageId = 0;

		foreach ($downloaded as $item) {
			$result = self::importImageBinary($idProduct, $item['binary']);

			if (!empty($result['success'])) {
				$imported++;

				if ($firstImageId <= 0) {
					$firstImageId = (int) ($result['id_image'] ?? $result['id'] ?? 0);
				}
			} else {
				$errors[] = $item['url'] . ': ' . (string) ($result['message'] ?? 'Görsel kaydedilemedi');
			}
		}

		if ($firstImageId > 0) {
			self::setCover($firstImageId);
		}

		return [
			'success' => $imported > 0,
			'message' => $imported > 0
				? $imported . ' görsel yüklendi'
				: 'Görsel yüklenemedi',
			'imported' => $imported,
			'errors' => $errors,
		];
	}

	/** @return array{success: bool, message?: string, binary?: string} */
	private static function fetchImageBinaryFromUrl(string $url): array
	{
		$url = trim($url);

		if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
			return ['success' => false, 'message' => 'Geçersiz görsel URL'];
		}

		$scheme = strtolower((string) (parse_url($url, PHP_URL_SCHEME) ?? ''));

		if (!in_array($scheme, ['http', 'https'], true)) {
			return ['success' => false, 'message' => 'Sadece http/https URL desteklenir'];
		}

		if (!Security::isSafeOutboundUrl($url)) {
			return ['success' => false, 'message' => 'Görsel URL güvenlik kontrolünden geçemedi'];
		}

		if (!function_exists('curl_init')) {
			return ['success' => false, 'message' => 'cURL eklentisi gerekli'];
		}

		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 3,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 20,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_USERAGENT => 'FShop-WebAPI/1.0',
			CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
			CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
		]);

		if (defined('CURLOPT_FOLLOWFUNCTION')) {
			curl_setopt($ch, CURLOPT_FOLLOWFUNCTION, static function ($curlHandle, $redirectUrl) {
				return Security::isSafeOutboundUrl((string) $redirectUrl) ? (int) strlen((string) $redirectUrl) : 0;
			});
		}

		$binary = curl_exec($ch);
		$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curlError = curl_error($ch);
		curl_close($ch);

		if ($binary === false || $binary === '') {
			return ['success' => false, 'message' => $curlError !== '' ? 'Görsel indirilemedi: ' . $curlError : 'Görsel indirilemedi'];
		}

		if ($httpCode < 200 || $httpCode >= 300) {
			return ['success' => false, 'message' => 'Görsel indirilemedi (HTTP ' . $httpCode . ')'];
		}

		if (strlen($binary) > 5 * 1024 * 1024) {
			return ['success' => false, 'message' => 'Görsel 5 MB sınırını aşıyor'];
		}

		$info = @getimagesizefromstring($binary);

		if (!$info) {
			return ['success' => false, 'message' => 'Dosya bir görsel değil'];
		}

		$allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

		if (!in_array($info[2], $allowed, true)) {
			return ['success' => false, 'message' => 'Sadece JPG, PNG veya WEBP desteklenir'];
		}

		return ['success' => true, 'binary' => $binary];
	}

	public static function patchQuick(int $id, array $data): array
	{
		$product = self::getByIdAdmin($id);

		if (!$product) {
			return self::fail('Ürün bulunamadı');
		}

		$row = [];

		if (array_key_exists('stock', $data)) {
			$row['stock'] = max(0, round((float) str_replace(',', '.', (string) $data['stock']), 3));
		}

		if (array_key_exists('active', $data)) {
			$row['active'] = filter_var($data['active'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
		}

		if (array_key_exists('cost', $data)) {
			$row['cost'] = (float) str_replace(',', '.', (string) $data['cost']);
		}

		if (array_key_exists('price', $data) || array_key_exists('old_price', $data)
			|| array_key_exists('doviz_price', $data) || array_key_exists('doviz_old_price', $data)) {
			$shopCurrency = Currency::getShopCurrency();
			$newPrice = array_key_exists('price', $data)
				? (float) str_replace(',', '.', (string) $data['price'])
				: (array_key_exists('doviz_price', $data)
					? (float) str_replace(',', '.', (string) $data['doviz_price'])
					: (float) ($product['price'] ?? 0));
			$newOldPrice = array_key_exists('old_price', $data)
				? (float) str_replace(',', '.', (string) $data['old_price'])
				: (array_key_exists('doviz_old_price', $data)
					? (float) str_replace(',', '.', (string) $data['doviz_old_price'])
					: (float) ($product['old_price'] ?? 0));

			$row['price'] = max(0, $newPrice);
			$row['old_price'] = max(0, $newOldPrice);
			$row['doviz'] = $shopCurrency;
			$row['doviz_price'] = max(0, $newPrice);
			$row['doviz_old_price'] = max(0, $newOldPrice);
		}

		if ($row === []) {
			return self::fail('Güncellenecek alan yok (price, stock veya active gönderin)');
		}

		$ok = DB::update('products', $row, 'id_product = :where_id', ['where_id' => $id]);

		if ($ok !== false && array_key_exists('stock', $row) && class_exists('StockAnalysis', false)) {
			StockAnalysis::touchStockEmptyAt($id, (float) ($product['stock'] ?? 0), (float) $row['stock']);
		}

		if ($ok !== false && array_key_exists('stock', $row)) {
			self::fireUpdatedHook($id, false);
		}

		return $ok !== false
			? ['success' => true, 'message' => 'Ürün hızlı güncellendi', 'id' => $id]
			: self::fail('Ürün güncellenemedi');
	}

	public static function setCover(int $idImage): array
	{
		$row = DB::getRowSafe('images', 'id_image = ?', [$idImage]);

		if (!$row) {
			return self::fail('Görsel bulunamadı');
		}

		$idProduct = (int) $row['id_product'];
		DB::update('images', ['cover' => 0], 'id_product = :where_pid', ['where_pid' => $idProduct]);
		DB::update('images', ['cover' => 1], 'id_image = :where_id', ['where_id' => $idImage]);

		return ['success' => true, 'message' => 'Kapak görseli güncellendi', 'id' => $idImage];
	}

	/**
	 * @param list<int|string> $ids
	 * @return array{success: bool, message: string, count: int}
	 */
	public static function bulkSetActive(array $ids, int $active): array
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function (int $id): bool {
			return $id > 0;
		})));

		if ($ids === []) {
			return self::fail('Ürün seçilmedi');
		}

		$active = $active === 1 ? 1 : 0;
		$count = 0;

		foreach ($ids as $id) {
			if (!self::getByIdAdmin($id)) {
				continue;
			}

			$ok = DB::update('products', ['active' => $active], 'id_product = :where_id', ['where_id' => $id]);

			if ($ok === false) {
				continue;
			}

			self::fireUpdatedHook($id, false);
			$count++;
		}

		if ($count === 0) {
			return self::fail('Seçili ürünler güncellenemedi');
		}

		return [
			'success' => true,
			'message' => $count . ' ürün ' . ($active === 1 ? 'aktif' : 'pasif') . ' edildi',
			'count' => $count,
		];
	}

	/**
	 * @param list<int|string> $ids
	 * @return array{success: bool, message: string, count: int}
	 */
	public static function bulkDelete(array $ids): array
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function (int $id): bool {
			return $id > 0;
		})));

		if ($ids === []) {
			return self::fail('Ürün seçilmedi');
		}

		$count = 0;

		foreach ($ids as $id) {
			$result = self::deleteById($id);

			if (!empty($result['success'])) {
				$count++;
			}
		}

		if ($count === 0) {
			return self::fail('Seçili ürünler silinemedi');
		}

		return [
			'success' => true,
			'message' => $count . ' ürün silindi',
			'count' => $count,
		];
	}

	public static function deleteById(int $id): array
	{
		if ($id <= 0 || !self::getByIdAdmin($id)) {
			return self::fail('Ürün bulunamadı');
		}

		foreach (self::getImages($id) as $image) {
			self::deleteImage((int) $image['id_image']);
		}

		ProductVariation::deleteByProduct($id);
		DB::execute('DELETE FROM products WHERE id_product = ?', [$id]);

		return ['success' => true, 'message' => 'Ürün silindi', 'id' => $id];
	}

	public static function deleteImage(int $idImage): array
	{
		$row = DB::getRowSafe('images', 'id_image = ?', [$idImage]);

		if (!$row) {
			return self::fail('Görsel bulunamadı');
		}

		$idProduct = (int) $row['id_product'];
		$wasCover = (int) $row['cover'] === 1;
		$file = dirname(__DIR__) . '/img/products/' . $idImage . '.jpg';

		DB::execute('DELETE FROM images WHERE id_image = ?', [$idImage]);

		if (is_file($file)) {
			@unlink($file);
		}

		if ($wasCover) {
			$next = DB::getRowSafe('images', 'id_product = ?', [$idProduct]);
			if ($next) {
				DB::update('images', ['cover' => 1], 'id_image = :where_id', ['where_id' => (int) $next['id_image']]);
			}
		}

		return ['success' => true, 'message' => 'Görsel silindi', 'id' => $idImage];
	}

	public static function importFromExcel(array $rows): array
	{
		if (count($rows) < 2) {
			return self::fail('Excel dosyasında veri satırı yok');
		}

		$headers = array_map([self::class, 'normalizeImportHeader'], $rows[0]);
		$map = self::buildImportColumnMap($headers);

		if (!isset($map['product_name'])) {
			return self::fail('Ürün adı sütunu bulunamadı');
		}

		$created = 0;
		$updated = 0;
		$imagesImported = 0;
		$categoriesCreated = 0;
		$brandsCreated = 0;
		$errors = [];
		$categoryCache = [];
		$brandCache = [];

		for ($i = 1, $count = count($rows); $i < $count; $i++) {
			if (self::isImportRowEmpty($rows[$i])) {
				continue;
			}

			$result = self::importExcelRow(
				$rows[$i],
				$map,
				$i + 1,
				$categoryCache,
				$brandCache,
				$categoriesCreated,
				$brandsCreated
			);

			if ($result['success']) {
				$imagesImported += (int) ($result['images_imported'] ?? 0);

				if (!empty($result['image_warnings'])) {
					foreach ($result['image_warnings'] as $warning) {
						$errors[] = 'Satır ' . ($i + 1) . ' görsel: ' . $warning;
					}
				}

				if (!empty($result['created'])) {
					$created++;
				} else {
					$updated++;
				}
				continue;
			}

			$errors[] = $result['message'];
		}

		if ($created === 0 && $updated === 0 && $errors) {
			return self::fail(implode(' ', array_slice($errors, 0, 3)));
		}

		$message = $created . ' ürün eklendi, ' . $updated . ' ürün güncellendi';

		if ($categoriesCreated > 0) {
			$message .= ', ' . $categoriesCreated . ' kategori oluşturuldu';
		}

		if ($brandsCreated > 0) {
			$message .= ', ' . $brandsCreated . ' marka oluşturuldu';
		}

		if ($imagesImported > 0) {
			$message .= ', ' . $imagesImported . ' görsel yüklendi';
		}

		if ($errors) {
			$message .= '. Hatalar: ' . implode('; ', array_slice($errors, 0, 5));

			if (count($errors) > 5) {
				$message .= ' (+' . (count($errors) - 5) . ' hata daha)';
			}
		}

		return [
			'success' => true,
			'message' => $message,
			'created' => $created,
			'updated' => $updated,
			'categories_created' => $categoriesCreated,
			'brands_created' => $brandsCreated,
			'errors' => $errors,
			'id' => 0,
		];
	}

	private static function importExcelRow(
		array $row,
		array $map,
		int $lineNo,
		array &$categoryCache,
		array &$brandCache,
		int &$categoriesCreated,
		int &$brandsCreated
	): array {
		$barcode = self::importCell($row, $map, 'barcode');
		$stockCode = self::importCell($row, $map, 'stock_code');

		if ($stockCode === '') {
			return self::fail('Satır ' . $lineNo . ': stok kodu zorunludur');
		}

		$id = self::findIdByStockCode($stockCode);

		$categoryName = self::importCell($row, $map, 'category_name');
		$brandName = self::importCell($row, $map, 'brand_name');

		$idCategory = self::resolveOrCreateCategoryId($categoryName, $categoryCache, $categoriesCreated);
		$idBrand = self::resolveOrCreateBrandId($brandName, $brandCache, $brandsCreated);

		if ($idCategory <= 0) {
			return self::fail('Satır ' . $lineNo . ': kategori oluşturulamadı');
		}

		if ($idBrand <= 0) {
			return self::fail('Satır ' . $lineNo . ': marka oluşturulamadı');
		}

		$defaultLang = Lang::getDefault();
		$langFields = [
			'product_name' => self::importCell($row, $map, 'product_name'),
			'product_link' => self::importCell($row, $map, 'slug'),
			'short_description' => self::importCell($row, $map, 'short_description'),
			'description' => self::importCell($row, $map, 'description'),
			'meta_title' => self::importCell($row, $map, 'meta_title'),
			'meta_description' => self::importCell($row, $map, 'meta_description'),
		];

		$data = [
			'product_name' => $langFields['product_name'],
			'barcode' => $barcode,
			'stock_code' => $stockCode,
			'desi' => self::importCell($row, $map, 'desi'),
			'price' => self::importCell($row, $map, 'price'),
			'old_price' => self::importCell($row, $map, 'old_price'),
			'vat' => self::importCell($row, $map, 'vat'),
			'stock' => self::importCell($row, $map, 'stock'),
			'short_description' => $langFields['short_description'],
			'description' => $langFields['description'],
			'meta_title' => $langFields['meta_title'],
			'meta_description' => $langFields['meta_description'],
			'product_link' => $langFields['product_link'],
			'id_category' => $idCategory,
			'id_brand' => $idBrand,
			'active' => self::parseImportActive(self::importCell($row, $map, 'active')),
			'langs' => [
				$defaultLang => $langFields,
			],
		];

		$result = self::save($data, $id);

		if (!$result['success']) {
			return self::fail('Satır ' . $lineNo . ': ' . $result['message']);
		}

		$idProduct = (int) ($result['id'] ?? 0);
		$imageRaw = self::importCell($row, $map, 'images');

		if ($imageRaw !== '' && $idProduct > 0) {
			$imageResult = self::importImagesFromExcel($idProduct, $imageRaw);

			if (($imageResult['imported'] ?? 0) <= 0) {
				$imageError = implode('; ', array_slice($imageResult['errors'] ?? [], 0, 2));

				return self::fail(
					'Satır ' . $lineNo . ': ürün kaydedildi ancak görsel yüklenemedi'
					. ($imageError !== '' ? ' (' . $imageError . ')' : '')
				);
			}

			if (!empty($imageResult['errors'])) {
				$result['image_warnings'] = $imageResult['errors'];
			}

			$result['images_imported'] = (int) $imageResult['imported'];
		}

		$result['created'] = $id <= 0;

		return $result;
	}

	private static function findIdByStockCode(string $stockCode): int
	{
		$stockCode = trim($stockCode);

		if ($stockCode === '') {
			return 0;
		}

		return (int) DB::getValue(
			'SELECT id_product FROM products WHERE stock_code = ? LIMIT 1',
			[$stockCode]
		);
	}

	private static function resolveOrCreateCategoryId(string $name, array &$cache, int &$createdCount): int
	{
		$name = trim($name);

		if ($name === '') {
			return 0;
		}

		$key = mb_strtolower($name);

		if (isset($cache[$key])) {
			return $cache[$key];
		}

		$id = (int) DB::getValue(
			'SELECT id_category FROM categories WHERE LOWER(category_name) = LOWER(?) LIMIT 1',
			[$name]
		);

		if ($id > 0) {
			$cache[$key] = $id;

			return $id;
		}

		$result = Category::save([
			'category_name' => $name,
			'id_parent' => self::getImportCategoryParentId(),
			'active' => 1,
		]);

		if (!$result['success']) {
			return 0;
		}

		$id = (int) $result['id'];
		$cache[$key] = $id;
		$createdCount++;

		return $id;
	}

	private static function resolveOrCreateBrandId(string $name, array &$cache, int &$createdCount): int
	{
		$name = trim($name);

		if ($name === '') {
			return 0;
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

		if (!$result['success']) {
			return 0;
		}

		$id = (int) $result['id'];
		$cache[$key] = $id;
		$createdCount++;

		return $id;
	}

	private static function getImportCategoryParentId(): int
	{
		static $parentId = null;

		if ($parentId !== null) {
			return $parentId;
		}

		$parentId = (int) DB::getValue(
			'SELECT id_category FROM categories WHERE id_parent = 0 AND active = 1 ORDER BY id_category ASC LIMIT 1'
		);

		return max(0, $parentId);
	}

	private static function normalizeImportHeader($header): string
	{
		$header = strip_tags((string) $header);
		$header = html_entity_decode($header, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$header = strtolower(trim($header));
		$header = preg_replace('/\s+/', ' ', $header);

		return $header ?? '';
	}

	private static function buildImportColumnMap(array $headers): array
	{
		$aliases = [
			'product_id' => ['product id'],
			'product_name' => ['product name', 'ürün adı', 'urun adi'],
			'barcode' => ['barcode', 'barkod'],
			'stock_code' => ['stock code', 'stok kodu', 'sku'],
			'desi' => ['desi'],
			'price' => ['price', 'fiyat'],
			'old_price' => ['old price', 'eski fiyat'],
			'vat' => ['vat', 'kdv'],
			'stock' => ['stock', 'stok'],
			'short_description' => ['short description', 'kısa açıklama', 'kisa aciklama'],
			'description' => ['description', 'açıklama', 'aciklama'],
			'meta_title' => ['meta title'],
			'meta_description' => ['meta description'],
			'slug' => ['slug', 'product link', 'url'],
			'category_name' => ['category name', 'kategori', 'kategori adı', 'kategori adi'],
			'brand_name' => ['brand name', 'marka', 'marka adı', 'marka adi'],
			'images' => ['images', 'image', 'görseller', 'gorseller', 'gorsel'],
			'active' => ['active', 'durum', 'aktif'],
		];

		$map = [];

		foreach ($headers as $index => $header) {
			if ($header === '') {
				continue;
			}

			foreach ($aliases as $field => $names) {
				if (in_array($header, $names, true)) {
					$map[$field] = $index;
					break;
				}
			}
		}

		return $map;
	}

	private static function importCell(array $row, array $map, string $key): string
	{
		if (!isset($map[$key])) {
			return '';
		}

		$index = $map[$key];

		return isset($row[$index]) ? trim((string) $row[$index]) : '';
	}

	private static function isImportRowEmpty(array $row): bool
	{
		foreach ($row as $cell) {
			if (trim((string) $cell) !== '') {
				return false;
			}
		}

		return true;
	}

	private static function parseImportActive(string $value): int
	{
		$value = strtolower(trim($value));

		if ($value === '' || $value === 'aktif' || $value === '1' || $value === 'yes' || $value === 'evet') {
			return 1;
		}

		if ($value === 'pasif' || $value === '0' || $value === 'no' || $value === 'hayır' || $value === 'hayir') {
			return 0;
		}

		return (int) $value > 0 ? 1 : 0;
	}

	private static function fail(string $message): array
	{
		return ['success' => false, 'message' => $message, 'id' => 0];
	}
	
	public static function refreshCurrencyPrices(): int
	{
		if (!class_exists('ExchangeRate', false)) {
			require_once dirname(__DIR__) . '/core/ExchangeRate.php';
		}

		ExchangeRate::getRates(true);

		return ExchangeRate::refreshProductPrices();
	}

	private static function kurPrice(float $price, string $currency): float
	{
		if (!class_exists('ExchangeRate', false)) {
			require_once dirname(__DIR__) . '/core/ExchangeRate.php';
		}

		return ExchangeRate::toTry($price, $currency);
	}

	private static function fetchExchangeRate(string $symbol): float
	{
		if (!class_exists('ExchangeRate', false)) {
			require_once dirname(__DIR__) . '/core/ExchangeRate.php';
		}

		$map = [
			'USDTRY' => 'usd',
			'EURTRY' => 'eur',
			'GLDGR' => 'xau',
		];

		$code = $map[$symbol] ?? '';

		if ($code === '') {
			return 0.0;
		}

		return ExchangeRate::getRate($code);
	}
}
