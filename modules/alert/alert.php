<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/AlertService.php';

class AlertModule extends ModuleBase
{
	public string $name = 'alert';
	public string $title = 'Uyarılar';
	public string $version = '1.0.0';
	public string $description = 'Sipariş ve kritik stok e-posta uyarıları; stoğa girince haber ver';
	public string $author = 'FShop';

	public array $displayHooks = [
		'product_detail' => 'Stokta yokken “stoğa girince haber ver” formu',
	];

	public array $defaultDisplayHooks = ['product_detail'];

	public array $frontStylesheets = ['front.css'];
	public array $frontScripts = ['front.js'];
	public array $adminStylesheets = ['admin.css'];

	public array $apiActions = [
		'subscribe-stock' => 'api/subscribe-stock.php',
	];

	public function install(): bool
	{
		if (!$this->runSqlFile('install.sql')) {
			return false;
		}

		AlertService::ensureDefaultSettings();

		return true;
	}

	public function uninstall(): bool
	{
		Settings::set('ALERT_ORDER_EMAIL_ENABLED', '');
		Settings::set('ALERT_CRITICAL_STOCK_ENABLED', '');
		Settings::set('ALERT_CRITICAL_STOCK_THRESHOLD', '');
		Settings::set('ALERT_BACK_IN_STOCK_ENABLED', '');
		Settings::set('ALERT_ADMIN_EMAILS', '');

		return $this->runSqlFile('uninstall.sql');
	}

	public function boot(): void
	{
		AlertService::ensureSchema();
		AlertService::ensureDefaultSettings();

		Module::registerHook('order.placed', static function (array $order): void {
			AlertService::handleOrderPlaced($order);
		});

		Module::registerHook('product.updated', static function ($idProduct, $product): void {
			if (!is_array($product)) {
				return;
			}

			AlertService::handleProductUpdated((int) $idProduct, $product);
		});

		$this->registerAdminMenuLink('Alerts', 'system', 55);
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';
		$flashType = 'success';
		$tab = (string) Tools::getValue('tab', 'settings');

		if (!in_array($tab, ['settings', 'subscriptions'], true)) {
			$tab = 'settings';
		}

		if (Tools::isSubmit('saveAlertSettings') && hash_equals($adminToken, (string) Tools::getValue('token'))) {
			$result = AlertService::saveSettings([
				'order_email' => Tools::getValue('order_email'),
				'critical_stock' => Tools::getValue('critical_stock'),
				'critical_threshold' => Tools::getValue('critical_threshold'),
				'back_in_stock' => Tools::getValue('back_in_stock'),
				'admin_emails' => Tools::getValue('admin_emails'),
			]);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';
			$tab = 'settings';
		}

		$filter = (string) Tools::getValue('filter', 'pending');
		$currentPage = max(1, (int) Tools::getValue('page'));
		$perPage = 30;
		$total = AlertService::countSubscriptions($filter);
		$pagination = Pagination::build(
			$total,
			$currentPage,
			$perPage,
			Admin::url($this->getAdminSlug()),
			array_filter([
				'tab' => 'subscriptions',
				'filter' => $filter !== 'pending' ? $filter : null,
			])
		);

		$smarty->assign([
			'flash' => $flash,
			'flashType' => $flashType,
			'tab' => $tab,
			'settings' => AlertService::getSettingsForAdmin(),
			'subscriptions' => AlertService::getSubscriptionsForAdmin($perPage, $pagination['offset'], $filter),
			'pagination' => $pagination,
			'subscriptionFilter' => $filter,
		]);
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'product_detail') {
			return null;
		}

		if (!AlertService::isBackInStockEnabled()) {
			return null;
		}

		$idProduct = (int) ($context['id_product'] ?? 0);

		if ($idProduct <= 0) {
			return null;
		}

		$product = Product::getById($idProduct);

		if (!$product || Product::isInStock($product)) {
			return null;
		}

		global $domain, $token;

		$userEmail = '';

		if (Customer::isLoggedIn()) {
			$current = Customer::getCurrent();
			$userEmail = trim((string) ($current['email'] ?? ''));
		}

		$html = $this->renderFrontTemplate('product_detail', [
			'id_product' => $idProduct,
			'product_name' => (string) ($product['product_name'] ?? ''),
			'api_url' => rtrim((string) $domain, '/') . '/api/module.php?m=alert&action=subscribe-stock',
			'csrf_token' => (string) ($token ?? ''),
			'user_email' => $userEmail,
			'is_logged_in' => Customer::isLoggedIn(),
		]);

		return $html !== '' ? $html : null;
	}
}
