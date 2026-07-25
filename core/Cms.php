<?php

class Cms
{
	private static bool $schemaReady = false;

	public static function ensureSchema(): void
	{
		if (self::$schemaReady) {
			return;
		}

		self::$schemaReady = true;
		Lang::ensureSchema();

		$pagesTable = DB::execute("SHOW TABLES LIKE 'cms_pages'");
		if (empty($pagesTable)) {
			DB::execute(
				"CREATE TABLE `cms_pages` (
					`id_cms` int(11) NOT NULL AUTO_INCREMENT,
					`slug` varchar(128) NOT NULL,
					`active` tinyint(1) NOT NULL DEFAULT 1,
					`show_footer` tinyint(1) NOT NULL DEFAULT 1,
					`position` int(11) NOT NULL DEFAULT 0,
					`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					`date_upd` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
					PRIMARY KEY (`id_cms`),
					UNIQUE KEY `slug` (`slug`),
					KEY `active` (`active`),
					KEY `show_footer` (`show_footer`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);

			DB::execute(
				"CREATE TABLE `cms_lang` (
					`id_cms` int(11) NOT NULL,
					`lang` varchar(8) NOT NULL,
					`slug` varchar(128) NOT NULL DEFAULT '',
					`title` varchar(255) NOT NULL DEFAULT '',
					`summary` varchar(512) NOT NULL DEFAULT '',
					`content` mediumtext,
					`meta_title` varchar(255) NOT NULL DEFAULT '',
					`meta_description` varchar(512) NOT NULL DEFAULT '',
					PRIMARY KEY (`id_cms`, `lang`),
					KEY `lang` (`lang`),
					UNIQUE KEY `lang_slug` (`lang`, `slug`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		} else {
			self::migrateLangSlug();
		}

		self::migrateLegacyPages();
		self::syncAllLangSlots();
	}

	public static function ensureLangSlots(string $lang): void
	{
		self::ensureSchema();
		$lang = strtolower(trim($lang));

		if ($lang === '') {
			return;
		}

		$pages = DB::execute('SELECT id_cms, slug FROM cms_pages') ?: [];
		$defaultLang = Lang::getDefault();

		foreach ($pages as $page) {
			$idCms = (int) $page['id_cms'];
			$exists = DB::getValue(
				'SELECT id_cms FROM cms_lang WHERE id_cms = ? AND lang = ? LIMIT 1',
				[$idCms, $lang]
			);

			if ($exists !== false) {
				continue;
			}

			$slug = (string) $page['slug'];
			$source = DB::getRowSafe('cms_lang', 'id_cms = ? AND lang = ?', [$idCms, $defaultLang]);

			if ($source && trim((string) ($source['slug'] ?? '')) !== '') {
				$slug = (string) $source['slug'];
			}

			DB::insert('cms_lang', [
				'id_cms' => $idCms,
				'lang' => $lang,
				'slug' => $slug,
				'title' => (string) ($source['title'] ?? ''),
				'summary' => (string) ($source['summary'] ?? ''),
				'content' => (string) ($source['content'] ?? ''),
				'meta_title' => (string) ($source['meta_title'] ?? ''),
				'meta_description' => (string) ($source['meta_description'] ?? ''),
			]);
		}
	}

	public static function syncAllLangSlots(): void
	{
		if (!class_exists('Lang', false)) {
			return;
		}

		foreach (Lang::getAvailable() as $lang) {
			self::ensureLangSlots($lang);
		}
	}

	public static function exists(string $slug): bool
	{
		self::ensureSchema();
		$slug = self::normalizeSlug($slug);

		if ($slug === '') {
			return false;
		}

		return (int) DB::getValue(
			'SELECT cl.id_cms
			 FROM cms_lang cl
			 INNER JOIN cms_pages p ON p.id_cms = cl.id_cms
			 WHERE cl.slug = ? AND p.active = 1
			 LIMIT 1',
			[$slug]
		) > 0;
	}

	public static function getBySlug(string $slug, ?string $lang = null): ?array
	{
		self::ensureSchema();
		$slug = self::normalizeSlug($slug);

		if ($slug === '') {
			return null;
		}

		$idCms = (int) DB::getValue(
			'SELECT cl.id_cms
			 FROM cms_lang cl
			 INNER JOIN cms_pages p ON p.id_cms = cl.id_cms
			 WHERE cl.slug = ? AND p.active = 1
			 LIMIT 1',
			[$slug]
		);

		if ($idCms <= 0) {
			return null;
		}

		$page = DB::getRowSafe('cms_pages', 'id_cms = ? AND active = 1', [$idCms]);

		return $page ? self::hydratePage($page, $lang) : null;
	}

	public static function getById(int $idCms, ?string $lang = null): ?array
	{
		self::ensureSchema();

		if ($idCms <= 0) {
			return null;
		}

		$page = DB::getRowSafe('cms_pages', 'id_cms = ?', [$idCms]);

		return $page ? self::hydratePage($page, $lang, true) : null;
	}

	public static function getPages(): array
	{
		self::ensureSchema();
		$rows = DB::execute(
			'SELECT * FROM cms_pages WHERE active = 1 ORDER BY position ASC, id_cms ASC'
		) ?: [];

		$pages = [];

		foreach ($rows as $row) {
			$page = self::hydratePage($row);
			if ($page) {
				$pages[$page['slug']] = $page;
			}
		}

		return $pages;
	}

	public static function getFooterLinks(): array
	{
		self::ensureSchema();
		global $domain;

		$lang = Lang::current();
		$rows = DB::execute(
			'SELECT * FROM cms_pages WHERE active = 1 AND show_footer = 1 ORDER BY position ASC, id_cms ASC'
		) ?: [];

		$links = [];
		$base = rtrim($domain, '/');

		foreach ($rows as $row) {
			$page = self::hydratePage($row, $lang);

			if (!$page) {
				continue;
			}

			$title = trim((string) ($page['title'] ?? ''));
			$slug = trim((string) ($page['slug'] ?? ''));

			if ($title === '' || $slug === '') {
				continue;
			}

			$links[] = [
				'slug' => $slug,
				'title' => $title,
				'url' => $base . '/' . $slug,
			];
		}

		return $links;
	}

	public static function getAdminList(): array
	{
		self::ensureSchema();
		global $adminUrl;

		$rows = DB::execute(
			'SELECT * FROM cms_pages ORDER BY position ASC, id_cms ASC'
		) ?: [];

		$list = [];
		$defaultLang = Lang::getDefault();

		foreach ($rows as $row) {
			$page = self::hydratePage($row, $defaultLang, true);
			if (!$page) {
				continue;
			}

			$list[] = array_merge($page, [
				'edit_url' => ($adminUrl ?? '') . 'cms-edit?id=' . (int) $page['id_cms'],
			]);
		}

		return $list;
	}

	public static function save(int $idCms, array $data): array
	{
		self::ensureSchema();

		$active = isset($data['active']) ? (int) $data['active'] : 1;
		$showFooter = isset($data['show_footer']) ? (int) $data['show_footer'] : 1;
		$position = max(0, (int) ($data['position'] ?? 0));
		$langData = is_array($data['langs'] ?? null) ? $data['langs'] : [];
		$defaultLang = Lang::getDefault();
		$defaultSlug = '';

		foreach (Lang::getAvailable() as $lang) {
			$entry = is_array($langData[$lang] ?? null) ? $langData[$lang] : [];
			$slug = self::normalizeSlug((string) ($entry['slug'] ?? ''));

			if ($slug === '') {
				$title = trim((string) ($entry['title'] ?? ''));

				if ($title !== '') {
					$slug = self::normalizeSlug($title);
				}
			}

			if ($slug === '') {
				return self::fail('Her dil için URL slug veya başlık zorunludur (' . Lang::label($lang) . ')');
			}

			$duplicate = DB::getValue(
				'SELECT cl.id_cms
				 FROM cms_lang cl
				 WHERE cl.slug = ? AND cl.lang = ?' . ($idCms > 0 ? ' AND cl.id_cms != ?' : '') . '
				 LIMIT 1',
				$idCms > 0 ? [$slug, $lang, $idCms] : [$slug, $lang]
			);

			if ($duplicate !== false) {
				return self::fail('Bu URL slug zaten kullanılıyor (' . Lang::label($lang) . ': ' . $slug . ')');
			}

			if ($lang === $defaultLang) {
				$defaultSlug = $slug;
			}
		}

		if ($defaultSlug === '') {
			return self::fail('Varsayılan dil için URL slug zorunludur');
		}

		$pageRow = [
			'slug' => $defaultSlug,
			'active' => $active ? 1 : 0,
			'show_footer' => $showFooter ? 1 : 0,
			'position' => $position,
		];

		if ($idCms > 0) {
			$ok = DB::update('cms_pages', $pageRow, 'id_cms = :where_id', ['where_id' => $idCms]);
			if ($ok === false) {
				return self::fail('Sayfa güncellenemedi');
			}
		} else {
			$newId = DB::insert('cms_pages', $pageRow);
			if (!$newId) {
				return self::fail('Sayfa oluşturulamadı');
			}
			$idCms = (int) $newId;
		}

		foreach (Lang::getAvailable() as $lang) {
			$entry = is_array($langData[$lang] ?? null) ? $langData[$lang] : [];
			$title = trim((string) ($entry['title'] ?? ''));
			$summary = trim(strip_tags((string) ($entry['summary'] ?? '')));
			$content = Security::sanitizeHtml((string) ($entry['content'] ?? ''));
			$metaTitle = trim(strip_tags((string) ($entry['meta_title'] ?? '')));
			$metaDescription = trim(strip_tags((string) ($entry['meta_description'] ?? '')));
			$slug = self::normalizeSlug((string) ($entry['slug'] ?? ''));

			if ($slug === '' && $title !== '') {
				$slug = self::normalizeSlug($title);
			}

			$exists = DB::getValue(
				'SELECT id_cms FROM cms_lang WHERE id_cms = ? AND lang = ? LIMIT 1',
				[$idCms, $lang]
			);

			$langRow = [
				'slug' => $slug,
				'title' => mb_substr($title, 0, 255),
				'summary' => mb_substr($summary, 0, 512),
				'content' => $content,
				'meta_title' => mb_substr($metaTitle, 0, 255),
				'meta_description' => mb_substr($metaDescription, 0, 512),
			];

			if ($exists !== false) {
				DB::update(
					'cms_lang',
					$langRow,
					'id_cms = :where_id AND lang = :where_lang',
					['where_id' => $idCms, 'where_lang' => $lang]
				);
			} else {
				DB::insert('cms_lang', array_merge($langRow, [
					'id_cms' => $idCms,
					'lang' => $lang,
				]));
			}
		}

		return self::ok('Sayfa kaydedildi', $idCms);
	}

	public static function delete(int $idCms): array
	{
		self::ensureSchema();

		if ($idCms <= 0) {
			return self::fail('Geçersiz sayfa');
		}

		DB::execute('DELETE FROM cms_lang WHERE id_cms = ?', [$idCms]);
		DB::execute('DELETE FROM cms_pages WHERE id_cms = ?', [$idCms]);

		return self::ok('Sayfa silindi');
	}

	public static function getLangRows(int $idCms): array
	{
		self::ensureSchema();
		$rows = DB::execute(
			'SELECT * FROM cms_lang WHERE id_cms = ?',
			[$idCms]
		) ?: [];

		$map = [];
		foreach ($rows as $row) {
			$map[(string) $row['lang']] = $row;
		}

		return $map;
	}

	private static function hydratePage(array $page, ?string $lang = null, bool $includeInactive = false): ?array
	{
		global $domain;

		if (!$includeInactive && (int) ($page['active'] ?? 0) !== 1) {
			return null;
		}

		$idCms = (int) $page['id_cms'];
		$lang = $lang ?: Lang::current();
		$defaultLang = Lang::getDefault();
		$langRow = null;

		foreach ([$lang, $defaultLang] as $tryLang) {
			$langRow = DB::getRowSafe('cms_lang', 'id_cms = ? AND lang = ?', [$idCms, $tryLang]);
			if ($langRow && trim((string) ($langRow['title'] ?? '')) !== '') {
				break;
			}
		}

		if (!$langRow) {
			$langRow = DB::execute(
				'SELECT * FROM cms_lang WHERE id_cms = ? ORDER BY lang ASC LIMIT 1',
				[$idCms]
			);
			$langRow = $langRow[0] ?? [
				'title' => '',
				'summary' => '',
				'content' => '',
				'meta_title' => '',
				'meta_description' => '',
			];
		}

		$slug = (string) ($langRow['slug'] ?? $page['slug'] ?? '');

		return [
			'id_cms' => $idCms,
			'slug' => $slug,
			'active' => (int) ($page['active'] ?? 1),
			'show_footer' => (int) ($page['show_footer'] ?? 1),
			'position' => (int) ($page['position'] ?? 0),
			'title' => (string) ($langRow['title'] ?? ''),
			'desc' => (string) ($langRow['summary'] ?? ''),
			'summary' => (string) ($langRow['summary'] ?? ''),
			'content' => (string) ($langRow['content'] ?? ''),
			'meta_title' => (string) ($langRow['meta_title'] ?? ''),
			'meta_description' => (string) ($langRow['meta_description'] ?? ''),
			'url' => rtrim($domain, '/') . '/' . $slug,
			'lang' => $lang,
		];
	}

	private static function migrateLegacyPages(): void
	{
		$count = (int) DB::getValue('SELECT COUNT(*) FROM cms_pages');

		if ($count > 0) {
			return;
		}

		$legacy = self::getLegacyPageDefinitions();
		$position = 0;

		foreach ($legacy as $slug => $meta) {
			$idCms = DB::insert('cms_pages', [
				'slug' => $slug,
				'active' => 1,
				'show_footer' => 1,
				'position' => $position++,
			]);

			if (!$idCms) {
				continue;
			}

			$content = self::readLegacyTemplateContent($slug);
			$seo = self::readLegacySeo($slug);

			foreach (Lang::getAvailable() as $lang) {
				DB::insert('cms_lang', [
					'id_cms' => (int) $idCms,
					'lang' => $lang,
					'slug' => $slug,
					'title' => $meta['title'],
					'summary' => $meta['desc'],
					'content' => $content,
					'meta_title' => $seo['meta_title'],
					'meta_description' => $seo['meta_description'],
				]);
			}
		}
	}

	private static function getLegacyPageDefinitions(): array
	{
		return [
			'hakkimizda' => [
				'title' => 'Hakkımızda',
				'desc' => 'FShop hakkında bilgi',
			],
			'mesafeli-satis' => [
				'title' => 'Mesafeli Satış Sözleşmesi',
				'desc' => 'Mesafeli satış sözleşmesi ve ön bilgilendirme',
			],
			'gizlilik' => [
				'title' => 'Gizlilik ve KVKK',
				'desc' => 'Kişisel verilerin korunması ve gizlilik politikası',
			],
			'iade-degisim' => [
				'title' => 'İade ve Değişim',
				'desc' => 'İade ve değişim koşulları',
			],
			'odeme-kargo' => [
				'title' => 'Ödeme ve Kargo',
				'desc' => 'Ödeme yöntemleri ve kargo bilgileri',
			],
		];
	}

	private static function readLegacyTemplateContent(string $slug): string
	{
		$legacy = self::getLegacyPageDefinitions();
		if (!isset($legacy[$slug])) {
			return '';
		}

		$theme = Settings::get('THEME') ?: 'default';
		$paths = [
			dirname(__DIR__) . '/templates/' . $theme . '/cms/' . $slug . '.tpl',
			dirname(__DIR__) . '/templates/default/cms/' . $slug . '.tpl',
		];

		foreach ($paths as $path) {
			if (is_file($path)) {
				return (string) file_get_contents($path);
			}
		}

		return '';
	}

	private static function readLegacySeo(string $slug): array
	{
		$table = DB::execute("SHOW TABLES LIKE 'cms_meta'");
		if (empty($table)) {
			return ['meta_title' => '', 'meta_description' => ''];
		}

		$row = DB::getRowSafe('cms_meta', 'slug = ?', [$slug]);

		return [
			'meta_title' => trim((string) ($row['meta_title'] ?? '')),
			'meta_description' => trim((string) ($row['meta_description'] ?? '')),
		];
	}

	private static function migrateLangSlug(): void
	{
		$slugCol = DB::execute("SHOW COLUMNS FROM `cms_lang` LIKE 'slug'");

		if (!empty($slugCol)) {
			return;
		}

		DB::execute(
			"ALTER TABLE `cms_lang`
			 ADD COLUMN `slug` varchar(128) NOT NULL DEFAULT '' AFTER `lang`"
		);

		DB::execute(
			'UPDATE cms_lang cl
			 INNER JOIN cms_pages p ON p.id_cms = cl.id_cms
			 SET cl.slug = p.slug
			 WHERE cl.slug = \'\''
		);

		$idx = DB::execute("SHOW INDEX FROM `cms_lang` WHERE Key_name = 'lang_slug'");

		if (empty($idx)) {
			DB::execute('ALTER TABLE `cms_lang` ADD UNIQUE KEY `lang_slug` (`lang`, `slug`)');
		}
	}

	private static function normalizeSlug(string $slug): string
	{
		return Tools::createSlug($slug);
	}

	private static function ok(string $message, int $idCms = 0): array
	{
		return [
			'success' => true,
			'message' => $message,
			'id_cms' => $idCms,
		];
	}

	private static function fail(string $message): array
	{
		return [
			'success' => false,
			'message' => $message,
			'id_cms' => 0,
		];
	}
}
