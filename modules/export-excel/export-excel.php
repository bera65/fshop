<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/ExportExcelService.php';

class ExportExcelModule extends ModuleBase
{
	public string $name = 'export-excel';
	public string $title = 'Excel Aktarım';
	public string $version = '1.0.0';
	public string $description = 'Ürün içe/dışa aktarma ve sipariş dışa aktarma (admin header)';
	public string $author = 'FShop';

	public array $displayHooks = [
		'admin_header' => 'Admin orta alan üstü — Excel aktarım',
	];

	public array $defaultDisplayHooks = [
		'admin_header',
	];

	public array $apiActions = [
		'export-products' => 'api/export-products.php',
		'export-orders' => 'api/export-orders.php',
	];

	private const ALLOWED_PAGES = [
		'products',
		'orders',
	];

	public function install(): bool
	{
		return true;
	}

	public function uninstall(): bool
	{
		return true;
	}

	public function boot(): void
	{
		$this->ensureDisplayHooks();
		$this->handleImportPost();
	}

	private function ensureDisplayHooks(): void
	{
		if (!Module::isEnabled($this->name)) {
			return;
		}

		$assigned = Module::getAssignedDisplayHooks($this->name);

		if (!in_array('admin_header', $assigned, true)) {
			Module::setDisplayHooks($this->name, ['admin_header']);
		}
	}

	/** Ürün içe aktarma: form admin sayfasına POST eder, sonra products’a döner. */
	private function handleImportPost(): void
	{
		if (!defined('IN_ADMIN') || !Admin::isLoggedIn()) {
			return;
		}

		if (!Tools::isSubmit('exportExcelImportProducts')) {
			return;
		}

		$token = (string) Tools::getValue('token');
		$sessionToken = (string) ($_SESSION['admin_csrf_token'] ?? '');

		if ($sessionToken === '' || !hash_equals($sessionToken, $token)) {
			ExportExcelService::setFlash(adminT('Invalid request'), 'danger');
			$this->redirectBack();
			return;
		}

		$result = ExportExcelService::importProductsFromUpload($_FILES['excelFile'] ?? []);
		ExportExcelService::setFlash(
			(string) ($result['message'] ?? ''),
			!empty($result['success']) ? 'success' : 'danger'
		);
		$this->redirectBack();
	}

	private function redirectBack(): void
	{
		$return = trim((string) Tools::getValue('return_url', ''));
		$fallback = Admin::url('products');
		$adminBase = rtrim(Admin::url(''), '/');

		if ($return === '') {
			header('Location: ' . $fallback);
			exit;
		}

		$return = str_replace(["\r", "\n", "\0", '\\'], '', $return);

		if (strpos($return, 'http://') === 0 || strpos($return, 'https://') === 0) {
			if (strpos($return, $adminBase) === 0) {
				header('Location: ' . $return);
				exit;
			}

			header('Location: ' . $fallback);
			exit;
		}

		if (strpos($return, '//') === 0 || $return === '' || $return[0] !== '/') {
			header('Location: ' . $fallback);
			exit;
		}

		if (isset($return[1]) && $return[1] === '/') {
			header('Location: ' . $fallback);
			exit;
		}

		$path = (string) (parse_url($return, PHP_URL_PATH) ?: '');
		$query = parse_url($return, PHP_URL_QUERY);
		$adminUri = '/' . Admin::uri();

		if ($path === '' || strpos($path, $adminUri) === false) {
			header('Location: ' . $fallback);
			exit;
		}

		$safe = $path . (is_string($query) && $query !== '' ? '?' . $query : '');
		header('Location: ' . $safe);
		exit;
	}

	public function adminPage(): void
	{
		global $smarty;

		$smarty->assign([
			'moduleTitle' => $this->title,
		]);
	}

	public function renderAdminDisplayHook(string $hook, array $context = []): ?string
	{
		global $domain, $adminToken;

		if ($hook !== 'admin_header') {
			return null;
		}

		$pageName = (string) ($context['page_name'] ?? '');

		if (!in_array($pageName, self::ALLOWED_PAGES, true)) {
			return null;
		}

		$domainBase = rtrim((string) $domain, '/');

		if ($pageName === 'products') {
			$query = trim((string) Tools::getValue('q'));
			$idCategory = (int) Tools::getValue('category');
			$idBrand = (int) Tools::getValue('brand');
			$activeFilter = Tools::getIsset('active') ? (int) Tools::getValue('active') : -1;

			$returnUrl = Admin::url('products');
			$params = array_filter([
				'q' => $query !== '' ? $query : null,
				'category' => $idCategory > 0 ? $idCategory : null,
				'brand' => $idBrand > 0 ? $idBrand : null,
				'active' => $activeFilter >= 0 ? (string) $activeFilter : null,
				'page' => max(1, (int) Tools::getValue('page')) > 1 ? (int) Tools::getValue('page') : null,
			], static function ($v) {
				return $v !== null && $v !== '';
			});

			if ($params !== []) {
				$returnUrl .= '?' . http_build_query($params);
			}

			return $this->renderAdminTemplate('admin_header', [
				'mode' => 'products',
				'adminToken' => (string) $adminToken,
				'domain' => $domainBase . '/',
				'exportUrl' => $domainBase . '/api/module.php?m=export-excel&action=export-products',
				'importAction' => Admin::url('products'),
				'returnUrl' => $returnUrl,
				'filterQ' => $query,
				'filterCategory' => $idCategory,
				'filterBrand' => $idBrand,
				'filterActive' => $activeFilter,
			]) ?: null;
		}

		$status = (int) Tools::getValue('status');
		$filters = Order::normalizeAdminFilters([
			'reference' => Tools::getValue('reference'),
			'customer' => Tools::getValue('customer'),
			'date_from' => Tools::getValue('date_from'),
			'date_to' => Tools::getValue('date_to'),
		]);

		return $this->renderAdminTemplate('admin_header', [
			'mode' => 'orders',
			'adminToken' => (string) $adminToken,
			'domain' => $domainBase . '/',
			'exportUrl' => $domainBase . '/api/module.php?m=export-excel&action=export-orders',
			'filterStatus' => $status,
			'filterReference' => $filters['reference'],
			'filterCustomer' => $filters['customer'],
			'filterDateFrom' => $filters['date_from'],
			'filterDateTo' => $filters['date_to'],
		]) ?: null;
	}
}
