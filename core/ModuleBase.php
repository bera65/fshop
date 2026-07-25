<?php

abstract class ModuleBase
{
	public string $name = '';
	public string $title = '';
	public string $version = '1.0.0';
	public string $description = '';
	public string $author = 'FShop';

	/** @var array<string, string> slug => relative path from module dir */
	public array $routes = [];

	/** @var array<int, array{slug: string, title: string, description?: string, file: string}> — geriye dönük */
	public array $adminPages = [];

	/** @var array<string, string> slug => relative path — geriye dönük uyumluluk */
	public array $adminRoutes = [];

	/** @var array<string, string> action => relative path */
	public array $apiActions = [];

	/**
	 * Modülün bağlanabileceği görünür hook'lar (şablonda {$hooks.footer} vb.).
	 * @var array<string, string> hook anahtarı => açıklama
	 */
	public array $displayHooks = [];

	/** @var string[] Kurulumda varsayılan atanacak hook'lar */
	public array $defaultDisplayHooks = [];

	/** @var array<string, string> — geriye dönük uyumluluk */
	public array $positions = [];

	/**
	 * Kullanılan hook'lar ve ne işe yaradıkları (dokümantasyon).
	 * @var array<string, string> hook adı => açıklama
	 */
	public array $hooksMeta = [];

	/** @var string[] Mağaza CSS — assets/css/ altındaki dosya adları (boşsa yüklenmez) */
	public array $frontStylesheets = [];

	/** @var string[] Mağaza JS — assets/js/ altındaki dosya adları (boşsa yüklenmez) */
	public array $frontScripts = [];

	/** @var string[] Admin yapılandırma CSS — assets/css/ altındaki dosya adları (boşsa yüklenmez) */
	public array $adminStylesheets = [];

	/** @var string[] Admin yapılandırma JS — assets/js/ altındaki dosya adları (boşsa yüklenmez) */
	public array $adminScripts = [];

	/** Ödeme modülü mü? true ise checkout'ta yöntem olarak kaydedilir */
	public bool $isPayment = false;

	/** Ödeme yöntemi kimliği — orders.payment_method değeri (ör. bank_transfer) */
	public string $paymentMethodId = '';

	/** Checkout ve sipariş detayında görünen etiket (boşsa $title) */
	public string $paymentMethodLabel = '';

	/**
	 * true ise sipariş OLUŞTURULMADAN önce ödeme alınır:
	 * müşteri getPaymentPageUrl() adresine yönlendirilir (kart formu),
	 * ödeme onaylanınca modül Order::placePending() çağırarak siparişi oluşturur.
	 */
	public bool $paysBeforeOrder = false;

	public function getPath(): string
	{
		return Module::path($this->name);
	}

	public function getUrl(string $path = ''): string
	{
		global $domain;

		return rtrim($domain, '/') . '/modules/' . $this->name . '/' . ltrim($path, '/');
	}

	public function getAssetUrl(string $file): string
	{
		$file = ltrim($file, '/');
		$folder = '';

		if (class_exists('Settings', false)) {
			$folder = trim((string) Settings::get('FOLDER'), '/');
		}

		$prefix = $folder !== '' ? '/' . $folder : '';
		$url = $prefix . '/modules/' . $this->name . '/assets/' . $file;
		$path = $this->getPath() . '/assets/' . $file;

		return class_exists('Performance', false)
			? Performance::versionedUrl($url, is_file($path) ? $path : null)
			: $url;
	}

	/** @return string[] */
	public function getFrontStylesheets(): array
	{
		return $this->resolveAssetUrls('css', $this->frontStylesheets);
	}

	/** @return string[] */
	public function getFrontScripts(): array
	{
		return $this->resolveAssetUrls('js', $this->frontScripts);
	}

	/** @return string[] */
	public function getAdminStylesheets(): array
	{
		return $this->resolveAssetUrls('css', $this->adminStylesheets);
	}

	/** @return string[] */
	public function getAdminScripts(): array
	{
		return $this->resolveAssetUrls('js', $this->adminScripts);
	}

	/** @return array{css: string[], js: string[]} */
	public function getAdminAssets(): array
	{
		return [
			'css' => $this->getAdminStylesheets(),
			'js' => $this->getAdminScripts(),
		];
	}

