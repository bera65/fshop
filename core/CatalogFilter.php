<?php

class CatalogFilter
{
	public int $baseCategoryId = 0;
	public int $subCategoryId = 0;
	public int $brandId = 0;
	public ?float $priceMin = null;
	public ?float $priceMax = null;

	/** '' | in_stock | out_of_stock */
	public string $stockStatus = '';

	/** '' | yes | no */
	public string $discount = '';

	/** @var array<string, string> varyasyon grubu => değer (ör. Beden => M) */
	public array $variationFilters = [];

	/** @var int[] */
	private array $allowedCategoryIds = [];

	public static function forCategory(int $idCategory): self
	{
		$filter = new self();
		$filter->baseCategoryId = max(0, $idCategory);
		$filter->allowedCategoryIds = $idCategory > 0 ? Category::getScopeIds($idCategory) : [];
		$filter->readRequest();

		return $filter;
	}

	public function readRequest(): void
	{
		$sub = (int) Tools::getValue('subcat', 0);

		if ($sub > 0 && in_array($sub, $this->allowedCategoryIds, true)) {
			$this->subCategoryId = $sub;
		}

		$this->brandId = max(0, (int) Tools::getValue('brand', 0));

		$priceMin = trim((string) Tools::getValue('price_min', ''));
		$priceMax = trim((string) Tools::getValue('price_max', ''));

		$this->priceMin = $priceMin !== '' ? max(0, (float) str_replace(',', '.', $priceMin)) : null;
		$this->priceMax = $priceMax !== '' ? max(0, (float) str_replace(',', '.', $priceMax)) : null;

		if ($this->priceMin !== null && $this->priceMax !== null && $this->priceMin > $this->priceMax) {
			$swap = $this->priceMin;
			$this->priceMin = $this->priceMax;
			$this->priceMax = $swap;
		}

		$stock = trim((string) Tools::getValue('stock', ''));

		if (in_array($stock, ['in_stock', 'out_of_stock'], true)) {
			$this->stockStatus = $stock;
		}

		$discount = trim((string) Tools::getValue('discount', ''));

		if (in_array($discount, ['yes', 'no'], true)) {
			$this->discount = $discount;
		}

		$this->variationFilters = self::parseVariationRequest(Tools::getValue('var'));
	}

	/** @param mixed $raw @return array<string, string> */
	private static function parseVariationRequest($raw): array
	{
		if (!is_array($raw)) {
			return [];
		}

		$filters = [];

		foreach ($raw as $group => $value) {
			$group = self::sanitizeVariationKey((string) $group);
			$value = trim(strip_tags((string) $value));

			if ($group !== '' && $value !== '') {
				$filters[$group] = $value;
			}
		}

		return $filters;
	}

	private static function sanitizeVariationKey(string $key): string
	{
		$key = trim($key);

		if ($key === '' || strlen($key) > 64) {
			return '';
		}

		return preg_match('/^[\p{L}\p{N}\s\-_.]+$/u', $key) ? $key : '';
	}

	/** @return int[] */
	public function getCategoryIds(): array
	{
		if ($this->baseCategoryId <= 0) {
			return [];
		}

		if ($this->subCategoryId > 0) {
			return Category::getScopeIds($this->subCategoryId);
		}

		return $this->allowedCategoryIds;
	}

	/** @return array<string, scalar|array<string, string>> */
	public function toQueryArray(): array
	{
		$query = [];

		if ($this->subCategoryId > 0) {
			$query['subcat'] = $this->subCategoryId;
		}

		if ($this->brandId > 0) {
			$query['brand'] = $this->brandId;
		}

		if ($this->priceMin !== null) {
			$query['price_min'] = $this->formatPriceQuery($this->priceMin);
		}

		if ($this->priceMax !== null) {
			$query['price_max'] = $this->formatPriceQuery($this->priceMax);
		}

		if ($this->stockStatus !== '') {
			$query['stock'] = $this->stockStatus;
		}

		if ($this->discount !== '') {
			$query['discount'] = $this->discount;
		}

		if ($this->variationFilters !== []) {
			$query['var'] = $this->variationFilters;
		}

		return $query;
	}

