<?php

require_once __DIR__ . '/marketplace/bootstrap.php';

class Marketplace
{
	public const PLATFORMS = [
		'trendyol' => [
			'label' => 'Trendyol',
			'active' => true,
		],
		'hepsiburada' => [
			'label' => 'Hepsiburada',
			'active' => true,
		],
		'n11' => [
			'label' => 'N11',
			'active' => true,
		],
	];

	/** send=0 ile kapatılır: pazaryerine stok/fiyat push yok, yalnızca FShop stok düşer */
	private static bool $allowMarketplaceStockPush = true;

	public static function setAllowMarketplaceStockPush(bool $allow): void
	{
		self::$allowMarketplaceStockPush = $allow;
	}

	public static function allowMarketplaceStockPush(): bool
	{
		return self::$allowMarketplaceStockPush;
	}

	public static function ensureSchema(): void
	{
		MarketplaceTables::ensureSchema();
		Trendyol\ProductSyncService::ensureSchema();
		Hepsiburada\ProductSyncService::ensureSchema();
		N11\ProductSyncService::ensureSchema();
	}

	public static function isEnabled(): bool
	{
		return true;
	}

	public static function platforms(): array
	{
		return self::PLATFORMS;
	}

	public static function platformList(): array
	{
		$list = [];

		foreach (self::PLATFORMS as $key => $platform) {
			$list[] = [
				'key' => $key,
				'label' => $platform['label'],
				'active' => !empty($platform['active']),
				'configured' => self::isPlatformConfigured($key),
				'settings_url' => self::settingsUrl($key),
			];
		}

		return $list;
	}

	public static function isPlatformActive(string $key): bool
	{
		return !empty(self::PLATFORMS[$key]['active']);
	}

	public static function isPlatformConfigured(string $key): bool
	{
		if ($key === 'trendyol') {
			return Trendyol\ProductSyncService::isConfigured();
		}

		if ($key === 'hepsiburada') {
			return Hepsiburada\ProductSyncService::isConfigured();
		}

		if ($key === 'n11') {
			return N11\ProductSyncService::isConfigured();
		}

		return false;
	}

	public static function handleAdminPosts(string $adminToken): string
	{
		if (\Tools::isSubmit('syncMarketplaceOrders')) {
			$postToken = (string) \Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				return 'Geçersiz istek';
			}

			$platform = trim((string) \Tools::getValue('marketplace_platform', 'trendyol'));
			$start = trim((string) \Tools::getValue('start_date'));
			$end = trim((string) \Tools::getValue('end_date'));

			$messages = [];

			$syncStart = $start !== '' ? $start : null;
			$syncEnd = $end !== '' ? $end : null;

			if ($platform === 'all') {
				$result = Trendyol\OrderService::syncOrders($syncStart, $syncEnd);
				$messages[] = (string) ($result['message'] ?? 'Trendyol siparişleri senkronize edildi');

				$result = Hepsiburada\OrderService::syncOrders($syncStart, $syncEnd);
				$messages[] = (string) ($result['message'] ?? 'Hepsiburada siparişleri senkronize edildi');

				$result = N11\OrderService::syncOrders();
				$messages[] = (string) ($result['message'] ?? 'N11 siparişleri senkronize edildi');

				return implode(' · ', $messages);
			}

			if ($platform === 'hepsiburada') {
				$result = Hepsiburada\OrderService::syncOrders($syncStart, $syncEnd);
				return (string) ($result['message'] ?? 'Hepsiburada siparişleri senkronize edildi');
			}

			if ($platform === 'n11') {
				$result = N11\OrderService::syncOrders();
				return (string) ($result['message'] ?? 'N11 siparişleri senkronize edildi');
			}

			$result = Trendyol\OrderService::syncOrders($syncStart, $syncEnd);
			return (string) ($result['message'] ?? 'Trendyol siparişleri senkronize edildi');
		}

		$flash = Trendyol\TrendyolAdminPages::handlePosts($adminToken);

		if ($flash === '') {
			$flash = Hepsiburada\HepsiburadaAdminPages::handlePosts($adminToken);
		}

		if ($flash === '') {
			$flash = N11\N11AdminPages::handlePosts($adminToken);
		}

