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
			'active' => false,
		],
		'n11' => [
			'label' => 'N11',
			'active' => false,
		],
	];

	public static function ensureSchema(): void
	{
		Trendyol\ProductSyncService::ensureSchema();
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
				'settings_url' => self::settingsUrl($key),
			];
		}

		return $list;
	}

	public static function isPlatformActive(string $key): bool
	{
		return !empty(self::PLATFORMS[$key]['active']);
	}

	public static function handleAdminPosts(string $adminToken): string
	{
		return Trendyol\TrendyolAdminPages::handlePosts($adminToken);
	}

	public static function isTrendyolConfigured(): bool
	{
		return Trendyol\ProductSyncService::isConfigured();
	}

	public static function urls(): array
	{
		return Trendyol\TrendyolAdminPages::commonUrls();
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

	public static function renderProductPanelHtml(int $idProduct): string
	{
		global $smarty;

		if ($idProduct <= 0) {
			return '';
		}

		$vars = Trendyol\TrendyolAdminPages::productPanelVars($idProduct);
		$previous = [];

		foreach ($vars as $key => $value) {
			$previous[$key] = $smarty->getTemplateVars($key);
			$smarty->assign($key, $value);
		}

		$html = $smarty->fetch('admin/marketplace/product_panel.tpl') ?: '';

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
	 * Sensibly syncs a product's updated local stock to connected active marketplaces.
	 *
	 * @param int $idProduct FShop Product ID
	 * @param string|null $excludePlatform Platform key to skip (e.g., 'trendyol' when order comes from Trendyol)
	 * @return array<string, array{ok: bool, message: string}>
	 */
	public static function syncProductStockAcrossPlatforms(int $idProduct, ?string $excludePlatform = null): array
	{
		$results = [];

		if ($idProduct <= 0) {
			return $results;
		}

		// 1. Trendyol
		if ($excludePlatform !== 'trendyol' && self::isPlatformActive('trendyol') && self::isTrendyolConfigured()) {
			$mapping = Trendyol\ProductSyncService::findMapping($idProduct);
			if ($mapping && Trendyol\ProductSyncService::isLinked($mapping)) {
				$results['trendyol'] = Trendyol\ProductSyncService::updatePriceStock($idProduct);
			}
		}

		// 2. Hepsiburada (future platform)
		if ($excludePlatform !== 'hepsiburada' && self::isPlatformActive('hepsiburada')) {
			// Hepsiburada stock update logic goes here when active
		}

		// 3. N11 (future platform)
		if ($excludePlatform !== 'n11' && self::isPlatformActive('n11')) {
			// N11 stock update logic goes here when active
		}

		return $results;
	}
}
