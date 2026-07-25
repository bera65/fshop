<?php

class Module
{
	private const HOOKS = [
		'smarty.assign',
		'head.assets',
		'footer.html',
		'admin.menu',
		'order.placed',
		'product.updated',
		'order.updated',
		'form.captcha.validate',
		'product.filter.sql',
		'catalog.filter.context',
		'cart.changed',
	];

	/** @var array<string, ModuleBase> */
	private static array $instances = [];

	/** @var array<string, array<int, callable>> */
	private static array $hookListeners = [];

	private static bool $booted = false;

	private const MAX_ZIP_BYTES = 52428800;

	public static function rootPath(): string
	{
		return dirname(__DIR__) . '/modules';
	}

	public static function path(string $name): string
	{
		return self::rootPath() . '/' . $name;
	}

	public static function ensureSchema(): void
	{
		DB::execute(
			'CREATE TABLE IF NOT EXISTS `modules` (
			  `id_module` int(11) NOT NULL AUTO_INCREMENT,
			  `name` varchar(64) NOT NULL,
			  `version` varchar(16) NOT NULL DEFAULT \'1.0.0\',
			  `active` tinyint(1) NOT NULL DEFAULT 0,
			  `installed` tinyint(1) NOT NULL DEFAULT 0,
			  `date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  PRIMARY KEY (`id_module`),
			  UNIQUE KEY `name` (`name`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		);

		DB::execute(
			'CREATE TABLE IF NOT EXISTS `module_display_hooks` (
			  `id_hook` int(11) NOT NULL AUTO_INCREMENT,
			  `module_name` varchar(64) NOT NULL,
			  `hook_name` varchar(32) NOT NULL,
			  `position` int(11) NOT NULL DEFAULT 0,
			  PRIMARY KEY (`id_hook`),
			  UNIQUE KEY `module_hook` (`module_name`, `hook_name`),
			  KEY `hook_name` (`hook_name`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		);

		self::migrateDisplayHooks();
	}

	private static function migrateDisplayHooks(): void
	{
		if (!self::isInstalled('newsletter')) {
			return;
		}

		if (self::getAssignedDisplayHooks('newsletter') !== []) {
			return;
		}

		$module = self::loadInstance('newsletter', false);

		if ($module) {
			self::assignDefaultDisplayHooks($module);
		}
	}

	public static function bootstrap(string $context = 'front'): void
	{
		if (self::$booted) {
			return;
		}

		self::ensureSchema();
		self::$booted = true;
		self::$instances = [];
		self::$hookListeners = [];

		foreach (self::getEnabledNames() as $name) {
			$module = self::loadInstance($name);

			if ($module) {
				$module->boot();
			}
		}

		self::runHook('smarty.assign', [$GLOBALS['smarty'] ?? null]);
	}

	public static function discover(): array
	{
		$dir = self::rootPath();

		if (!is_dir($dir)) {
			return [];
		}

		$discovered = [];

		foreach (scandir($dir) ?: [] as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}

			$moduleFile = $dir . '/' . $entry . '/' . $entry . '.php';

			if (!is_file($moduleFile)) {
				continue;
			}

			$instance = self::loadInstance($entry, false);

			if ($instance) {
				$discovered[$entry] = array_merge($instance->toArray(), self::getDbRow($entry) ?: [
					'installed' => 0,
					'active' => 0,
				]);
			}
		}

		return $discovered;
	}

	public static function getHookCatalog(): array
	{
		return [
			'smarty.assign' => 'Mağaza şablonlarına değişken ekler (footer, header vb.)',
			'head.assets' => 'Sayfaya ek CSS/JS yükler',
			'footer.html' => 'Footer alanına HTML ekler (eski; tercih: display hook)',
			'admin.menu' => 'Admin sidebar menüsüne öğe ekler (registerAdminMenuLink veya registerHook)',
			'order.placed' => 'Sipariş oluşturulunca tetiklenir',
			'product.updated' => 'Ürün admin veya API üzerinden kaydedilince tetiklenir',
			'order.updated' => 'Sipariş admin veya API üzerinden güncellenince tetiklenir',
			'form.captcha.validate' => 'Form gönderiminde CAPTCHA doğrulaması (modül dinler)',
		];
	}

	/** @return array<string, string> Şablonda {$hooks.footer} ile kullanılır */
	public static function getDisplayHookCatalog(): array
	{
		return [
			'footer' 			=> 'Footer — {$hooks.footer}',
			'header' 			=> 'Üst bar — {$hooks.header}',
			'main_menu' 		=> 'Ana menü (kategori menüsü) — {$hooks.main_menu}',
			'mobile_menu' 		=> 'Mobil menü (drawer) — {$hooks.mobile_menu}',
			'head.top' 			=> 'Head üst alanı — {$hooks.head.top}',
			'home' 				=> 'Ana sayfa — {$hooks.home}',
			'home_slider' 		=> 'Ana sayfa üst slayt — {$hooks.home_slider}',
			'home_promo_slider' => 'Ana sayfa kampanya slaytı — {$hooks.home_promo_slider}',
			'home_bottom'       => 'Ana sayfa alt bölüm — {$hooks.home_bottom}',
			'catalog_filters'   => 'Kategori filtreleri — {$hooks.catalog_filters}',
			'product' 			=> 'Ürün sayfası — {$hooks.product}',
			'product_detail' 	=> 'Ürün detay sayfası — {$hooks.product_detail}',
			'product_tab' 		=> 'Ürün Tabı (sekme butonu) — {$hooks.product_tab}',
			'product_tab_content' => 'Ürün Tabı (sekme içeriği) — {$hooks.product_tab_content}',
			'product_inf' 		=> 'Ürün detay — {$hooks.product_inf}',
			'order_payment' 	=> 'Ödeme Modülü — {$hooks.order_payment}',
			'order_confirmation' => 'Sipariş onay sayfası — {$hooks.order_confirmation}',
			'auth_social'       => 'Giriş / kayıt — sosyal butonlar — {$hooks.auth_social}',
			'contact_form'      => 'İletişim formu — CAPTCHA / ek alan — {$hooks.contact_form}',
			'auth_login'        => 'Giriş formu — CAPTCHA — {$hooks.auth_login}',
			'auth_register'     => 'Kayıt formu — CAPTCHA — {$hooks.auth_register}',
			'admin_login'       => 'Admin giriş formu — CAPTCHA — {$adminHooks.admin_login}',
			'admin_product_button' => 'Admin ürün düzenleme — Kaydet butonu yanı — {$adminHooks.admin_product_button}',
			'admin_order_detail' => 'Admin sipariş detay — {$adminHooks.admin_order_detail}',
			'admin_header' => 'Admin orta alan üstü (tüm sayfalar) — {$adminHooks.admin_header}',
			'admin_footer' => 'Admin footer (tüm sayfalar) — {$adminHooks.admin_footer}',
			'admin_dashboard_top' => 'Admin gösterge paneli — üst alan — {$adminHooks.admin_dashboard_top}',
			'admin_dashboard_kpi' => 'Admin gösterge paneli — KPI kartları altı — {$adminHooks.admin_dashboard_kpi}',
			'admin_dashboard_main_left' => 'Admin gösterge paneli — sol sütun — {$adminHooks.admin_dashboard_main_left}',
			'admin_dashboard_main_right' => 'Admin gösterge paneli — sağ sütun — {$adminHooks.admin_dashboard_main_right}',
			'admin_dashboard_bottom' => 'Admin gösterge paneli — sayfa altı — {$adminHooks.admin_dashboard_bottom}',
		];
	}

	/**
	 * @param string[] $hookNames
	 * @param array<string, mixed> $context
	 * @return array<string, string>
	 */
	public static function renderAdminHooks(array $hookNames, array $context = []): array
	{
		$hooks = [];

		foreach ($hookNames as $hookName) {
			$hookName = trim((string) $hookName);

			if ($hookName === '') {
				continue;
			}

			$hooks[$hookName] = self::renderDisplayHook($hookName, $context);
		}

		return $hooks;
	}

	/** Sayfa bağlamlı hook'ları günceller (ör. ürün sayfası) */
	public static function refreshHook($smarty, string $hookName, array $context = []): void
	{
		$hooks = $smarty->getTemplateVars('hooks');

		if (!is_array($hooks)) {
			$hooks = [];
		}

		self::assignHookValue($hooks, $hookName, self::renderDisplayHook($hookName, $context));
		$smarty->assign('hooks', $hooks);
	}

	public static function getAssignedDisplayHooks(string $name): array
	{
		$rows = DB::execute(
			'SELECT hook_name FROM module_display_hooks WHERE module_name = ? ORDER BY position ASC, hook_name ASC',
			[$name]
		) ?: [];

		return array_column($rows, 'hook_name');
	}

	public static function setDisplayHooks(string $name, array $hookNames): array
	{
		$module = self::loadInstance($name, false);

		if (!$module) {
			return self::fail('Modül bulunamadı');
		}

		if (!self::isInstalled($name)) {
			return self::fail('Önce modülü kurun');
		}

		$allowed = $module->getSupportedDisplayHooks();
		$catalog = array_keys(self::getDisplayHookCatalog());
		$valid = [];

		foreach ($hookNames as $hook) {
			$hook = trim((string) $hook);

			if ($hook !== '' && in_array($hook, $allowed, true) && in_array($hook, $catalog, true)) {
				$valid[] = $hook;
			}
		}

		$valid = array_values(array_unique($valid));

		DB::execute('DELETE FROM module_display_hooks WHERE module_name = ?', [$name]);

		foreach ($valid as $position => $hook) {
			DB::insert('module_display_hooks', [
				'module_name' => $name,
				'hook_name' => $hook,
				'position' => $position,
			]);
		}

		return self::ok('Hook atamaları kaydedildi');
	}

	public static function renderDisplayHook(string $hookName, array $context = []): string
	{
		if (!isset(self::getDisplayHookCatalog()[$hookName])) {
			return '';
		}

		if ($hookName === 'product' && empty($context['id_product'])) {
			return '';
		}

		$rows = DB::execute(
			'SELECT mh.module_name
			 FROM module_display_hooks mh
			 INNER JOIN modules m ON m.name = mh.module_name
			 WHERE mh.hook_name = ? AND m.installed = 1 AND m.active = 1
			 ORDER BY mh.position ASC, mh.module_name ASC',
			[$hookName]
		) ?: [];

		$html = '';

		foreach ($rows as $row) {
			$module = self::loadInstance($row['module_name']);

			if (!$module) {
				continue;
			}

			if (strpos($hookName, 'admin_') === 0) {
				$chunk = $module->renderAdminDisplayHook($hookName, $context);
			} else {
				$chunk = $module->renderDisplayHook($hookName, $context);
			}

			if ($chunk !== null && $chunk !== '') {
				$html .= $chunk;
			}
		}

		return $html;
	}

	/** @return array<string, string> */
	public static function getRenderedDisplayHooks(): array
	{
		$hooks = [];
		$deferred = [
			'product',
			'product_tab',
			'product_tab_content',
			'product_inf',
			'order_payment',
			'order_confirmation',
			'auth_social',
			'admin_product_button',
			'admin_order_detail',
			'admin_header',
			'admin_footer',
			'admin_dashboard_top',
			'admin_dashboard_kpi',
			'admin_dashboard_main_left',
			'admin_dashboard_main_right',
			'admin_dashboard_bottom',
		];

		foreach (array_keys(self::getDisplayHookCatalog()) as $hookName) {
			self::assignHookValue(
				$hooks,
				$hookName,
				in_array($hookName, $deferred, true)
					? ''
					: self::renderDisplayHook($hookName)
			);
		}

		return $hooks;
	}

	private static function assignHookValue(array &$hooks, string $hookName, string $value): void
	{
		if (strpos($hookName, '.') === false) {
			$hooks[$hookName] = $value;

			return;
		}

		$parts = explode('.', $hookName);
		$leaf = array_pop($parts);
		$ref = &$hooks;

		foreach ($parts as $part) {
			if (!isset($ref[$part]) || !is_array($ref[$part])) {
				$ref[$part] = [];
			}

			$ref = &$ref[$part];
		}

		$ref[$leaf] = $value;
	}

	public static function getAdminList(): array
	{
		$list = [];
		$domain = rtrim(Settings::get('DOMAIN'), '/');

		foreach (self::discover() as $name => $meta) {
			$instance = self::loadInstance($name, false);
			$row = self::getDbRow($name) ?: ['installed' => 0, 'active' => 0, 'version' => $meta['version']];
			$installed = (int) $row['installed'] === 1;
			$active = $installed && (int) $row['active'] === 1;
			$list[] = array_merge($meta, [
				'installed' => $installed,
				'active' => $active,
				'db_version' => $row['version'] ?? $meta['version'],
				'detail_url' => Admin::url('module?name=' . rawurlencode($name)),
				'configure_url' => Admin::url('module-' . $name),
				'has_configure' => $installed,
				'assigned_hooks' => self::getAssignedDisplayHooks($name),
				'icon_url' => $instance ? $instance->getLogoUrl() : '',
				'icon_letter' => mb_strtoupper(mb_substr($meta['title'], 0, 1)),
			]);
		}

		usort($list, static fn($a, $b) => strcmp($a['title'], $b['title']));

		return $list;
	}

	/**
	 * @return array{total: int, installed: int, active: int, inactive: int, not_installed: int}
	 */
	public static function getAdminStats(): array
	{
		$list = self::getAdminList();
		$total = count($list);
		$installed = 0;
		$active = 0;

		foreach ($list as $row) {
			if (!empty($row['installed'])) {
				++$installed;
			}

			if (!empty($row['active'])) {
				++$active;
			}
		}

		return [
			'total' => $total,
			'installed' => $installed,
			'active' => $active,
			'inactive' => max(0, $installed - $active),
			'not_installed' => max(0, $total - $installed),
		];
	}

	public static function getDetail(string $name): ?array
	{
		$instance = self::loadInstance($name, false);

		if (!$instance) {
			return null;
		}

		$row = self::getDbRow($name) ?: ['installed' => 0, 'active' => 0, 'version' => $instance->version];
		$installed = (int) $row['installed'] === 1;
		$active = $installed && (int) $row['active'] === 1;

		$adminPages = [];

		foreach ($instance->getAdminPageDefinitions() as $page) {
			$adminPages[] = [
				'slug' => $page['slug'],
				'title' => $page['title'],
				'description' => $page['description'],
				'url' => Admin::url($page['slug']),
				'usable' => $active,
			];
		}

		$apiActions = [];

		foreach ($instance->apiActions as $action => $file) {
			$apiActions[] = [
				'action' => $action,
				'endpoint' => rtrim(Settings::get('DOMAIN'), '/') . '/api/module.php?m='
					. rawurlencode($name) . '&action=' . rawurlencode($action),
			];
		}

		$displayHooks = $instance->displayHooks !== []
			? $instance->displayHooks
			: $instance->positions;

		return array_merge($instance->toArray(), [
			'installed' => $installed,
			'active' => $active,
			'db_version' => $row['version'] ?? $instance->version,
			'detail_url' => Admin::url('module?name=' . rawurlencode($name)),
			'configure_url' => Admin::url('module-' . $name),
			'admin_pages' => $adminPages,
			'api_actions' => $apiActions,
			'display_hooks' => $displayHooks,
			'assigned_hooks' => self::getAssignedDisplayHooks($name),
			'assigned_hooks_map' => array_fill_keys(self::getAssignedDisplayHooks($name), true),
			'hooks_meta' => $instance->hooksMeta,
			'has_admin_ui' => true,
			'logo_url' => $instance->getLogoUrl(),
		]);
	}

	public static function install(string $name): array
	{
		$module = self::loadInstance($name, false);

		if (!$module) {
			return self::fail('Modül bulunamadı');
		}

		if (self::isInstalled($name)) {
			return self::fail('Modül zaten kurulu');
		}

		if (!$module->install()) {
			return self::fail('Kurulum SQL hatası');
		}

		$ok = DB::insert('modules', [
			'name' => $name,
			'version' => $module->version,
			'active' => 1,
			'installed' => 1,
		]);

		if (!$ok) {
			return self::fail('Veritabanı kaydı oluşturulamadı');
		}

		self::assignDefaultDisplayHooks($module);
		self::$booted = false;

		return self::ok('Modül kuruldu ve etkinleştirildi');
	}

	/**
	 * ZIP arşivinden modül yükler (modules/{ad}/).
	 *
	 * @param array<string, mixed> $file $_FILES entry
	 * @return array{success: bool, message: string, name?: string}
	 */
	public static function installFromZip(array $file): array
	{
		if (class_exists('Admin', false) && Admin::isDemoMode()) {
			return self::fail(adminT('Demo mode: module upload is not allowed'));
		}

		if (!class_exists('ZipArchive')) {
			return self::fail('Sunucuda ZipArchive eklentisi yok');
		}

		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
			return self::fail('ZIP dosyası seçilmedi');
		}

		if (!empty($file['error'])) {
			return self::fail('ZIP yüklenemedi');
		}

		if (($file['size'] ?? 0) > self::MAX_ZIP_BYTES) {
			return self::fail('ZIP dosyası en fazla 50 MB olabilir');
		}

		$ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

		if ($ext !== 'zip') {
			return self::fail('Yalnızca .zip dosyası yükleyebilirsiniz');
		}

		$zip = new ZipArchive();
		$opened = $zip->open($file['tmp_name']);

		if ($opened !== true) {
			return self::fail('ZIP dosyası açılamadı');
		}

		$detected = self::detectZipModuleRoot($zip);

		if ($detected === null) {
			$zip->close();

			return self::fail('Geçersiz modül ZIP\'i. Klasör yapısı: modul-adi/modul-adi.php olmalı');
		}

		$name = $detected['name'];
		$prefix = $detected['prefix'];
		$targetDir = self::rootPath() . '/' . $name;

		if (is_dir($targetDir)) {
			$zip->close();

			return self::fail('Bu isimde bir modül zaten mevcut: ' . $name);
		}

		$tempDir = sys_get_temp_dir() . '/fshop-module-' . bin2hex(random_bytes(8));

		if (!mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
			$zip->close();

			return self::fail('Geçici klasör oluşturulamadı');
		}

		for ($i = 0; $i < $zip->numFiles; $i++) {
			$entry = str_replace('\\', '/', (string) $zip->getNameIndex($i));

			if (!self::isSafeZipEntry($entry, $prefix)) {
				self::removeDirectory($tempDir);
				$zip->close();

				return self::fail('ZIP içinde güvenli olmayan dosya yolu');
			}

			$relative = $prefix === '' ? $entry : substr($entry, strlen($prefix));

			if ($relative === '' || substr($relative, -1) === '/') {
				continue;
			}

			$dest = $tempDir . '/' . $relative;
			$destDir = dirname($dest);

			if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
				self::removeDirectory($tempDir);
				$zip->close();

				return self::fail('Modül dosyaları çıkarılamadı');
			}

			$contents = $zip->getFromIndex($i);

			if ($contents === false || file_put_contents($dest, $contents) === false) {
				self::removeDirectory($tempDir);
				$zip->close();

				return self::fail('Modül dosyaları yazılamadı');
			}
		}

		$zip->close();

		if (!is_file($tempDir . '/' . $name . '.php')) {
			self::removeDirectory($tempDir);

			return self::fail('Modül ana dosyası bulunamadı: ' . $name . '/' . $name . '.php');
		}

		$modulesRoot = self::rootPath();

		if (!is_dir($modulesRoot) && !mkdir($modulesRoot, 0755, true) && !is_dir($modulesRoot)) {
			self::removeDirectory($tempDir);

			return self::fail('modules/ klasörü oluşturulamadı');
		}

		if (!self::copyDirectory($tempDir, $targetDir)) {
			self::removeDirectory($tempDir);

			return self::fail('Modül klasörü taşınamadı');
		}

		self::removeDirectory($tempDir);

		$module = self::loadInstance($name, false);

		if (!$module) {
			self::removeDirectory($targetDir);

			return self::fail('Modül yüklendi ancak sınıf okunamadı. Dosya adı ve ModuleBase uyumunu kontrol edin');
		}

		return [
			'success' => true,
			'message' => 'Modül yüklendi: ' . $module->title . '. Şimdi kurabilirsiniz.',
			'name' => $name,
		];
	}

	public static function uninstall(string $name): array
	{
		if (!self::isInstalled($name)) {
			return self::fail('Modül kurulu değil');
		}

		$module = self::loadInstance($name, false);

		if ($module && !$module->uninstall()) {
			return self::fail('Kaldırma işlemi başarısız');
		}

		DB::execute('DELETE FROM modules WHERE name = ?', [$name]);
		DB::execute('DELETE FROM module_display_hooks WHERE module_name = ?', [$name]);
		unset(self::$instances[$name]);
		self::$booted = false;

		return self::ok('Modül kaldırıldı');
	}

	public static function setActive(string $name, bool $active): array
	{
		if (!self::isInstalled($name)) {
			return self::fail('Önce modülü kurun');
		}

		DB::update('modules', ['active' => $active ? 1 : 0], 'name = :where_name', ['where_name' => $name]);
		self::$booted = false;

		return self::ok($active ? 'Modül etkinleştirildi' : 'Modül devre dışı bırakıldı');
	}

	public static function isInstalled(string $name): bool
	{
		$row = self::getDbRow($name);

		return $row && (int) $row['installed'] === 1;
	}

	public static function isEnabled(string $name): bool
	{
		$row = self::getDbRow($name);

		return $row && (int) $row['installed'] === 1 && (int) $row['active'] === 1;
	}

	/** Modül sınıf örneği (admin route, özel sayfalar). */
	public static function getInstance(string $name, bool $cache = true): ?ModuleBase
	{
		if (!self::isInstalled($name)) {
			return null;
		}

		return self::loadInstance($name, $cache);
	}

	public static function resolveFrontRoute(string $slug): ?string
	{
		foreach (self::getEnabledInstances() as $module) {
			if (!isset($module->routes[$slug])) {
				continue;
			}

			$file = $module->getPath() . '/' . ltrim($module->routes[$slug], '/');

			return is_file($file) ? $file : null;
		}

		return null;
	}

	public static function resolveAdminModuleName(string $slug): ?string
	{
		if (strpos($slug, 'module-') !== 0) {
			return null;
		}

		$name = substr($slug, 7);

		if ($name === '' || !preg_match('/^[a-z0-9\-]+$/', $name)) {
			return null;
		}

		if (!is_file(self::path($name) . '/' . $name . '.php')) {
			return null;
		}

		return $name;
	}

	public static function dispatchAdminPage(string $name): void
	{
		if (!self::isInstalled($name)) {
			http_response_code(404);
			AdminPage::add('404', 'Modül kurulu değil');

			return;
		}

		$module = self::loadInstance($name, false);

		if (!$module) {
			http_response_code(404);
			AdminPage::add('404', 'Modül bulunamadı');

			return;
		}

		$module->adminPage();
		AdminPage::addModule($module);
	}

	/** @deprecated Eski modüller için dosya tabanlı admin route */
	public static function resolveAdminRoute(string $slug): ?string
	{
		foreach (self::getEnabledInstances() as $module) {
			foreach ($module->adminPages as $page) {
				if (($page['slug'] ?? '') !== $slug || empty($page['file'])) {
					continue;
				}

				$file = $module->getPath() . '/' . ltrim($page['file'], '/');

				return is_file($file) ? $file : null;
			}

			foreach ($module->adminRoutes as $pageSlug => $file) {
				if ($pageSlug !== $slug) {
					continue;
				}

				$path = $module->getPath() . '/' . ltrim($file, '/');

				return is_file($path) ? $path : null;
			}
		}

		return null;
	}

	public static function dispatchApi(string $moduleName, string $action): void
	{
		if (!self::isEnabled($moduleName)) {
			self::apiResponse(['success' => false, 'message' => 'Modül aktif değil'], 404);
		}

		$module = self::loadInstance($moduleName);

		if (!$module || !isset($module->apiActions[$action])) {
			self::apiResponse(['success' => false, 'message' => 'Geçersiz işlem'], 404);
		}

		$file = $module->getPath() . '/' . ltrim($module->apiActions[$action], '/');

		if (!is_file($file)) {
			self::apiResponse(['success' => false, 'message' => 'Endpoint bulunamadı'], 404);
		}

		require $file;
		exit;
	}

	/**
	 * Aktif ödeme modüllerinin yöntemleri.
	 * @return array<string, array{id: string, label: string, module: string}>
	 */
	public static function getPaymentMethods(): array
	{
		$methods = [];

		foreach (self::getEnabledInstances() as $module) {
			if (!$module->isPayment || $module->paymentMethodId === '') {
				continue;
			}

			$methods[$module->paymentMethodId] = [
				'id' => $module->paymentMethodId,
				'label' => $module->getPaymentMethodLabel(),
				'module' => $module->name,
			];
		}

		return $methods;
	}

	/** Ödeme yöntemi kimliğinden modül örneğini döndürür */
	public static function getPaymentModule(string $methodId): ?ModuleBase
	{
		foreach (self::getEnabledInstances() as $module) {
			if ($module->isPayment && $module->paymentMethodId === $methodId) {
				return $module;
			}
		}

		return null;
	}

	/**
	 * Seçilen ödeme yönteminin indirimi.
	 *
	 * @return array{amount:float,label:string,percent:float}
	 */
	public static function getPaymentDiscount(string $methodId, float $amount): array
	{
		$empty = [
			'amount' => 0.0,
			'label' => '',
			'percent' => 0.0,
		];

		if ($methodId === '' || $amount <= 0) {
			return $empty;
		}

		$module = self::getPaymentModule($methodId);

		if (!$module) {
			return $empty;
		}

		$result = $module->getPaymentDiscount($amount);

		return [
			'amount' => max(0.0, (float) ($result['amount'] ?? 0)),
			'label' => (string) ($result['label'] ?? ''),
			'percent' => max(0.0, (float) ($result['percent'] ?? 0)),
		];
	}

	public static function registerHook(string $hook, callable $listener): void
	{
		if (!in_array($hook, self::HOOKS, true)) {
			return;
		}

		self::$hookListeners[$hook][] = $listener;
	}

	public static function runHook(string $hook, array $args = []): array
	{
		$results = [];

		foreach (self::$hookListeners[$hook] ?? [] as $listener) {
			$result = $listener(...$args);

			if ($result !== null && $result !== '') {
				$results[] = $result;
			}
		}

		return $results;
	}

	/**
	 * Admin sidebar items from the admin.menu hook, grouped for header.tpl.
	 *
	 * @return array{general: array<int, array<string, mixed>>, catalog: array<int, array<string, mixed>>, system: array<int, array<string, mixed>>, sales: array<int, array<string, mixed>>, marketplace: array<int, array<string, mixed>>}
	 */
	public static function getAdminMenuItems(): array
	{
		$items = [];
		$returns = self::runHook('admin.menu', [&$items]);

		foreach ($returns as $result) {
			if (!is_array($result)) {
				continue;
			}

			if (isset($result['label'])) {
				$items[] = $result;
				continue;
			}

			foreach ($result as $row) {
				if (is_array($row) && isset($row['label'])) {
					$items[] = $row;
				}
			}
		}

		$normalized = [];

		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}

			$label = trim((string) ($item['label'] ?? ''));
			$url = trim((string) ($item['url'] ?? ''));

			if ($label === '' || $url === '') {
				continue;
			}

			$group = strtolower(trim((string) ($item['group'] ?? 'system')));

			if (!in_array($group, ['general', 'catalog', 'system', 'sales', 'marketplace'], true)) {
				$group = 'system';
			}

			$slug = trim((string) ($item['slug'] ?? ''));

			if ($slug === '') {
				$slug = self::inferAdminMenuSlug($url);
			}

			$normalized[] = [
				'label' => $label,
				'url' => $url,
				'slug' => $slug,
				'group' => $group,
				'position' => (int) ($item['position'] ?? 100),
				'badge' => max(0, (int) ($item['badge'] ?? 0)),
				'target' => trim((string) ($item['target'] ?? '')),
			];
		}

		usort($normalized, static function (array $a, array $b): int {
			if ($a['group'] !== $b['group']) {
				return strcmp($a['group'], $b['group']);
			}

			if ($a['position'] !== $b['position']) {
				return $a['position'] <=> $b['position'];
			}

			return strcmp($a['label'], $b['label']);
		});

		$grouped = [
			'general' => [],
			'sales' => [],
			'catalog' => [],
			'marketplace' => [],
			'system' => [],
		];

		foreach ($normalized as $item) {
			$grouped[$item['group']][] = $item;
		}

		return $grouped;
	}

	private static function inferAdminMenuSlug(string $url): string
	{
		$path = (string) (parse_url($url, PHP_URL_PATH) ?: '');

		if (preg_match('#/admin/([^/?]+)#', $path, $matches)) {
			return $matches[1];
		}

		$path = trim($path, '/');

		return $path !== '' ? basename(str_replace('\\', '/', $path)) : '';
	}

	public static function getHeadAssets(): array
	{
		$assets = ['css' => [], 'js' => []];

		foreach (self::getEnabledInstances() as $module) {
			$assets['css'] = array_merge($assets['css'], $module->getFrontStylesheets());
			$assets['js'] = array_merge($assets['js'], $module->getFrontScripts());
		}

		self::runHook('head.assets', [&$assets]);

		foreach (['css', 'js'] as $type) {
			foreach ($assets[$type] as $index => $url) {
				$assets[$type][$index] = Performance::versionedUrl((string) $url);
			}

			$assets[$type] = array_values(array_unique($assets[$type]));
		}

		return $assets;
	}

	private static function loadInstance(string $name, bool $cache = true): ?ModuleBase
	{
		if ($cache && isset(self::$instances[$name])) {
			return self::$instances[$name];
		}

		$file = self::path($name) . '/' . $name . '.php';

		if (!is_file($file)) {
			return null;
		}

		require_once dirname(__DIR__) . '/core/ModuleBase.php';
		require_once $file;

		$class = self::classNameFromName($name);

		if (!class_exists($class)) {
			return null;
		}

		$instance = new $class();

		if (!$instance instanceof ModuleBase) {
			return null;
		}

		if ($cache) {
			self::$instances[$name] = $instance;
		}

		return $instance;
	}

	private static function classNameFromName(string $name): string
	{
		$parts = explode('-', $name);
		$parts = array_map(static fn($p) => ucfirst($p), $parts);

		return implode('', $parts) . 'Module';
	}

	/** @return string[] */
	public static function getEnabledNames(): array
	{
		$rows = DB::execute('SELECT name FROM modules WHERE installed = 1 AND active = 1 ORDER BY name ASC') ?: [];

		return array_column($rows, 'name');
	}

	/** @return ModuleBase[] */
	private static function getEnabledInstances(): array
	{
		$modules = [];

		foreach (self::getEnabledNames() as $name) {
			$instance = self::loadInstance($name);

			if ($instance) {
				$modules[] = $instance;
			}
		}

		return $modules;
	}

	private static function getDbRow(string $name): ?array
	{
		$row = DB::getRowSafe('modules', 'name = ?', [$name]);

		return $row ?: null;
	}

	private static function assignDefaultDisplayHooks(ModuleBase $module): void
	{
		$defaults = $module->getDefaultDisplayHookNames();

		if ($defaults === []) {
			return;
		}

		self::setDisplayHooks($module->name, $defaults);
	}

	private static function isValidModuleName(string $name): bool
	{
		return $name !== '' && (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $name);
	}

	/** @return array{name: string, prefix: string}|null */
	private static function detectZipModuleRoot(ZipArchive $zip): ?array
	{
		$matches = [];

		for ($i = 0; $i < $zip->numFiles; $i++) {
			$entry = str_replace('\\', '/', (string) $zip->getNameIndex($i));

			if ($entry === '' || strpos($entry, '__MACOSX/') === 0) {
				continue;
			}

			if (preg_match('#^([^/]+)/\1\.php$#', $entry, $m)) {
				$matches[$m[1]] = $m[1] . '/';
			}
		}

		if (count($matches) !== 1) {
			return null;
		}

		$name = (string) array_key_first($matches);

		if (!self::isValidModuleName($name)) {
			return null;
		}

		$prefix = $matches[$name];

		for ($i = 0; $i < $zip->numFiles; $i++) {
			$entry = str_replace('\\', '/', (string) $zip->getNameIndex($i));

			if ($entry === '' || strpos($entry, '__MACOSX/') === 0) {
				continue;
			}

			if (strpos($entry, $prefix) !== 0) {
				return null;
			}
		}

		return ['name' => $name, 'prefix' => $prefix];
	}

	private static function isSafeZipEntry(string $entry, string $rootPrefix): bool
	{
		$entry = str_replace('\\', '/', $entry);

		if ($entry === '' || strpos($entry, "\0") !== false) {
			return false;
		}

		if ($entry[0] === '/' || strpos($entry, '../') !== false || substr($entry, -3) === '/..') {
			return false;
		}

		if ($rootPrefix !== '' && strpos($entry, $rootPrefix) !== 0) {
			return false;
		}

		if (strpos($entry, '__MACOSX/') === 0) {
			return false;
		}

		return true;
	}

	private static function copyDirectory(string $source, string $target): bool
	{
		if (!is_dir($source)) {
			return false;
		}

		if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
			return false;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $item) {
			/** @var SplFileInfo $item */
			$subPath = substr($item->getPathname(), strlen($source) + 1);
			$dest = $target . DIRECTORY_SEPARATOR . $subPath;

			if ($item->isDir()) {
				if (!is_dir($dest) && !mkdir($dest, 0755, true) && !is_dir($dest)) {
					return false;
				}
			} elseif (!copy($item->getPathname(), $dest)) {
				return false;
			}
		}

		return true;
	}

	private static function removeDirectory(string $dir): void
	{
		if (!is_dir($dir)) {
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($iterator as $item) {
			/** @var SplFileInfo $item */
			if ($item->isDir()) {
				@rmdir($item->getPathname());
			} else {
				@unlink($item->getPathname());
			}
		}

		@rmdir($dir);
	}

	private static function ok(string $message): array
	{
		return ['success' => true, 'message' => $message];
	}

	private static function fail(string $message): array
	{
		return ['success' => false, 'message' => $message];
	}

	private static function apiResponse(array $data, int $code = 200): void
	{
		http_response_code($code);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data);
		exit;
	}
}