		return $flash;
	}

	public static function isTrendyolConfigured(): bool
	{
		return Trendyol\ProductSyncService::isConfigured();
	}

	public static function urls(): array
	{
		$urls = Trendyol\TrendyolAdminPages::commonUrls();
		$domain = rtrim((string) Settings::get('DOMAIN'), '/') . '/';
		$api = rtrim($domain, '/') . '/api/marketplace.php';
		$token = urlencode((string) Settings::get('SHOP_TOKEN'));

		$urls['cronOrdersUrlHb'] = $api . '?action=cron&type=orders&platform=hepsiburada&token=' . $token;
		$urls['cronQuestionsUrlHb'] = $api . '?action=cron&type=questions&platform=hepsiburada&token=' . $token;
		$urls['cronOrdersUrlN11'] = $api . '?action=cron&type=orders&platform=n11&token=' . $token;
		$urls['cronQuestionsUrlN11'] = $api . '?action=cron&type=questions&platform=n11&token=' . $token;

		$urls['exportOrdersUrl'] = $api . '?action=export-orders';
		$urls['orderActionUrl'] = $api . '?action=order-action';

		return $urls;
	}

	public static function settingsUrl(string $platform = 'trendyol'): string
	{
		$path = 'marketplace-settings';

		if ($platform !== '') {
			$path .= '?platform=' . rawurlencode($platform);
		}

		return Admin::url($path);
	}

	public static function adminAssets(): array
	{
		global $adminCssDir, $domain;

		$jsBase = rtrim((string) ($adminCssDir ?? $domain . 'templates/admin/css/'), '/');
		$jsBase = preg_replace('#/css/?$#', '/js', $jsBase) ?: $domain . 'templates/admin/js/';

		return [
			'css' => [],
			'js' => [
				$domain . 'templates/admin/js/marketplace-admin.js',
			],
		];
	}

	public static function renderProductPanelHtml(int $idProduct, string $platform = 'trendyol'): string
	{
		global $smarty;

		if ($idProduct <= 0) {
			return '';
		}

		if ($platform === 'hepsiburada') {
			$vars = Hepsiburada\HepsiburadaAdminPages::productPanelVars($idProduct);
			$tpl = 'admin/marketplace/product_panel_hepsiburada.tpl';
		} elseif ($platform === 'n11') {
			$vars = N11\N11AdminPages::productPanelVars($idProduct);
			$tpl = 'admin/marketplace/product_panel_n11.tpl';
		} else {
			$vars = Trendyol\TrendyolAdminPages::productPanelVars($idProduct);
			$tpl = 'admin/marketplace/product_panel.tpl';
		}

		$previous = [];

		foreach ($vars as $key => $value) {
			$previous[$key] = $smarty->getTemplateVars($key);
			$smarty->assign($key, $value);
		}

		$html = $smarty->fetch($tpl) ?: '';

		foreach ($previous as $key => $value) {
			if ($value === null) {
				$smarty->clearAssign($key);
			} else {
				$smarty->assign($key, $value);
			}
		}

		return $html;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	public static function enrichCatalogRow(array $row): array
	{
		$idProduct = (int) ($row['id_product'] ?? 0);
		$row['ty_linked'] = !empty($row['ty_linked']) || trim((string) ($row['ty_barcode'] ?? '')) !== '';

		$hb = $idProduct > 0 ? Hepsiburada\ProductSyncService::findMapping($idProduct) : null;
		$row['hb_linked'] = Hepsiburada\ProductSyncService::isLinked($hb);
		$row['hb_sale_price'] = (float) ($hb['sale_price'] ?? 0);
		$row['hb_merchant_sku'] = (string) ($hb['merchant_sku'] ?? '');

		$n11 = $idProduct > 0 ? N11\ProductSyncService::findMapping($idProduct) : null;
		$row['n11_linked'] = N11\ProductSyncService::isLinked($n11);
		$row['n11_sale_price'] = (float) ($n11['sale_price'] ?? 0);
		$row['n11_stock_code'] = (string) ($n11['stock_code'] ?? '');

		return $row;
	}

	/**
	 * @param int $idProduct FShop Product ID
	 * @param string|null $excludePlatform Platform key to skip
	 * @return array<string, array{ok: bool, message: string}>
	 */
	public static function syncProductStockAcrossPlatforms(int $idProduct, ?string $excludePlatform = null): array
	{
		$results = [];

		if ($idProduct <= 0 || !self::$allowMarketplaceStockPush) {
			return $results;
		}

		if ($excludePlatform !== 'trendyol' && self::isPlatformActive('trendyol') && self::isTrendyolConfigured()) {
			$mapping = Trendyol\ProductSyncService::findMapping($idProduct);
			if ($mapping && Trendyol\ProductSyncService::isLinked($mapping)) {
				$results['trendyol'] = Trendyol\ProductSyncService::updatePriceStock($idProduct);
			}
		}

		if ($excludePlatform !== 'hepsiburada' && self::isPlatformActive('hepsiburada') && self::isPlatformConfigured('hepsiburada')) {
			$mapping = Hepsiburada\ProductSyncService::findMapping($idProduct);
			if ($mapping && Hepsiburada\ProductSyncService::isLinked($mapping)) {
				$results['hepsiburada'] = Hepsiburada\ProductSyncService::updatePriceStock($idProduct);
			}
		}

		if ($excludePlatform !== 'n11' && self::isPlatformActive('n11') && self::isPlatformConfigured('n11')) {
			$mapping = N11\ProductSyncService::findMapping($idProduct);
			if ($mapping && N11\ProductSyncService::isLinked($mapping)) {
				$results['n11'] = N11\ProductSyncService::updatePriceStock($idProduct);
			}
		}

		return $results;
	}
}
