<?php

class Lang
{
	private static bool $schemaReady = false;

	public static function ensureSchema(): void
	{
		if (self::$schemaReady) {
			return;
		}

		self::$schemaReady = true;

		$productLang = DB::execute("SHOW TABLES LIKE 'product_lang'");
		if (empty($productLang)) {
			DB::execute(
				"CREATE TABLE `product_lang` (
					`id_product` int(11) NOT NULL,
					`lang` varchar(8) NOT NULL,
					`product_name` varchar(128) NOT NULL DEFAULT '',
					`product_link` varchar(128) NOT NULL DEFAULT '',
					`short_description` varchar(512) NOT NULL DEFAULT '',
					`description` mediumtext,
					`meta_title` varchar(255) NOT NULL DEFAULT '',
					`meta_description` varchar(512) NOT NULL DEFAULT '',
					PRIMARY KEY (`id_product`, `lang`),
					KEY `lang` (`lang`),
					UNIQUE KEY `lang_link` (`lang`, `product_link`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		} else {
			$productLink = DB::execute("SHOW COLUMNS FROM `product_lang` LIKE 'product_link'");
			if (empty($productLink)) {
				DB::execute(
					"ALTER TABLE `product_lang`
					 ADD COLUMN `product_link` varchar(128) NOT NULL DEFAULT '' AFTER `product_name`"
				);
				self::migrateProductLinks();
			}
		}

		$categoryLang = DB::execute("SHOW TABLES LIKE 'category_lang'");
		if (empty($categoryLang)) {
			DB::execute(
				"CREATE TABLE `category_lang` (
					`id_category` int(11) NOT NULL,
					`lang` varchar(8) NOT NULL,
					`category_name` varchar(64) NOT NULL DEFAULT '',
					`category_link` varchar(128) NOT NULL DEFAULT '',
					`meta_title` varchar(255) NOT NULL DEFAULT '',
					`meta_description` varchar(512) NOT NULL DEFAULT '',
					PRIMARY KEY (`id_category`, `lang`),
					KEY `lang` (`lang`),
					UNIQUE KEY `lang_link` (`lang`, `category_link`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		} else {
			$catLink = DB::execute("SHOW COLUMNS FROM `category_lang` LIKE 'category_link'");
			if (empty($catLink)) {
				DB::execute(
					"ALTER TABLE `category_lang`
					 ADD COLUMN `category_link` varchar(128) NOT NULL DEFAULT '' AFTER `category_name`"
				);
				self::migrateCategoryLinks();
			}
		}

		$brandLang = DB::execute("SHOW TABLES LIKE 'brand_lang'");
		if (empty($brandLang)) {
			DB::execute(
				"CREATE TABLE `brand_lang` (
					`id_brand` int(11) NOT NULL,
					`lang` varchar(8) NOT NULL,
					`brand_name` varchar(64) NOT NULL DEFAULT '',
					`brand_link` varchar(128) NOT NULL DEFAULT '',
					`meta_title` varchar(255) NOT NULL DEFAULT '',
					`meta_description` varchar(512) NOT NULL DEFAULT '',
					PRIMARY KEY (`id_brand`, `lang`),
					KEY `lang` (`lang`),
					UNIQUE KEY `lang_link` (`lang`, `brand_link`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
			self::migrateBrandLang();
		}

		}

	private static function migrateProductLinks(): void
	{
		$rows = DB::execute('SELECT id_product, product_name, product_link FROM products') ?: [];

		foreach ($rows as $row) {
			$id = (int) $row['id_product'];
			$link = (string) $row['product_link'];

			foreach (self::getAvailable() as $lang) {
				$exists = DB::getValue(
					'SELECT id_product FROM product_lang WHERE id_product = ? AND lang = ? LIMIT 1',
					[$id, $lang]
				);

				if ($exists === false) {
					DB::insert('product_lang', [
						'id_product' => $id,
						'lang' => $lang,
						'product_name' => (string) $row['product_name'],
						'product_link' => $link,
					]);
				} else {
					DB::execute(
						'UPDATE product_lang SET product_link = ? WHERE id_product = ? AND lang = ? AND product_link = \'\'',
						[$link, $id, $lang]
					);
				}
			}
		}
	}

	private static function migrateCategoryLinks(): void
	{
		$rows = DB::execute('SELECT id_category, category_name, category_link FROM categories') ?: [];

		foreach ($rows as $row) {
			$id = (int) $row['id_category'];
			$link = (string) $row['category_link'];

			foreach (self::getAvailable() as $lang) {
				$exists = DB::getValue(
					'SELECT id_category FROM category_lang WHERE id_category = ? AND lang = ? LIMIT 1',
					[$id, $lang]
				);

				if ($exists === false) {
					DB::insert('category_lang', [
						'id_category' => $id,
						'lang' => $lang,
						'category_name' => (string) $row['category_name'],
						'category_link' => $link,
					]);
				} else {
					DB::execute(
						'UPDATE category_lang SET category_link = ? WHERE id_category = ? AND lang = ? AND category_link = \'\'',
						[$link, $id, $lang]
					);
				}
			}
		}
	}

	private static function migrateBrandLang(): void
	{
		$rows = DB::execute('SELECT id_brand, brand_name, brand_link, meta_title, meta_description FROM brands') ?: [];

		foreach ($rows as $row) {
			$id = (int) $row['id_brand'];

			foreach (self::getAvailable() as $lang) {
				DB::insert('brand_lang', [
					'id_brand' => $id,
					'lang' => $lang,
					'brand_name' => (string) $row['brand_name'],
					'brand_link' => (string) $row['brand_link'],
					'meta_title' => (string) ($row['meta_title'] ?? ''),
					'meta_description' => (string) ($row['meta_description'] ?? ''),
				]);
			}
		}
	}

	public static function current(): string
	{
		global $selectLang;

		$lang = is_string($selectLang ?? null) ? trim($selectLang) : '';

		return self::isValid($lang) ? $lang : self::getDefault();
	}

	public static function handleSwitchRequest(): void
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			return;
		}

		if (!isset($_GET['set_lang'])) {
			return;
		}

		$code = strtolower(trim((string) $_GET['set_lang']));

		if (in_array($code, self::getAvailable(), true)) {
			$_SESSION['selectLang'] = $code;
		}

		$redirect = trim((string) ($_GET['redirect'] ?? ''));

		if ($redirect === '') {
			$redirect = '/';
		}

		if (strpos($redirect, '://') !== false || strncmp($redirect, '//', 2) === 0) {
			$redirect = '/';
		}

		$folder = rtrim((string) Settings::get('FOLDER'), '/');

		if ($folder !== '' && $folder !== '/' && strpos($redirect, $folder) === 0) {
			$redirect = substr($redirect, strlen($folder)) ?: '/';
		}

		if ($redirect === '' || $redirect[0] !== '/') {
			$redirect = '/' . ltrim($redirect, '/');
		}

		$domain = rtrim((string) Settings::get('DOMAIN'), '/');

		header('Location: ' . $domain . $redirect);
		exit;
	}

	public static function makeSwitchUrl(string $code): string
	{
		$code = strtolower(trim($code));
		$domain = rtrim((string) Settings::get('DOMAIN'), '/');
		$folder = rtrim((string) Settings::get('FOLDER'), '/');
		$redirect = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

		if ($folder !== '' && $folder !== '/' && strpos($redirect, $folder) === 0) {
			$redirect = substr($redirect, strlen($folder)) ?: '/';
		}

		if ($redirect === '' || $redirect[0] !== '/') {
			$redirect = '/' . ltrim($redirect, '/');
		}

		return $domain . '/?' . http_build_query([
			'set_lang' => $code,
			'redirect' => $redirect,
		]);
	}

	/** @return array<int, array{code: string, label: string, url: string, active: bool}> */
	public static function getSwitcherList(): array
	{
		$current = self::current();
		$list = [];

		foreach (self::getAvailable() as $code) {
			$list[] = [
				'code' => $code,
				'label' => self::label($code),
				'url' => self::makeSwitchUrl($code),
				'active' => $code === $current,
			];
		}

		return $list;
	}

	public static function getDefault(): string
	{
		$setting = trim((string) Settings::get('DEFAULT_LANG'));

		return self::isValid($setting) ? $setting : 'en';
	}

	/** @return string[] */
	public static function getAvailable(): array
	{
		$configured = trim((string) Settings::get('SHOP_LANGUAGES'));
		$langs = [];

		if ($configured !== '') {
			foreach (explode(',', $configured) as $code) {
				$code = strtolower(trim($code));
				if (self::isValid($code)) {
					$langs[] = $code;
				}
			}
		}

		if ($langs === []) {
			$dir = dirname(__DIR__) . '/lang';
			foreach (glob($dir . '/*.php') ?: [] as $file) {
				$code = basename($file, '.php');
				if ($code !== 'lang' && $code !== 'index' && self::isValid($code)) {
					$langs[] = $code;
				}
			}
		}

		if ($langs === []) {
			$langs = ['en'];
		}

		$default = self::getDefault();
		if (!in_array($default, $langs, true)) {
			array_unshift($langs, $default);
		}

		return array_values(array_unique($langs));
	}

	public static function label(string $code): string
	{
		$labels = self::getLabels();

		if (isset($labels[$code]) && $labels[$code] !== '') {
			return $labels[$code];
		}

		$builtIn = [
			'en' => 'English',
			'tr' => 'Türkçe',
			'de' => 'Deutsch',
			'fr' => 'Français',
			'es' => 'Español',
			'ar' => 'العربية',
		];

		return $builtIn[$code] ?? strtoupper($code);
	}

	/** @return array<string, string> */
	public static function getLabels(): array
	{
		$raw = trim((string) Settings::get('LANG_LABELS'));
		if ($raw === '') {
			return [];
		}

		$decoded = json_decode($raw, true);

		return is_array($decoded) ? $decoded : [];
	}

	/** @return array<int, array{code: string, label: string, is_default: bool, has_file: bool}> */
	public static function getAdminList(): array
	{
		$default = self::getDefault();
		$list = [];

		foreach (self::getAvailable() as $code) {
			$list[] = [
				'code' => $code,
				'label' => self::label($code),
				'is_default' => $code === $default,
				'has_file' => is_file(self::getLangFilePath($code)),
			];
		}

		return $list;
	}

	public static function addLanguage(string $code, string $label = ''): array
	{
		$code = strtolower(trim($code));

		if (!self::isValid($code)) {
			return self::fail('Geçersiz dil kodu (ör. en, tr, de)');
		}

		$langs = self::getAvailable();
		if (in_array($code, $langs, true)) {
			return self::fail('Bu dil zaten tanımlı');
		}

		$langs[] = $code;
		self::persistLanguages($langs);

		$labels = self::getLabels();
		$labels[$code] = $label !== '' ? $label : strtoupper($code);
		self::persistLabels($labels);

		if (!is_file(self::getLangFilePath($code))) {
			$template = self::buildNewLangFileContent($code);
			if (@file_put_contents(self::getLangFilePath($code), $template) === false) {
				return self::fail('Dil dosyası oluşturulamadı');
			}
		}

		if (class_exists('Cms', false)) {
			Cms::ensureLangSlots($code);
		} else {
			require_once dirname(__DIR__) . '/core/Cms.php';
			Cms::ensureLangSlots($code);
		}

		self::syncAllNewLangSlots($code);

		return self::ok('Dil eklendi');
	}

	public static function removeLanguage(string $code): array
	{
		$code = strtolower(trim($code));
		$langs = self::getAvailable();

		if (!in_array($code, $langs, true)) {
			return self::fail('Dil bulunamadı');
		}

		if (count($langs) <= 1) {
			return self::fail('Son dil silinemez');
		}

		if ($code === self::getDefault()) {
			return self::fail('Varsayılan dili silmeden önce başka bir dili varsayılan yapın');
		}

		$langs = array_values(array_filter($langs, static fn(string $l): bool => $l !== $code));
		self::persistLanguages($langs);

		$labels = self::getLabels();
		unset($labels[$code]);
		self::persistLabels($labels);

		self::deleteLangData($code);

		$file = self::getLangFilePath($code);
		if (is_file($file)) {
			@unlink($file);
		}

		return self::ok('Dil kaldırıldı');
	}

	public static function setDefaultLanguage(string $code): array
	{
		$code = strtolower(trim($code));

		if (!in_array($code, self::getAvailable(), true)) {
			return self::fail('Dil bulunamadı');
		}

		Settings::set('DEFAULT_LANG', $code);

		return self::ok('Varsayılan dil güncellendi');
	}

	public static function updateLabel(string $code, string $label): array
	{
		$code = strtolower(trim($code));

		if (!in_array($code, self::getAvailable(), true)) {
			return self::fail('Dil bulunamadı');
		}

		$labels = self::getLabels();
		$labels[$code] = trim($label) !== '' ? trim($label) : strtoupper($code);
		self::persistLabels($labels);

		return self::ok('Dil adı güncellendi');
	}

	private static function persistLanguages(array $langs): void
	{
		$langs = array_values(array_unique(array_filter($langs, static fn(string $c): bool => self::isValid($c))));
		Settings::set('SHOP_LANGUAGES', implode(',', $langs));
	}

	private static function persistLabels(array $labels): void
	{
		Settings::set('LANG_LABELS', json_encode($labels, JSON_UNESCAPED_UNICODE));
	}

	private static function getLangFilePath(string $code): string
	{
		return dirname(__DIR__) . '/lang/' . $code . '.php';
	}

	private static function buildNewLangFileContent(string $code): string
	{
		$label = self::label($code);

		return "<?php\n\n// UI translations for {$label} ({$code})\nreturn [\n];\n";
	}

	private static function deleteLangData(string $code): void
	{
		self::ensureSchema();
		DB::execute('DELETE FROM cms_lang WHERE lang = ?', [$code]);
		DB::execute('DELETE FROM product_lang WHERE lang = ?', [$code]);
		DB::execute('DELETE FROM category_lang WHERE lang = ?', [$code]);
		DB::execute('DELETE FROM brand_lang WHERE lang = ?', [$code]);
	}

	private static function ok(string $message): array
	{
		return ['success' => true, 'message' => $message];
	}

	private static function fail(string $message): array
	{
		return ['success' => false, 'message' => $message];
	}

	private static function isValid(string $code): bool
	{
		return $code !== '' && preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $code) === 1;
	}

	public static function getLocalizedRow(string $table, string $idField, int $id, array $fields): array
	{
		self::ensureSchema();

		$lang = self::current();
		$default = self::getDefault();
		$localized = [];

		foreach ([$lang, $default] as $tryLang) {
			if ($tryLang === '' || isset($localized['_resolved'])) {
				continue;
			}

			$row = DB::getRowSafe($table, $idField . ' = ? AND lang = ?', [$id, $tryLang]);

			if (!$row) {
				continue;
			}

			foreach ($fields as $field) {
				$value = trim((string) ($row[$field] ?? ''));
				if ($value !== '' && empty($localized[$field])) {
					$localized[$field] = $value;
				}
			}

			if ($localized !== []) {
				$localized['_resolved'] = true;
			}
		}

		unset($localized['_resolved']);

		return $localized;
	}

	public static function applyProduct(array $row): array
	{
		return self::applyProductForLang($row, self::current());
	}

	public static function applyProductForLang(array $row, string $lang): array
	{
		$id = (int) ($row['id_product'] ?? 0);

		if ($id <= 0) {
			return $row;
		}

		$lang = strtolower(trim($lang));
		$default = self::getDefault();
		$fields = [
			'product_name',
			'product_link',
			'short_description',
			'description',
			'meta_title',
			'meta_description',
		];
		$localized = [];

		foreach ([$lang, $default] as $tryLang) {
			if ($tryLang === '' || !in_array($tryLang, self::getAvailable(), true) || isset($localized['_resolved'])) {
				continue;
			}

			$dbRow = DB::getRowSafe('product_lang', 'id_product = ? AND lang = ?', [$id, $tryLang]);

			if (!$dbRow) {
				continue;
			}

			foreach ($fields as $field) {
				$value = trim((string) ($dbRow[$field] ?? ''));

				if ($value !== '' && empty($localized[$field])) {
					$localized[$field] = $value;
				}
			}

			if ($localized !== []) {
				$localized['_resolved'] = true;
			}
		}

		unset($localized['_resolved']);

		foreach ($localized as $field => $value) {
			$row[$field] = $value;
		}

		return $row;
	}

	public static function applyCategory(array $row): array
	{
		$id = (int) ($row['id_category'] ?? 0);

		if ($id <= 0) {
			return $row;
		}

		$localized = self::getLocalizedRow('category_lang', 'id_category', $id, [
			'category_name',
			'category_link',
			'meta_title',
			'meta_description',
		]);

		foreach ($localized as $field => $value) {
			$row[$field] = $value;
		}

		return $row;
	}

	public static function applyBrand(array $row): array
	{
		$id = (int) ($row['id_brand'] ?? 0);

		if ($id <= 0) {
			return $row;
		}

		$localized = self::getLocalizedRow('brand_lang', 'id_brand', $id, [
			'brand_name',
			'brand_link',
			'meta_title',
			'meta_description',
		]);

		foreach ($localized as $field => $value) {
			$row[$field] = $value;
		}

		return $row;
	}

	/** @return array<string, array<string, mixed>> */
	public static function getLangRowsMap(string $table, string $idField, int $id): array
	{
		self::ensureSchema();

		$rows = DB::execute(
			'SELECT * FROM `' . $table . '` WHERE `' . $idField . '` = ?',
			[$id]
		) ?: [];

		$map = [];

		foreach ($rows as $row) {
			$map[(string) $row['lang']] = $row;
		}

		return $map;
	}

	public static function saveLangRow(string $table, string $idField, int $id, string $lang, array $fields): void
	{
		self::ensureSchema();

		foreach (['product_link', 'category_link', 'brand_link'] as $linkField) {
			if (array_key_exists($linkField, $fields) && trim((string) $fields[$linkField]) === '') {
				return;
			}
		}

		$exists = DB::getValue(
			'SELECT `' . $idField . '` FROM `' . $table . '` WHERE `' . $idField . '` = ? AND `lang` = ? LIMIT 1',
			[$id, $lang]
		);

		if ($exists !== false) {
			DB::update(
				$table,
				$fields,
				'`' . $idField . '` = :where_id AND `lang` = :where_lang',
				['where_id' => $id, 'where_lang' => $lang]
			);

			return;
		}

		DB::insert($table, array_merge($fields, [
			$idField => $id,
			'lang' => $lang,
		]));
	}

	public static function bootstrapLangSlot(
		string $table,
		string $idField,
		int $id,
		string $lang,
		array $defaults
	): void {
		self::ensureSchema();

		$exists = DB::getValue(
			'SELECT `' . $idField . '` FROM `' . $table . '` WHERE `' . $idField . '` = ? AND `lang` = ? LIMIT 1',
			[$id, $lang]
		);

		if ($exists !== false) {
			return;
		}

		$defaultLang = self::getDefault();
		$source = null;

		if ($lang !== $defaultLang) {
			$source = DB::getRowSafe($table, $idField . ' = ? AND lang = ?', [$id, $defaultLang]);
		}

		$row = [];

		foreach ($defaults as $field => $value) {
			$fromSource = trim((string) ($source[$field] ?? ''));

			$row[$field] = $fromSource !== '' ? $fromSource : $value;
		}

		foreach (['product_link', 'category_link', 'brand_link'] as $linkField) {
			if (!array_key_exists($linkField, $row)) {
				continue;
			}

			if (trim((string) $row[$linkField]) !== '') {
				continue;
			}

			$nameField = str_replace('_link', '_name', $linkField);
			$name = trim((string) ($row[$nameField] ?? ''));

			if ($name !== '') {
				$row[$linkField] = Tools::createSlug($name);
			}
		}

		foreach (['product_link', 'category_link', 'brand_link'] as $linkField) {
			if (array_key_exists($linkField, $row) && trim((string) $row[$linkField]) === '') {
				return;
			}
		}

		DB::insert($table, array_merge($row, [
			$idField => $id,
			'lang' => $lang,
		]));
	}

	public static function syncAllNewLangSlots(string $lang): void
	{
		self::ensureSchema();

		foreach (DB::execute('SELECT * FROM products') ?: [] as $product) {
			self::bootstrapLangSlot('product_lang', 'id_product', (int) $product['id_product'], $lang, [
				'product_name' => (string) $product['product_name'],
				'product_link' => (string) $product['product_link'],
				'short_description' => (string) ($product['short_description'] ?? ''),
				'description' => (string) ($product['description'] ?? ''),
				'meta_title' => (string) ($product['meta_title'] ?? ''),
				'meta_description' => (string) ($product['meta_description'] ?? ''),
			]);
		}

		foreach (DB::execute('SELECT * FROM categories') ?: [] as $category) {
			self::bootstrapLangSlot('category_lang', 'id_category', (int) $category['id_category'], $lang, [
				'category_name' => (string) $category['category_name'],
				'category_link' => (string) $category['category_link'],
				'meta_title' => (string) ($category['meta_title'] ?? ''),
				'meta_description' => (string) ($category['meta_description'] ?? ''),
			]);
		}

		foreach (DB::execute('SELECT * FROM brands') ?: [] as $brand) {
			self::bootstrapLangSlot('brand_lang', 'id_brand', (int) $brand['id_brand'], $lang, [
				'brand_name' => (string) $brand['brand_name'],
				'brand_link' => (string) $brand['brand_link'],
				'meta_title' => (string) ($brand['meta_title'] ?? ''),
				'meta_description' => (string) ($brand['meta_description'] ?? ''),
			]);
		}
	}

	/**
	 * @return array<string, string>
	 */
	public static function loadUiDictionary(string $code): array
	{
		$code = strtolower(trim($code));

		if (!self::isValid($code)) {
			return [];
		}

		$path = self::getLangFilePath($code);

		if (!is_file($path)) {
			return [];
		}

		$map = include $path;

		if (!is_array($map)) {
			return [];
		}

		$out = [];

		foreach ($map as $key => $value) {
			if (!is_string($key)) {
				continue;
			}

			$out[$key] = is_scalar($value) ? (string) $value : '';
		}

		return $out;
	}

	/**
	 * Mevcut dosyayla birleştirerek kaydeder (gönderilmeyen anahtarlar silinmez).
	 *
	 * @param array<string, string> $updates
	 * @return array{success:bool,message:string}
	 */
	public static function mergeUiDictionary(string $code, array $updates): array
	{
		$code = strtolower(trim($code));

		if (!self::isValid($code)) {
			return self::fail('Geçersiz dil kodu');
		}

		if (!in_array($code, self::getAvailable(), true) && $code !== 'en') {
			return self::fail('Dil mağazada tanımlı değil');
		}

		$existing = self::loadUiDictionary($code);

		foreach ($updates as $key => $value) {
			if (!is_string($key) || $key === '') {
				continue;
			}

			$existing[$key] = trim((string) $value);
		}

		ksort($existing, SORT_STRING);

		$path = self::getLangFilePath($code);
		$dir = dirname($path);

		if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
			return self::fail('Dil klasörü oluşturulamadı');
		}

		$content = "<?php\n\n\treturn [\n";

		foreach ($existing as $key => $value) {
			$content .= "\t\t" . var_export($key, true) . ' => ' . var_export($value, true) . ",\n";
		}

		$content .= "\t];\n";

		if (@file_put_contents($path, $content) === false) {
			return self::fail('Dil dosyası yazılamadı');
		}

		return self::ok('Çeviriler kaydedildi');
	}

	/**
	 * Yeni mağaza UI çeviri anahtarı ekler (en.php + isteğe bağlı hedef dil).
	 *
	 * @return array{success:bool,message:string}
	 */
	public static function addUiTranslationKey(
		string $key,
		string $enValue,
		string $targetLang = '',
		string $targetValue = ''
	): array {
		$key = trim($key);
		$enValue = trim($enValue);
		$targetLang = strtolower(trim($targetLang));
		$targetValue = trim($targetValue);

		if ($key === '') {
			return self::fail('Translation key is required');
		}

		if (mb_strlen($key) > 512) {
			return self::fail('Translation key is too long');
		}

		if ($enValue === '') {
			$enValue = $key;
		}

		$existingEn = self::loadUiDictionary('en');

		if (isset($existingEn[$key])) {
			return self::fail('This key already exists');
		}

		$enResult = self::mergeUiDictionary('en', [$key => $enValue]);

		if (empty($enResult['success'])) {
			return $enResult;
		}

		if ($targetLang !== '' && $targetLang !== 'en' && $targetValue !== '') {
			if (!in_array($targetLang, self::getAvailable(), true)) {
				return self::ok('Translation key added');
			}

			$targetResult = self::mergeUiDictionary($targetLang, [$key => $targetValue]);

			if (empty($targetResult['success'])) {
				return $targetResult;
			}
		}

		return self::ok('Translation key added');
	}

	/**
	 * İngilizce (en.php) kaynak; hedef dil değerleriyle satır listesi.
	 *
	 * @return array{
	 *   rows: list<array{key:string,en:string,translation:string,missing:bool}>,
	 *   total:int,
	 *   missing:int
	 * }
	 */
	public static function getUiTranslationWorkspace(string $targetLang, string $filter = 'all', string $q = ''): array
	{
		$targetLang = strtolower(trim($targetLang));
		$en = self::loadUiDictionary('en');
		$target = self::loadUiDictionary($targetLang);
		$q = mb_strtolower(trim($q));
		$filter = $filter === 'missing' ? 'missing' : 'all';

		// en'de olmayan ama hedefte olan anahtarları da göster (orphan)
		$keys = array_unique(array_merge(array_keys($en), array_keys($target)));
		sort($keys, SORT_STRING);

		$rows = [];
		$missingCount = 0;

		foreach ($keys as $key) {
			$enValue = $en[$key] ?? $key;
			$trValue = $target[$key] ?? '';
			$isMissing = $trValue === '' || ($targetLang !== 'en' && $trValue === $key);

			if ($isMissing) {
				$missingCount++;
			}

			if ($filter === 'missing' && !$isMissing) {
				continue;
			}

			if ($q !== '') {
				$hay = mb_strtolower($key . ' ' . $enValue . ' ' . $trValue);

				if (mb_strpos($hay, $q) === false) {
					continue;
				}
			}

			$rows[] = [
				'key' => $key,
				'en' => $enValue,
				'translation' => $trValue,
				'missing' => $isMissing,
			];
		}

		return [
			'rows' => $rows,
			'total' => count($keys),
			'missing' => $missingCount,
		];
	}

	public static function translate(string $text): string
	{
		return self::translateFor($text, self::current());
	}

	public static function translateFor(string $text, string $langCode): string
	{
		static $cache = [];

		$langCode = strtolower(trim($langCode));

		if (!self::isValid($langCode)) {
			$langCode = self::getDefault();
		}

		if (!isset($cache[$langCode])) {
			$path = dirname(__DIR__) . '/lang/' . $langCode . '.php';
			$cache[$langCode] = is_file($path) ? require $path : [];

			if (!is_array($cache[$langCode])) {
				$cache[$langCode] = [];
			}
		}

		return $cache[$langCode][$text] ?? $text;
	}
}
