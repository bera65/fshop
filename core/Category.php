<?php

class Category
{
	private static bool $schemaReady = false;

	public static function ensureSchema(): void
	{
		if (self::$schemaReady) {
			return;
		}

		self::$schemaReady = true;

		$metaTitle = DB::execute("SHOW COLUMNS FROM `categories` LIKE 'meta_title'");

		if (empty($metaTitle)) {
			DB::execute(
				"ALTER TABLE `categories`
				 ADD COLUMN `meta_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' AFTER `category_link`,
				 ADD COLUMN `meta_description` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' AFTER `meta_title`"
			);
		}

		$showOnHome = DB::execute("SHOW COLUMNS FROM `categories` LIKE 'show_on_home'");

		if (empty($showOnHome)) {
			DB::execute(
				"ALTER TABLE `categories`
				 ADD COLUMN `show_on_home` tinyint(1) NOT NULL DEFAULT 0 AFTER `active`,
				 ADD COLUMN `home_position` int(11) NOT NULL DEFAULT 0 AFTER `show_on_home`"
			);
		}
	}

	/**
	 * Ana sayfada gösterilecek kategoriler (show_on_home=1).
	 * Hiçbiri seçili değilse menü kategorilerine düşer (geriye uyumluluk).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function getHomeList(): array
	{
		self::ensureSchema();

		$rows = DB::execute(
			'SELECT * FROM categories
			 WHERE active = 1 AND show_on_home = 1
			 ORDER BY home_position ASC, category_name ASC'
		) ?: [];

		if ($rows === []) {
			return self::getMenuList();
		}

		return array_map(static fn(array $row): array => Lang::applyCategory($row), $rows);
	}

	public static function getByLink(string $link): ?array
	{
		$link = trim($link);

		if ($link === '') {
			return null;
		}

		$row = DB::getRowSafe('categories', 'category_link = ? AND active = 1', [$link]);

		if (!$row) {
			$lang = Lang::current();
			$idCategory = DB::getValue(
				'SELECT id_category FROM category_lang WHERE category_link = ? AND lang = ? LIMIT 1',
				[$link, $lang]
			);

			if ($idCategory === false) {
				$idCategory = DB::getValue(
					'SELECT id_category FROM category_lang WHERE category_link = ? LIMIT 1',
					[$link]
				);
			}

			if ($idCategory !== false) {
				$row = DB::getRowSafe('categories', 'id_category = ? AND active = 1', [(int) $idCategory]);
			}
		}

		return $row ? Lang::applyCategory($row) : null;
	}

	public static function getById(int $id): ?array
	{
		$row = DB::getRowSafe('categories', 'id_category = ? AND active = 1', [$id]);

		return $row ? Lang::applyCategory($row) : null;
	}

	public static function getMenuList(): array
	{
		$rows = DB::execute(
			'SELECT * FROM categories WHERE active = 1 AND id_parent > 0 ORDER BY category_name ASC'
		) ?: [];

		return array_map(static fn(array $row): array => Lang::applyCategory($row), $rows);
	}

	/**
	 * Ürün formu için hiyerarşik kategori seçenekleri (alt kategoriler dahil).
	 *
	 * @return array<int, array{id_category: int, category_name: string, depth: int}>
	 */
	public static function getProductSelectOptions(): array
	{
		$rows = DB::execute(
			'SELECT * FROM categories WHERE active = 1 ORDER BY category_name ASC'
		) ?: [];

		$rows = array_map(static fn(array $row): array => Lang::applyCategory($row), $rows);

		/** @var array<int, list<array<string, mixed>>> $childrenByParent */
		$childrenByParent = [];

		foreach ($rows as $row) {
			$parentId = (int) ($row['id_parent'] ?? 0);
			$childrenByParent[$parentId][] = $row;
		}

		foreach ($childrenByParent as &$group) {
			usort($group, static fn(array $a, array $b): int => strcasecmp(
				(string) ($a['category_name'] ?? ''),
				(string) ($b['category_name'] ?? '')
			));
		}
		unset($group);

		$options = [];

		$walk = static function (int $parentId, int $depth) use (&$walk, &$options, $childrenByParent): void {
			foreach ($childrenByParent[$parentId] ?? [] as $row) {
				$id = (int) ($row['id_category'] ?? 0);

				if ($id <= 0) {
					continue;
				}

				$name = (string) ($row['category_name'] ?? '');
				$prefix = $depth > 0 ? str_repeat('— ', $depth) : '';

				$options[] = [
					'id_category' => $id,
					'category_name' => $prefix . $name,
					'depth' => $depth,
				];

				$walk($id, $depth + 1);
			}
		};

		$walk(0, 0);

		return $options;
	}

	/**
	 * Kategori sayfası breadcrumb öğeleri (üst kategoriler dahil).
	 *
	 * @return array<int, array{name: string, url: string}>
	 */
	public static function getBreadcrumbItems(int $idCategory): array
	{
		global $domain;

		if ($idCategory <= 0) {
			return [];
		}

		$chain = [];
		$currentId = $idCategory;
		$guard = 0;

		while ($currentId > 0 && $guard < 32) {
			$guard++;
			$row = DB::getRowSafe('categories', 'id_category = ? AND active = 1', [$currentId]);

			if (!$row) {
				break;
			}

			$chain[] = Lang::applyCategory($row);
			$currentId = (int) ($row['id_parent'] ?? 0);
		}

		if ($chain === []) {
			return [];
		}

		$chain = array_reverse($chain);
		$items = [];
		$lastIndex = count($chain) - 1;

		foreach ($chain as $index => $row) {
			if ((int) ($row['id_parent'] ?? 0) === 0 && $index !== $lastIndex) {
				continue;
			}

			$isLast = $index === $lastIndex;

			$items[] = [
				'name' => (string) ($row['category_name'] ?? ''),
				'url' => $isLast ? '' : $domain . ($row['category_link'] ?? ''),
			];
		}

		return $items;
	}

	/** Menü + alt kategoriler (mega menu için) */
	public static function getMenuListWithChildren(): array
	{
		$menu = self::getMenuList();

		foreach ($menu as &$cat) {
			$children = DB::execute(
				'SELECT * FROM categories WHERE active = 1 AND id_parent = ? ORDER BY category_name ASC',
				[(int) $cat['id_category']]
			) ?: [];
			$cat['subcategories'] = array_map(static fn(array $row): array => Lang::applyCategory($row), $children);
		}
		unset($cat);

		return $menu;
	}

	/** @return array<int, array<string, mixed>> */
	public static function getChildren(int $idParent): array
	{
		if ($idParent <= 0) {
			return [];
		}

		$rows = DB::execute(
			'SELECT * FROM categories WHERE active = 1 AND id_parent = ? ORDER BY category_name ASC',
			[$idParent]
		) ?: [];

		return array_map(static fn(array $row): array => Lang::applyCategory($row), $rows);
	}

	/** @return int[] */
	public static function getScopeIds(int $idCategory): array
	{
		if ($idCategory <= 0) {
			return [];
		}

		$ids = [$idCategory];
		$queue = [$idCategory];

		while ($queue !== []) {
			$parentId = array_shift($queue);
			foreach (self::getChildren((int) $parentId) as $child) {
				$childId = (int) ($child['id_category'] ?? 0);

				if ($childId <= 0 || in_array($childId, $ids, true)) {
					continue;
				}

				$ids[] = $childId;
				$queue[] = $childId;
			}
		}

		return $ids;
	}

	/**
	 * @param int[] $categoryIds
	 * @return array<int, array<string, mixed>>
	 */
	public static function getBrandsInCategories(array $categoryIds): array
	{
		$categoryIds = array_values(array_filter(array_map('intval', $categoryIds)));

		if ($categoryIds === []) {
			return [];
		}

		$placeholders = implode(',', array_fill(0, count($categoryIds), '?'));

		$rows = DB::execute(
			'SELECT b.*, COUNT(p.id_product) AS product_count
			 FROM brands b
			 INNER JOIN products p ON p.id_brand = b.id_brand AND p.active = 1
			 WHERE b.active = 1 AND p.id_category IN (' . $placeholders . ')
			 GROUP BY b.id_brand
			 HAVING product_count > 0
			 ORDER BY b.brand_name ASC',
			$categoryIds
		) ?: [];

		return array_map(static fn(array $row): array => Lang::applyBrand($row), $rows);
	}

	public static function getUrl(array $category): string
	{
		global $domain;

		return $domain . $category['category_link'];
	}

	public static function getAdminList(int $activeFilter = -1, int $limit = 50, int $offset = 0): array
	{
		self::ensureSchema();

		$sql = 'SELECT c.*, p.category_name AS parent_name
			FROM categories c
			LEFT JOIN categories p ON c.id_parent = p.id_category
			WHERE 1=1';
		$params = [];

		if ($activeFilter >= 0) {
			$sql .= ' AND c.active = ?';
			$params[] = $activeFilter;
		}

		$sql .= ' ORDER BY c.show_on_home DESC, c.home_position ASC, c.id_category ASC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

		return DB::execute($sql, $params) ?: [];
	}

	/** Ana sayfa vitrin bayrağını aç/kapat */
	public static function setShowOnHome(int $idCategory, bool $show): bool
	{
		self::ensureSchema();

		if ($idCategory <= 0) {
			return false;
		}

		return DB::update(
			'categories',
			['show_on_home' => $show ? 1 : 0],
			'id_category = :where_id',
			['where_id' => $idCategory]
		) !== false;
	}

	public static function countAdmin(int $activeFilter = -1): int
	{
		if ($activeFilter >= 0) {
			return (int) DB::getValue('SELECT COUNT(*) FROM categories WHERE active = ?', [$activeFilter]);
		}

		return (int) DB::getValue('SELECT COUNT(*) FROM categories');
	}

	public static function getByIdAdmin(int $id): ?array
	{
		$row = DB::getRowSafe('categories', 'id_category = ?', [$id]);

		return $row ?: null;
	}

	public static function getParentOptions(int $excludeId = 0): array
	{
		$sql = 'SELECT id_category, category_name FROM categories WHERE active = 1';
		$params = [];

		if ($excludeId > 0) {
			$sql .= ' AND id_category != ?';
			$params[] = $excludeId;
		}

		$sql .= ' ORDER BY category_name ASC';

		return DB::execute($sql, $params) ?: [];
	}

	public static function isLinkUnique(string $link, int $excludeId = 0, ?string $lang = null): bool
	{
		$lang = $lang ?: Lang::getDefault();

		if ($lang === Lang::getDefault()) {
			$sql = 'SELECT COUNT(*) FROM categories WHERE category_link = ?';
			$params = [$link];

			if ($excludeId > 0) {
				$sql .= ' AND id_category != ?';
				$params[] = $excludeId;
			}

			if ((int) DB::getValue($sql, $params) > 0) {
				return false;
			}
		}

		$sql = 'SELECT COUNT(*) FROM category_lang WHERE category_link = ? AND lang = ?';
		$params = [$link, $lang];

		if ($excludeId > 0) {
			$sql .= ' AND id_category != ?';
			$params[] = $excludeId;
		}

		return (int) DB::getValue($sql, $params) === 0;
	}

	public static function getLangRows(int $idCategory): array
	{
		Lang::ensureSchema();

		return Lang::getLangRowsMap('category_lang', 'id_category', $idCategory);
	}

	private static function saveLangRows(int $idCategory, array $langData): ?array
	{
		Lang::ensureSchema();

		$defaultLang = Lang::getDefault();

		foreach (Lang::getAvailable() as $lang) {
			if (!array_key_exists($lang, $langData)) {
				continue;
			}

			$entry = is_array($langData[$lang]) ? $langData[$lang] : [];
			$name = trim((string) ($entry['category_name'] ?? ''));
			$link = trim((string) ($entry['category_link'] ?? ''));
			$metaTitle = trim(strip_tags((string) ($entry['meta_title'] ?? '')));
			$metaDescription = trim(strip_tags((string) ($entry['meta_description'] ?? '')));

			if ($lang !== $defaultLang) {
				$hasContent = $name !== '' || $link !== '' || $metaTitle !== '' || $metaDescription !== '';

				if (!$hasContent) {
					continue;
				}
			}

			if ($link === '' && $name !== '') {
				$link = Tools::createSlug($name);
			} elseif ($link !== '') {
				$link = Tools::createSlug($link);
			}

			if ($link === '') {
				continue;
			}

			if (!self::isLinkUnique($link, $idCategory, $lang)) {
				return self::fail('Bu URL slug zaten kullanılıyor (' . Lang::label($lang) . ')');
			}

			Lang::saveLangRow('category_lang', 'id_category', $idCategory, $lang, [
				'category_name' => mb_substr($name, 0, 64),
				'category_link' => mb_substr($link, 0, 128),
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

		$name = trim((string) ($defaultEntry['category_name'] ?? $data['category_name'] ?? ''));
		$link = trim((string) ($defaultEntry['category_link'] ?? $data['category_link'] ?? ''));
		$idParent = (int) ($data['id_parent'] ?? 0);
		$active = !empty($data['active']) ? 1 : 0;
		$showOnHome = !empty($data['show_on_home']) ? 1 : 0;
		$homePosition = (int) ($data['home_position'] ?? 0);
		$metaTitle = mb_substr(trim(strip_tags((string) ($defaultEntry['meta_title'] ?? $data['meta_title'] ?? ''))), 0, 255);
		$metaDescription = mb_substr(trim(strip_tags((string) ($defaultEntry['meta_description'] ?? $data['meta_description'] ?? ''))), 0, 512);

		if ($name === '') {
			return self::fail('Kategori adı zorunludur');
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

		$langData[$defaultLang] = array_merge([
			'category_name' => $name,
			'category_link' => $link,
			'meta_title' => $metaTitle,
			'meta_description' => $metaDescription,
		], is_array($langData[$defaultLang] ?? null) ? $langData[$defaultLang] : []);

		$row = [
			'category_name' => $name,
			'category_link' => $link,
			'meta_title' => $metaTitle,
			'meta_description' => $metaDescription,
			'id_parent' => max(0, $idParent),
			'active' => $active,
			'show_on_home' => $showOnHome,
			'home_position' => max(0, $homePosition),
		];

		if ($id > 0) {
			$ok = DB::update('categories', $row, 'id_category = :where_id', ['where_id' => $id]);

			if ($ok === false) {
				return self::fail('Kategori güncellenemedi');
			}

			$langError = self::saveLangRows($id, $langData);

			return $langError ?: ['success' => true, 'message' => 'Kategori güncellendi', 'id' => $id];
		}

		$newId = DB::insert('categories', $row);

		if (!$newId) {
			return self::fail('Kategori eklenemedi');
		}

		$newId = (int) $newId;
		$langError = self::saveLangRows($newId, $langData);

		return $langError ?: ['success' => true, 'message' => 'Kategori eklendi', 'id' => $newId];
	}

	private static function fail(string $message): array
	{
		return ['success' => false, 'message' => $message, 'id' => 0];
	}
}