	public function hasActiveFilters(): bool
	{
		return $this->subCategoryId > 0
			|| $this->brandId > 0
			|| $this->priceMin !== null
			|| $this->priceMax !== null
			|| $this->stockStatus !== ''
			|| $this->discount !== ''
			|| $this->variationFilters !== [];
	}

	public function buildUrl(string $baseUrl, array $extra = []): string
	{
		$params = $this->toQueryArray();

		foreach ($extra as $key => $value) {
			if ($value === null) {
				unset($params[$key]);
			} else {
				$params[$key] = $value;
			}
		}

		$params = array_filter($params, static function ($value) {
			return $value !== null && $value !== '';
		});

		$qs = http_build_query($params);

		return $baseUrl . ($qs !== '' ? '?' . $qs : '');
	}

	/**
	 * Ürün listesi sorgusuna filtre koşullarını ekler.
	 *
	 * @param array<int, scalar> $params
	 */
	public function appendProductSql(string &$sql, array &$params): void
	{
		$categoryIds = $this->getCategoryIds();

		if ($categoryIds !== []) {
			$placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
			$sql .= ' AND p.id_category IN (' . $placeholders . ')';
			foreach ($categoryIds as $id) {
				$params[] = $id;
			}
		}

		if ($this->brandId > 0) {
			$sql .= ' AND p.id_brand = ?';
			$params[] = $this->brandId;
		}

		if ($this->priceMin !== null) {
			$sql .= ' AND p.price >= ?';
			$params[] = $this->priceMin;
		}

		if ($this->priceMax !== null) {
			$sql .= ' AND p.price <= ?';
			$params[] = $this->priceMax;
		}

		if ($this->discount === 'yes') {
			$sql .= ' AND p.old_price > 0 AND p.old_price > p.price';
		} elseif ($this->discount === 'no') {
			$sql .= ' AND (p.old_price <= 0 OR p.old_price <= p.price)';
		}

		if ($this->stockStatus === 'in_stock') {
			$sql .= ' AND (
				(p.product_type != \'pack\' AND NOT EXISTS (
					SELECT 1 FROM product_variations pv0
					WHERE pv0.id_product = p.id_product AND pv0.active = 1
				) AND p.stock > 0)
				OR EXISTS (
					SELECT 1 FROM product_variations pv1
					WHERE pv1.id_product = p.id_product AND pv1.active = 1 AND pv1.stock > 0
				)
			)';
		} elseif ($this->stockStatus === 'out_of_stock') {
			$sql .= ' AND (
				(p.product_type != \'pack\' AND NOT EXISTS (
					SELECT 1 FROM product_variations pv0
					WHERE pv0.id_product = p.id_product AND pv0.active = 1
				) AND p.stock <= 0)
				OR (
					EXISTS (
						SELECT 1 FROM product_variations pv1
						WHERE pv1.id_product = p.id_product AND pv1.active = 1
					)
					AND NOT EXISTS (
						SELECT 1 FROM product_variations pv2
						WHERE pv2.id_product = p.id_product AND pv2.active = 1 AND pv2.stock > 0
					)
				)
			)';
		}

		foreach ($this->variationFilters as $group => $value) {
			$key = self::sanitizeVariationKey($group);

			if ($key === '') {
				continue;
			}

			$sql .= ' AND EXISTS (
				SELECT 1 FROM product_variations pvf
				WHERE pvf.id_product = p.id_product
				  AND pvf.active = 1
				  AND JSON_UNQUOTE(JSON_EXTRACT(pvf.options_json, ?)) = ?
			)';
			$params[] = '$.' . $key;
			$params[] = $value;
		}
	}

	/**
	 * Kategorideki ürünlerden otomatik varyasyon filtre grupları.
	 *
	 * @return array<int, array{group: string, values: array<int, array{value: string, count: int}>}>
	 */
	public function getVariationFacets(): array
	{
		$categoryIds = $this->getCategoryIds();

		if ($categoryIds === []) {
			return [];
		}

		ProductVariation::ensureSchema();
		$placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
		$rows = DB::execute(
			'SELECT pv.options_json
			 FROM product_variations pv
			 INNER JOIN products p ON p.id_product = pv.id_product AND p.active = 1
			 WHERE pv.active = 1 AND p.id_category IN (' . $placeholders . ')',
			$categoryIds
		) ?: [];

		/** @var array<string, array<string, int>> */
		$groups = [];

		foreach ($rows as $row) {
			$options = json_decode((string) ($row['options_json'] ?? '{}'), true);

			if (!is_array($options)) {
				continue;
			}

			foreach ($options as $name => $val) {
				$name = self::sanitizeVariationKey((string) $name);
				$val = trim(strip_tags((string) $val));

				if ($name === '' || $val === '') {
					continue;
				}

				$groups[$name][$val] = (int) ($groups[$name][$val] ?? 0) + 1;
			}
		}

		$facets = [];

		foreach ($groups as $group => $values) {
			$valueList = [];

			foreach ($values as $value => $count) {
				$valueList[] = ['value' => $value, 'count' => $count];
			}

			usort($valueList, static fn(array $a, array $b): int => strcmp($a['value'], $b['value']));
			$facets[] = ['group' => $group, 'values' => $valueList];
		}

		usort($facets, static fn(array $a, array $b): int => strcmp($a['group'], $b['group']));

		return $facets;
	}

	/** @return array{in_stock: int, out_of_stock: int} */
	public function getStockFacets(): array
	{
		$categoryIds = $this->getCategoryIds();

		if ($categoryIds === []) {
			return ['in_stock' => 0, 'out_of_stock' => 0];
		}

		ProductVariation::ensureSchema();
		$placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
		$params = $categoryIds;

		$inStock = (int) DB::getValue(
			'SELECT COUNT(*) FROM products p
			 WHERE p.active = 1 AND p.id_category IN (' . $placeholders . ')
			 AND (
				(p.product_type != \'pack\' AND NOT EXISTS (
					SELECT 1 FROM product_variations pv0
					WHERE pv0.id_product = p.id_product AND pv0.active = 1
				) AND p.stock > 0)
				OR EXISTS (
					SELECT 1 FROM product_variations pv1
					WHERE pv1.id_product = p.id_product AND pv1.active = 1 AND pv1.stock > 0
				)
			 )',
			$params
		);

		$outOfStock = (int) DB::getValue(
			'SELECT COUNT(*) FROM products p
			 WHERE p.active = 1 AND p.id_category IN (' . $placeholders . ')
			 AND (
				(p.product_type != \'pack\' AND NOT EXISTS (
					SELECT 1 FROM product_variations pv0
					WHERE pv0.id_product = p.id_product AND pv0.active = 1
				) AND p.stock <= 0)
				OR (
					EXISTS (
						SELECT 1 FROM product_variations pv1
						WHERE pv1.id_product = p.id_product AND pv1.active = 1
					)
					AND NOT EXISTS (
						SELECT 1 FROM product_variations pv2
						WHERE pv2.id_product = p.id_product AND pv2.active = 1 AND pv2.stock > 0
					)
				)
			 )',
			$params
		);

		return ['in_stock' => $inStock, 'out_of_stock' => $outOfStock];
	}

	/** @return array{yes: int, no: int} */
	public function getDiscountFacets(): array
	{
		$categoryIds = $this->getCategoryIds();

		if ($categoryIds === []) {
			return ['yes' => 0, 'no' => 0];
		}

		$placeholders = implode(',', array_fill(0, count($categoryIds), '?'));

		$yes = (int) DB::getValue(
			'SELECT COUNT(*) FROM products p
			 WHERE p.active = 1 AND p.id_category IN (' . $placeholders . ')
			 AND p.old_price > 0 AND p.old_price > p.price',
			$categoryIds
		);

		$no = (int) DB::getValue(
			'SELECT COUNT(*) FROM products p
			 WHERE p.active = 1 AND p.id_category IN (' . $placeholders . ')
			 AND (p.old_price <= 0 OR p.old_price <= p.price)',
			$categoryIds
		);

		return ['yes' => $yes, 'no' => $no];
	}

	private function formatPriceQuery(float $value): string
	{
		return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
	}
}
