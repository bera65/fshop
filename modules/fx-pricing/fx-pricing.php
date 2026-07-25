<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/FxPricingService.php';

class FxPricingModule extends ModuleBase
{
	public string $name = 'fx-pricing';
	public string $title = 'Canlı Kur & Döviz Fiyat';
	public string $version = '1.0.0';
	public string $description = 'Canlı döviz kurları ile mağaza fiyat gösterimi ve admin ürün döviz fiyat girişi';
	public string $author = 'FShop';

	public array $displayHooks = [
		'footer' => 'Para birimi seçici yapılandırması',
		'admin_product_button' => 'Admin ürün formunda döviz fiyat alanları',
	];

	public array $defaultDisplayHooks = ['footer', 'admin_product_button'];

	public array $frontStylesheets = ['.no-global-assets'];
	public array $frontScripts = ['front.js'];
	public array $adminStylesheets = ['admin.css'];
	public array $adminScripts = ['admin.js'];

	public array $apiActions = [
		'rates' => 'api/rates.php',
		'refresh' => 'api/refresh.php',
	];

	public function install(): bool
	{
		Settings::set(FxPricingService::SET_ENABLED, '1');
		Settings::set(FxPricingService::SET_ADMIN_CURRENCIES, 'usd,eur');

		return true;
	}

	public function uninstall(): bool
	{
		Settings::delete(FxPricingService::SET_ENABLED);
		Settings::delete(FxPricingService::SET_ADMIN_CURRENCIES);

		return true;
	}

	public function boot(): void
	{
		if (!$this->isEnabled()) {
			return;
		}

		FxPricingService::ensureSchema();

		Module::registerHook('product.updated', static function (int $idProduct): void {
			if (!Tools::isSubmit('saveProduct')) {
				return;
			}

			FxPricingService::handleProductSave($idProduct);
		});

		Module::registerHook('smarty.assign', static function ($smarty): void {
			if (defined('IN_ADMIN')) {
				return;
			}

			$redirect = FxPricingService::currentRedirectPath();
			$options = FxPricingService::buildSwitcherOptions($redirect);

			$smarty->assign([
				'currencyOptions' => $options,
				'currencyOptionsJson' => json_encode($options, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
				'displayCurrency' => Currency::getDisplayCurrency(),
				'shopCurrencyCode' => Currency::getShopCurrency(),
				'siteBaseUrl' => Currency::getSiteBaseUrl(),
				'currencyRedirectPath' => $redirect,
			]);
		});
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'footer' || !$this->isEnabled() || defined('IN_ADMIN')) {
			return null;
		}

		global $domain;

		$redirect = FxPricingService::currentRedirectPath();
		$options = FxPricingService::buildSwitcherOptions($redirect);
		$json = json_encode($options, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

		return '<script>window.currencyOptions=' . $json . ';</script>';
	}

	public function isEnabled(): bool
	{
		return Settings::get(FxPricingService::SET_ENABLED, '1') === '1';
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken, $domain;

		$flash = '';
		$flashType = 'info';

		if (Tools::isSubmit('saveFxPricingSettings')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} else {
				Settings::set(FxPricingService::SET_ENABLED, Tools::getValue('enabled') ? '1' : '0');
				$codes = array_filter(array_map('trim', explode(',', (string) Tools::getValue('admin_currencies', 'usd,eur'))));
				$valid = [];

				foreach ($codes as $code) {
					$code = strtolower($code);

					if (in_array($code, Currency::getAvailable(), true) && $code !== Currency::getShopCurrency()) {
						$valid[] = $code;
					}
				}

				Settings::set(FxPricingService::SET_ADMIN_CURRENCIES, implode(',', $valid !== [] ? $valid : ['usd', 'eur']));
				$flash = 'Ayarlar kaydedildi';
				$flashType = 'success';
			}
		}

		if (Tools::isSubmit('refreshFxRates')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} else {
				$updated = FxPricingService::refreshAll(true);
				$flash = $updated['products'] . ' ürün fiyatı güncellendi. Kurlar yenilendi.';
				$flashType = 'success';
			}
		}

		$rates = FxPricingService::getPublicRates();
		$shopToken = (string) Settings::get('SHOP_TOKEN');

		$smarty->assign([
			'flash' => $flash,
			'flashType' => $flashType,
			'adminToken' => $adminToken,
			'fxSettings' => [
				'enabled' => $this->isEnabled(),
				'admin_currencies' => Settings::get(FxPricingService::SET_ADMIN_CURRENCIES, 'usd,eur'),
			],
			'fxRates' => $rates,
			'shopCurrency' => Currency::getShopCurrency(),
			'currencyList' => Currency::getAdminList(),
			'cronUrl' => rtrim((string) $domain, '/') . '/api/cron.php?action=currency&token=' . rawurlencode($shopToken),
			'fxRefreshCronUrl' => rtrim((string) $domain, '/') . '/api/module.php?m=fx-pricing&action=refresh&token=' . rawurlencode($shopToken),
			'fxProductCount' => FxPricingService::countFxProducts(),
		]);
	}

	public function renderAdminDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'admin_product_button' || !$this->isEnabled()) {
			return null;
		}

		$idProduct = (int) ($context['id_product'] ?? 0);
		$product = $idProduct > 0 ? (Product::getByIdAdmin($idProduct) ?: []) : [];
		$shopCurrency = Currency::getShopCurrency();
		$productCurrency = strtolower(trim((string) ($product['doviz'] ?? $shopCurrency)));
		$useFx = $productCurrency !== '' && $productCurrency !== $shopCurrency;

		$html = $this->renderAdminTemplate('admin_product_button', [
			'id_product' => $idProduct,
			'shop_currency' => $shopCurrency,
			'shop_currency_label' => Currency::label($shopCurrency),
			'fx_currencies' => FxPricingService::getAdminCurrencyOptions(),
			'product_currency' => $useFx ? $productCurrency : 'usd',
			'fx_cost' => FxPricingService::resolveFxCost($product, $productCurrency, $useFx),
			'fx_price' => $useFx ? (float) ($product['doviz_price'] ?? 0) : 0,
			'fx_old_price' => $useFx ? (float) ($product['doviz_old_price'] ?? 0) : 0,
			'use_fx' => $useFx ? '1' : '0',
			'rates_api_url' => $this->getAssetUrl('../api/rates.php'),
			'module_rates_url' => rtrim((string) ($GLOBALS['domain'] ?? ''), '/') . '/api/module.php?m=fx-pricing&action=rates',
			'admin_js_url' => $this->getAssetUrl('js/admin.js'),
		]);

		return $html !== '' ? $html : null;
	}
}
