<?php

class BlogService
{
	public static function ensureSchema(): void
	{
		$posts = DB::execute("SHOW TABLES LIKE 'blog_posts'");

		if (empty($posts)) {
			DB::execute(
				"CREATE TABLE IF NOT EXISTS `blog_posts` (
					`id_blog_post` int(11) NOT NULL AUTO_INCREMENT,
					`id_blog_category` int(11) NOT NULL DEFAULT 0,
					`title` varchar(255) NOT NULL DEFAULT '',
					`slug` varchar(255) NOT NULL DEFAULT '',
					`excerpt` varchar(512) NOT NULL DEFAULT '',
					`content` mediumtext NOT NULL,
					`cover_image` varchar(255) NOT NULL DEFAULT '',
					`meta_title` varchar(255) NOT NULL DEFAULT '',
					`meta_description` varchar(512) NOT NULL DEFAULT '',
					`active` tinyint(1) NOT NULL DEFAULT 1,
					`date_add` datetime NOT NULL,
					`date_upd` datetime NOT NULL,
					PRIMARY KEY (`id_blog_post`),
					UNIQUE KEY `slug` (`slug`),
					KEY `active_date` (`active`, `date_add`),
					KEY `id_blog_category` (`id_blog_category`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}

		$catCol = DB::execute("SHOW COLUMNS FROM `blog_posts` LIKE 'id_blog_category'");

		if (empty($catCol)) {
			DB::execute(
				'ALTER TABLE `blog_posts` ADD `id_blog_category` int(11) NOT NULL DEFAULT 0 AFTER `id_blog_post`, ADD KEY `id_blog_category` (`id_blog_category`)'
			);
		}

		$cats = DB::execute("SHOW TABLES LIKE 'blog_categories'");

		if (empty($cats)) {
			DB::execute(
				"CREATE TABLE IF NOT EXISTS `blog_categories` (
					`id_blog_category` int(11) NOT NULL AUTO_INCREMENT,
					`name` varchar(128) NOT NULL DEFAULT '',
					`slug` varchar(128) NOT NULL DEFAULT '',
					`description` varchar(512) NOT NULL DEFAULT '',
					`position` int(11) NOT NULL DEFAULT 0,
					`active` tinyint(1) NOT NULL DEFAULT 1,
					PRIMARY KEY (`id_blog_category`),
					UNIQUE KEY `slug` (`slug`),
					KEY `position` (`position`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}
	}

	public static function slugify(string $text): string
	{
		$map = [
			'ş' => 's', 'Ş' => 's', 'ı' => 'i', 'İ' => 'i', 'ğ' => 'g', 'Ğ' => 'g',
			'ü' => 'u', 'Ü' => 'u', 'ö' => 'o', 'Ö' => 'o', 'ç' => 'c', 'Ç' => 'c',
		];
		$text = strtr($text, $map);
		$text = strtolower(trim($text));
		$text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
		$text = trim($text, '-');

		return $text !== '' ? $text : 'yazi';
	}

	public static function uniqueSlug(string $slug, int $excludeId = 0): string
	{
		$base = self::slugify($slug);
		$candidate = $base;
		$i = 2;

		while (true) {
			$id = (int) DB::getValue(
				'SELECT id_blog_post FROM blog_posts WHERE slug = ? AND id_blog_post != ? LIMIT 1',
				[$candidate, $excludeId]
			);

			if ($id <= 0) {
				return $candidate;
			}

			$candidate = $base . '-' . $i;
			$i++;
		}
	}

	public static function uniqueCategorySlug(string $slug, int $excludeId = 0): string
	{
		$base = self::slugify($slug);
		$candidate = $base !== '' ? $base : 'kategori';
		$i = 2;

		while (true) {
			$id = (int) DB::getValue(
				'SELECT id_blog_category FROM blog_categories WHERE slug = ? AND id_blog_category != ? LIMIT 1',
				[$candidate, $excludeId]
			);

			if ($id <= 0) {
				return $candidate;
			}

			$candidate = $base . '-' . $i;
			$i++;
		}
	}

	public static function getById(int $id): ?array
	{
		$row = DB::getRowSafe('blog_posts', 'id_blog_post = ?', [$id]);

		return $row ?: null;
	}

	public static function getPublishedById(int $id): ?array
	{
		if ($id <= 0) {
			return null;
		}

		$row = DB::getRowSafe('blog_posts', 'id_blog_post = ? AND active = 1', [$id]);

		return $row ?: null;
	}

	public static function getBySlug(string $slug): ?array
	{
		$slug = trim($slug);

		if ($slug === '') {
			return null;
		}

		$row = DB::getRowSafe('blog_posts', 'slug = ? AND active = 1', [$slug]);

		return $row ?: null;
	}

	/** /blog/{slug}-{id} */
	public static function buildUrl(string $slug, int $id): string
	{
		global $domain;

		$slug = trim($slug, '/');
		$base = rtrim((string) $domain, '/') . '/';

		if ($slug === '' || $id <= 0) {
			return $base . 'blog';
		}

		return $base . 'blog/' . $slug . '-' . $id;
	}

	/** /blog/kategori/{slug}-{id} */
	public static function buildCategoryUrl(string $slug, int $id): string
	{
		global $domain;

		$slug = trim($slug, '/');
		$base = rtrim((string) $domain, '/') . '/';

		if ($slug === '' || $id <= 0) {
			return $base . 'blog';
		}

		return $base . 'blog/kategori/' . $slug . '-' . $id;
	}

	public static function getCategoryById(int $id): ?array
	{
		if ($id <= 0) {
			return null;
		}

		$row = DB::getRowSafe('blog_categories', 'id_blog_category = ?', [$id]);

		return $row ?: null;
	}

	public static function getPublishedCategory(int $id): ?array
	{
		if ($id <= 0) {
			return null;
		}

		$row = DB::getRowSafe('blog_categories', 'id_blog_category = ? AND active = 1', [$id]);

		return $row ? self::enrichCategory($row) : null;
	}

	/** @return array<int, array<string, mixed>> */
	public static function getCategories(bool $activeOnly = false): array
	{
		self::ensureSchema();

		$sql = 'SELECT * FROM blog_categories';

		if ($activeOnly) {
			$sql .= ' WHERE active = 1';
		}

		$sql .= ' ORDER BY position ASC, name ASC';

		$rows = DB::execute($sql) ?: [];

		foreach ($rows as &$row) {
			$row = self::enrichCategory($row);
		}
		unset($row);

		return $rows;
	}

	public static function enrichCategory(array $row): array
	{
		$id = (int) ($row['id_blog_category'] ?? 0);
		$slug = (string) ($row['slug'] ?? '');
		$row['url'] = self::buildCategoryUrl($slug, $id);

		return $row;
	}

	public static function saveCategory(array $data, int $id = 0): array
	{
		self::ensureSchema();

		$name = trim((string) ($data['name'] ?? ''));
		$slug = trim((string) ($data['slug'] ?? ''));
		$description = trim((string) ($data['description'] ?? ''));
		$position = (int) ($data['position'] ?? 0);
		$active = !empty($data['active']) ? 1 : 0;

		if ($name === '') {
			return ['success' => false, 'message' => 'Kategori adı gerekli'];
		}

		if ($slug === '') {
			$slug = $name;
		}

		$slug = self::uniqueCategorySlug($slug, $id);

		$payload = [
			'name' => $name,
			'slug' => $slug,
			'description' => $description,
			'position' => $position,
			'active' => $active,
		];

		if ($id > 0) {
			DB::update('blog_categories', $payload, 'id_blog_category = :id', ['id' => $id]);

			return ['success' => true, 'message' => 'Kategori güncellendi', 'id' => $id];
		}

		$newId = DB::insert('blog_categories', $payload);

		return $newId
			? ['success' => true, 'message' => 'Kategori eklendi', 'id' => (int) $newId]
			: ['success' => false, 'message' => 'Kayıt başarısız'];
	}

	public static function deleteCategory(int $id): array
	{
		if ($id <= 0) {
			return ['success' => false, 'message' => 'Geçersiz kategori'];
		}

		DB::execute('UPDATE blog_posts SET id_blog_category = 0 WHERE id_blog_category = ?', [$id]);
		DB::execute('DELETE FROM blog_categories WHERE id_blog_category = ?', [$id]);

		return ['success' => true, 'message' => 'Kategori silindi'];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function getList(bool $activeOnly = false, int $limit = 50, int $offset = 0, int $categoryId = 0): array
	{
		$sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
			FROM blog_posts p
			LEFT JOIN blog_categories c ON c.id_blog_category = p.id_blog_category';
		$where = [];
		$params = [];

		if ($activeOnly) {
			$where[] = 'p.active = 1';
		}

		if ($categoryId > 0) {
			$where[] = 'p.id_blog_category = ?';
			$params[] = $categoryId;
		}

		if ($where !== []) {
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}

		$sql .= ' ORDER BY p.date_add DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

		$rows = DB::execute($sql, $params) ?: [];

		foreach ($rows as &$row) {
			$row = self::enrich($row);
		}
		unset($row);

		return $rows;
	}

	public static function enrich(array $row): array
	{
		global $domain;

		$base = rtrim((string) $domain, '/') . '/';
		$id = (int) ($row['id_blog_post'] ?? 0);
		$slug = (string) ($row['slug'] ?? '');
		$row['url'] = self::buildUrl($slug, $id);
		$row['date_formatted'] = !empty($row['date_add']) ? Tools::formatDate3($row['date_add']) : '';
		$row['id_blog_category'] = (int) ($row['id_blog_category'] ?? 0);

		if (empty($row['category_name']) && $row['id_blog_category'] > 0) {
			$cat = self::getCategoryById($row['id_blog_category']);
			if ($cat) {
				$row['category_name'] = (string) ($cat['name'] ?? '');
				$row['category_slug'] = (string) ($cat['slug'] ?? '');
			}
		}

		if (!empty($row['category_name']) && !empty($row['category_slug']) && $row['id_blog_category'] > 0) {
			$row['category_url'] = self::buildCategoryUrl((string) $row['category_slug'], $row['id_blog_category']);
		} else {
			$row['category_url'] = '';
			$row['category_name'] = (string) ($row['category_name'] ?? '');
		}

		$cover = trim((string) ($row['cover_image'] ?? ''));

		if ($cover !== '' && preg_match('#^https?://#i', $cover)) {
			$row['cover_url'] = $cover;
		} elseif ($cover !== '') {
			$coverPath = ltrim(str_replace('\\', '/', $cover), '/');
			if (strpos($coverPath, 'img/') !== 0) {
				$coverPath = 'img/' . $coverPath;
			}
			$row['cover_url'] = $base . $coverPath;
		} else {
			$row['cover_url'] = '';
		}

		return $row;
	}

	public static function save(array $data, int $id = 0): array
	{
		self::ensureSchema();

		$title = trim((string) ($data['title'] ?? ''));
		$slug = trim((string) ($data['slug'] ?? ''));
		$excerpt = trim((string) ($data['excerpt'] ?? ''));
		$content = Security::sanitizeHtml((string) ($data['content'] ?? ''));
		$cover = trim((string) ($data['cover_image'] ?? ''));
		$metaTitle = trim((string) ($data['meta_title'] ?? ''));
		$metaDesc = trim((string) ($data['meta_description'] ?? ''));
		$active = !empty($data['active']) ? 1 : 0;
		$categoryId = (int) ($data['id_blog_category'] ?? 0);

		if ($title === '') {
			return ['success' => false, 'message' => 'Başlık gerekli'];
		}

		if ($categoryId > 0 && !self::getCategoryById($categoryId)) {
			$categoryId = 0;
		}

		if ($slug === '') {
			$slug = $title;
		}

		$slug = self::uniqueSlug($slug, $id);
		$now = date('Y-m-d H:i:s');

		$payload = [
			'id_blog_category' => $categoryId,
			'title' => $title,
			'slug' => $slug,
			'excerpt' => $excerpt,
			'content' => $content,
			'cover_image' => $cover,
			'meta_title' => $metaTitle !== '' ? $metaTitle : $title,
			'meta_description' => $metaDesc !== '' ? $metaDesc : self::clipMeta(strip_tags($excerpt !== '' ? $excerpt : $content)),
			'active' => $active,
			'date_upd' => $now,
		];

		if ($id > 0) {
			DB::update('blog_posts', $payload, 'id_blog_post = :id', ['id' => $id]);

			return ['success' => true, 'message' => 'Yazı güncellendi', 'id' => $id];
		}

		$payload['date_add'] = $now;
		$newId = DB::insert('blog_posts', $payload);

		return $newId
			? ['success' => true, 'message' => 'Yazı eklendi', 'id' => (int) $newId]
			: ['success' => false, 'message' => 'Kayıt başarısız'];
	}

	public static function delete(int $id): array
	{
		if ($id <= 0) {
			return ['success' => false, 'message' => 'Geçersiz yazı'];
		}

		DB::execute('DELETE FROM blog_posts WHERE id_blog_post = ?', [$id]);

		return ['success' => true, 'message' => 'Silindi'];
	}

	private static function clipMeta(string $text): string
	{
		$text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

		if (function_exists('mb_substr')) {
			return mb_substr($text, 0, 160);
		}

		return substr($text, 0, 160);
	}
}