	/** @param string[] $files */
	private function resolveAssetUrls(string $type, array $files): array
	{
		if ($files === []) {
			return [];
		}

		$ext = $type === 'css' ? 'css' : 'js';
		$urls = [];

		foreach ($files as $file) {
			$file = ltrim((string) $file, '/');

			if ($file === '' || $file[0] === '.' || substr($file, -strlen('.' . $ext)) !== '.' . $ext) {
				continue;
			}

			if (is_file($this->getPath() . '/assets/' . $type . '/' . $file)) {
				$urls[] = $this->getAssetUrl($type . '/' . $file);
			}
		}

		return $urls;
	}

	public function getAdminSlug(): string
	{
		return 'module-' . $this->name;
	}

	/**
	 * Register a link in the admin sidebar (admin.menu hook).
	 *
	 * @param string $label English label key for adminT()
	 * @param string $group general|catalog|system|sales|marketplace
	 */
	protected function registerAdminMenuLink(
		string $label,
		string $group = 'system',
		int $position = 100,
		?string $slug = null,
		?string $url = null,
		int $badge = 0
	): void {
		$slug = $slug ?? $this->getAdminSlug();

		Module::registerHook('admin.menu', static function (array &$items) use (
			$label,
			$group,
			$position,
			$slug,
			$url,
			$badge
		): void {
			$itemUrl = $url ?? '';

			if ($itemUrl === '') {
				if (class_exists('Admin', false)) {
					$itemUrl = Admin::url($slug);
				} else {
					global $domain;
					$uri = 'admin';

					if (class_exists('App', false)) {
						$raw = trim((string) App::env('ADMIN_URI', 'admin'), "/ \t\n\r\0\x0B");
						$sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '', $raw);
						$uri = ($sanitized !== null && $sanitized !== '') ? $sanitized : 'admin';
					}

					$itemUrl = rtrim((string) ($domain ?? ''), '/') . '/' . $uri . '/' . ltrim($slug, '/');
				}
			}

			$items[] = [
				'label' => $label,
				'url' => $itemUrl,
				'slug' => $slug,
				'group' => $group,
				'position' => $position,
				'badge' => $badge,
			];
		});
	}

	public function getAdminPageTitle(): string
	{
		return $this->title . ' — Yapılandır';
	}

	public function getAdminTemplatePath(string $template = 'admin'): string
	{
		return $this->getPath() . '/assets/templates/admin/' . $template . '.tpl';
	}

	public function getFrontTemplatePath(string $template): string
	{
		return $this->getPath() . '/assets/templates/front/' . $template . '.tpl';
	}

	public function hasAdminTemplate(): bool
	{
		return is_file($this->getAdminTemplatePath('admin'));
	}

	public function hasFrontTemplate(string $template): bool
	{
		return is_file($this->getFrontTemplatePath($template));
	}

	/**
	 * Mağaza hook şablonunu render eder (assets/templates/front/{ad}.tpl).
	 * @param array<string, mixed> $vars
	 */
	public function renderFrontTemplate(string $template, array $vars = []): string
	{
		if (!$this->hasFrontTemplate($template)) {
			return '';
		}

		global $smarty;

		$tplFile = $this->getFrontTemplatePath($template);
		$compileId = 'module_front_' . $this->name;

		// Modül değişkenlerini ana şablondan ayır; clearAssign global isLoggedIn vb. siliyordu
		$tpl = $smarty->createTemplate('file:' . $tplFile, $vars, $compileId, $smarty);

		return $tpl->fetch();
	}

	public function getLogoPath(): string
	{
		return $this->getPath() . '/logo.png';
	}

	public function getLogoUrl(): string
	{
		if (!is_file($this->getLogoPath())) {
			return '';
		}

		return $this->getUrl('logo.png');
	}

	abstract public function install(): bool;

	abstract public function uninstall(): bool;

	public function boot(): void
	{
	}

	/** @return string[] */
	public function getSupportedDisplayHooks(): array
	{
		if ($this->displayHooks !== []) {
			return array_keys($this->displayHooks);
		}

		return array_keys($this->positions);
	}

	/** @return string[] */
	public function getDefaultDisplayHookNames(): array
	{
		if ($this->defaultDisplayHooks !== []) {
			return array_values(array_intersect(
				$this->defaultDisplayHooks,
				$this->getSupportedDisplayHooks()
			));
		}

		return $this->getSupportedDisplayHooks();
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		return null;
	}

	/**
	 * Admin panel display hook — assets/templates/admin/{hook}.tpl
	 * @param array<string, mixed> $context
	 */
	public function renderAdminDisplayHook(string $hook, array $context = []): ?string
	{
		if (!in_array($hook, $this->getSupportedDisplayHooks(), true)) {
			return null;
		}

		if ($this->hasAdminDisplayTemplate($hook)) {
			return $this->renderAdminTemplate($hook, $context);
		}

		return null;
	}

	public function hasAdminDisplayTemplate(string $template): bool
	{
		return is_file($this->getPath() . '/assets/templates/admin/' . $template . '.tpl');
	}

	/**
	 * @param array<string, mixed> $vars
	 */
	public function renderAdminTemplate(string $template, array $vars = []): string
	{
		if (!$this->hasAdminDisplayTemplate($template)) {
			return '';
		}

		global $smarty;

		$tplFile = $this->getPath() . '/assets/templates/admin/' . $template . '.tpl';
		$compileId = 'module_admin_' . $this->name;
		$tpl = $smarty->createTemplate('file:' . $tplFile, $vars, $compileId, $smarty);

		return $tpl->fetch();
	}

	public function getPaymentMethodLabel(): string
	{
		return $this->paymentMethodLabel !== '' ? $this->paymentMethodLabel : $this->title;
	}

	/**
	 * Ödeme yöntemine özel indirim (kupon/promosyon sonrası ürün tutarı üzerinden).
	 *
	 * @return array{amount:float,label:string,percent:float}
	 */
	public function getPaymentDiscount(float $amount): array
	{
		return [
			'amount' => 0.0,
			'label' => '',
			'percent' => 0.0,
		];
	}

	/** paysBeforeOrder = true olan modülün ödeme (kart) sayfası adresi */
	public function getPaymentPageUrl(): string
	{
		return '';
	}

	/**
	 * Sipariş kaydedildikten hemen sonra çağrılır (ödeme modülleri için).
	 * Dönüş: ['success' => bool, 'redirect' => string, 'message' => string]
	 * - redirect dolu ise müşteri o adrese yönlendirilir (ör. PayTR ödeme sayfası).
	 * - boş ise standart checkout-success sayfası gösterilir.
	 */
	public function processPayment(array $order): array
	{
		return [
			'success' => true,
			'redirect' => '',
			'message' => '',
		];
	}

	/** Modül yapılandırma ekranı — veri atama ve form işlemleri burada */
	public function adminPage(): void
	{
	}

	/** @return array<int, array{slug: string, title: string, description: string}> */
	public function getAdminPageDefinitions(): array
	{
		if ($this->adminPages !== []) {
			return array_map(static function (array $page) {
				return [
					'slug' => $page['slug'],
					'title' => $page['title'],
					'description' => $page['description'] ?? '',
				];
			}, $this->adminPages);
		}

		if ($this->adminRoutes !== []) {
			$pages = [];

			foreach ($this->adminRoutes as $slug => $file) {
				$pages[] = [
					'slug' => $slug,
					'title' => $this->title,
					'description' => '',
				];
			}

			return $pages;
		}

		return [[
			'slug' => $this->getAdminSlug(),
			'title' => $this->getAdminPageTitle(),
			'description' => $this->description,
		]];
	}

	/** @return array<string, mixed> */
	public function toArray(): array
	{
		return [
			'name' => $this->name,
			'title' => $this->title,
			'version' => $this->version,
			'description' => $this->description,
			'author' => $this->author,
		];
	}

	public function runSqlFile(string $relativePath): bool
	{
		$file = $this->getPath() . '/' . ltrim($relativePath, '/');

		if (!is_file($file)) {
			return true;
		}

		$sql = file_get_contents($file);
		$statements = array_filter(array_map('trim', preg_split('/;\s*[\r\n]+/', $sql)));

		foreach ($statements as $statement) {
			if ($statement === '' || strpos($statement, '--') === 0) {
				continue;
			}

			if (DB::execute($statement) === false) {
				return false;
			}
		}

		return true;
	}
}
