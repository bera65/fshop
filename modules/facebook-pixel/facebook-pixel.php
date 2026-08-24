<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/FacebookPixelService.php';

class FacebookPixelModule extends ModuleBase
{
	public string $name = 'facebook-pixel';
	public string $title = 'Facebook Pixel';
	public string $version = '1.0.0';
	public string $description = 'Meta (Facebook) Pixel ile ziyaret, ürün, sepet ve satın alma olaylarını izler';
	public string $author = 'FShop';

	public array $displayHooks = [
		'head.top' => 'Pixel temel kodu (head)',
		'footer' => 'Satın alma olayı ve sepet/checkout script ayarları',
		'product_detail' => 'Ürün görüntüleme (ViewContent)',
	];

	public array $defaultDisplayHooks = ['head.top', 'footer', 'product_detail'];

	public array $frontScripts = ['front.js'];

	public function getFrontScripts(): array
	{
		return FacebookPixelService::isEnabled() ? parent::getFrontScripts() : [];
	}

	public function install(): bool
	{
		if (Settings::get('FB_PIXEL_ENABLED') === '') {
			Settings::set('FB_PIXEL_ENABLED', '0');
		}

		foreach (['VIEW', 'CART', 'CHECKOUT', 'PURCHASE'] as $event) {
			$key = 'FB_PIXEL_TRACK_' . $event;

			if (Settings::get($key) === '') {
				Settings::set($key, '1');
			}
		}

		return true;
	}

	public function uninstall(): bool
	{
		foreach ([
			'FB_PIXEL_ID',
			'FB_PIXEL_ENABLED',
			'FB_PIXEL_TRACK_VIEW',
			'FB_PIXEL_TRACK_CART',
			'FB_PIXEL_TRACK_CHECKOUT',
			'FB_PIXEL_TRACK_PURCHASE',
		] as $key) {
			Settings::delete($key);
		}

		return true;
	}

	public function boot(): void
	{
		Module::registerHook('order.placed', static function (array $order): void {
			FacebookPixelService::rememberPurchase($order);
		});
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if (!FacebookPixelService::isEnabled()) {
			return null;
		}

		if ($hook === 'head.top') {
			$html = $this->renderFrontTemplate('head-top', [
				'pixelId' => FacebookPixelService::getPixelId(),
			]);

			return $html !== '' ? $html : null;
		}

		if ($hook === 'product_detail') {
			$event = FacebookPixelService::buildViewContentEvent((int) ($context['id_product'] ?? 0));

			if (!$event) {
				return null;
			}

			$html = $this->renderFrontTemplate('view-content', [
				'eventJson' => Security::jsonForHtmlScript($event),
			]);

			return $html !== '' ? $html : null;
		}

		if ($hook === 'footer') {
			$config = FacebookPixelService::getFooterClientConfig();
			$json = Security::jsonForHtmlScript($config);

			if ($json === 'null') {
				return null;
			}

			$html = $this->renderFrontTemplate('footer', [
				'fbPixelConfigJson' => $json,
			]);

			return $html !== '' ? $html : null;
		}

		return null;
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';
		$flashType = 'success';

		if (Tools::isSubmit('saveFacebookPixel')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} else {
				$pixelId = trim((string) Tools::getValue('pixel_id'));

				if ($pixelId !== '' && !FacebookPixelService::isValidPixelId($pixelId)) {
					$flash = 'Geçersiz Pixel ID. Yalnızca rakamlardan oluşmalıdır (ör. 123456789012345).';
					$flashType = 'danger';
				} else {
					FacebookPixelService::saveSettings([
						'enabled' => Tools::getValue('enabled'),
						'pixel_id' => $pixelId,
						'track_view' => Tools::getValue('track_view'),
						'track_cart' => Tools::getValue('track_cart'),
						'track_checkout' => Tools::getValue('track_checkout'),
						'track_purchase' => Tools::getValue('track_purchase'),
					]);
					$flash = 'Facebook Pixel ayarları kaydedildi';
				}
			}
		}

		$smarty->assign([
			'flash' => $flash,
			'flashType' => $flashType,
			'settings' => FacebookPixelService::getSettings(),
		]);
	}
}
