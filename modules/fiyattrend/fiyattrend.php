<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/FiyattrendFeedService.php';

class FiyattrendModule extends ModuleBase
{
	public string $name = 'fiyattrend';
	public string $title = 'FiyatTrend';
	public string $version = '1.0.0';
	public string $description = 'FiyatTrend.com ürün karşılaştırması için Google Merchant uyumlu XML feed';
	public string $author = 'FShop';

	public array $displayHooks = [];
	public array $defaultDisplayHooks = [];

	public array $adminStylesheets = ['admin.css'];

	public array $apiActions = [
		'feed' => 'api/feed.php',
		'preview' => 'api/preview.php',
		'regenerate' => 'api/regenerate.php',
	];

	public function install(): bool
	{
		Settings::set('FT_ENABLED', '1');
		Settings::set('FT_CACHE_TTL', '360');
		Settings::set('FT_CURRENCY', 'TRY');
		Settings::set('FT_CONDITION', 'new');
		Settings::set('FT_BRAND_FALLBACK', Settings::get('SITE_NAME') ?: '');
		Settings::set('FT_EXCLUDE_CATS', '');
		Settings::set('FT_INCLUDE_OUTSTOCK', '0');
		Settings::set('FT_FEED_TOKEN', bin2hex(random_bytes(16)));
		Settings::set('FT_LAST_REGEN', '');

		return true;
	}

	public function uninstall(): bool
	{
		foreach ([
			'FT_ENABLED', 'FT_CACHE_TTL', 'FT_CURRENCY', 'FT_CONDITION',
			'FT_BRAND_FALLBACK', 'FT_EXCLUDE_CATS', 'FT_INCLUDE_OUTSTOCK',
			'FT_FEED_TOKEN', 'FT_LAST_REGEN',
		] as $key) {
			Settings::set($key, '');
		}

		@unlink(FiyattrendFeedService::cachePath());

		return true;
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken, $domain;

		$flash = '';
		$flashType = 'success';

		if (Tools::isSubmit('saveFtSettings')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} else {
				Settings::set('FT_ENABLED', Tools::getValue('ft_enabled') ? '1' : '0');
				Settings::set('FT_CACHE_TTL', (string) max(10, (int) Tools::getValue('ft_cache_ttl')));
				Settings::set('FT_CURRENCY', strtoupper(trim((string) Tools::getValue('ft_currency'))));
				$condition = (string) Tools::getValue('ft_condition');
				Settings::set('FT_CONDITION', in_array($condition, ['new', 'used', 'refurbished'], true) ? $condition : 'new');
				Settings::set('FT_BRAND_FALLBACK', trim(strip_tags((string) Tools::getValue('ft_brand_fallback'))));
				Settings::set('FT_EXCLUDE_CATS', trim((string) Tools::getValue('ft_exclude_cats')));
				Settings::set('FT_INCLUDE_OUTSTOCK', Tools::getValue('ft_include_outstock') ? '1' : '0');
				@unlink(FiyattrendFeedService::cachePath());
				$flash = 'Ayarlar kaydedildi. Feed önbelleği temizlendi.';
			}
		}

		if (Tools::isSubmit('regenToken')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} else {
				Settings::set('FT_FEED_TOKEN', bin2hex(random_bytes(16)));
				@unlink(FiyattrendFeedService::cachePath());
				$flash = 'Feed token yenilendi. FiyatTrend panelindeki XML linkini güncellemeniz gerekir.';
			}
		}

		$feedUrl = FiyattrendFeedService::buildFeedUrl((string) $domain);

		$smarty->assign([
			'flash' => $flash,
			'flashType' => $flashType,
			'panelUrl' => FiyattrendFeedService::PANEL_URL,
			'ftEnabled' => Settings::get('FT_ENABLED'),
			'ftCacheTtl' => Settings::get('FT_CACHE_TTL') ?: '360',
			'ftCurrency' => Settings::get('FT_CURRENCY') ?: 'TRY',
			'ftCondition' => Settings::get('FT_CONDITION') ?: 'new',
			'ftBrandFallback' => Settings::get('FT_BRAND_FALLBACK'),
			'ftExcludeCats' => Settings::get('FT_EXCLUDE_CATS'),
			'ftIncludeOutstock' => Settings::get('FT_INCLUDE_OUTSTOCK'),
			'feedUrl' => $feedUrl,
			'lastRegen' => Settings::get('FT_LAST_REGEN') ?: '—',
			'cacheExists' => file_exists(FiyattrendFeedService::cachePath()),
		]);
	}
}
