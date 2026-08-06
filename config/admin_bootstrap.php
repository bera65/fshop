<?php
	ob_start();

	define('IN_ADMIN', true);
	require_once dirname(__FILE__) . '/function.php';
	require_once dirname(__FILE__) . '/connection.php';
	require_once dirname(__FILE__) . '/database.php';
	require_once dirname(__FILE__) . '/config.php';
	require_once dirname(__FILE__) . '/../core/autoload.php';

	App::configureSession();

	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}

	AdminLang::handleSwitchRequest();
	$adminLang = AdminLang::current();

	if (!function_exists('adminT')) {
		function adminT($text)
		{
			return AdminLang::translate((string) $text);
		}
	}

	if (!function_exists('translate')) {
		function translate($text)
		{
			return AdminLang::translate((string) $text);
		}
	}

	Performance::ensureDefaults();
	App::configureErrors();

	App::sendSecurityHeaders();

	date_default_timezone_set('Europe/Istanbul');

	$domain = rtrim((string) Settings::get('DOMAIN'), '/') . '/';
	CanonicalHost::redirectIfNeeded();
	$adminUrl = rtrim($domain, '/') . '/' . Admin::uri() . '/';
	Admin::syncHtaccessRewrite();
	$siteName = Settings::get('SITE_NAME');

	define('_ADMIN_THEME_DIR_', dirname(__FILE__) . '/../templates/admin/');
	define('_ADMIN_CSS_DIR_', $domain . 'templates/admin/css/');
	define('_ADMIN_JS_DIR_', $domain . 'templates/admin/js/');

	require_once dirname(__FILE__) . '/../libs/Smarty.class.php';
	require_once dirname(__FILE__) . '/smarty_setup.php';
	$smarty = new Smarty\Smarty;
	$smarty->setTemplateDir(dirname(__FILE__) . '/../templates/');
	fshop_configure_smarty($smarty);

	require_once dirname(__FILE__) . '/admin_page.php';

	if (empty($_SESSION['admin_csrf_token'])) {
		$_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
	}

	$adminToken = $_SESSION['admin_csrf_token'];
	Security::enforcePostCsrf('admin');
	$adminUser = Admin::getCurrent();

	Module::bootstrap('admin');
	Schema::ensure();
	Marketplace::ensureSchema();

	$adminNavBadges = [
		'orders' => Order::countAdmin(Order::STATUS_PENDING) + Order::countAdmin(Order::STATUS_PROCESSING),
		'returns' => ReturnRequest::countPending(),
		'cancellations' => CancelRequest::countPending(),
		'notifications' => AdminNotification::countUnread(),
		'messages' => Contact::countUnread(),
	];

	$adminMenuItems = Module::getAdminMenuItems();
	$smarty->assign([
		'domain' => $domain,
		'adminUrl' => $adminUrl,
		'adminCssDir' => _ADMIN_CSS_DIR_,
		'adminJsDir' => _ADMIN_JS_DIR_,
		'siteName' => $siteName,
		'adminToken' => $adminToken,
		'adminUser' => $adminUser,
		'adminInitial' => $adminUser
			? mb_strtoupper(mb_substr($adminUser['full_name'], 0, 1, 'UTF-8'))
			: 'A',
		'adminNavBadges' => $adminNavBadges,
		'adminMenuItems' => $adminMenuItems,
		'marketplaceAdminAssets' => ['css' => [], 'js' => []],
		'year' => date('Y'),
		'moduleAdminAssets' => ['css' => [], 'js' => []],
		'adminUseCharts' => false,
		'adminUseOrderStatus' => false,
		'adminUseEditor' => false,
		'adminLogoUrl' => SiteAssets::resolveLogoUrl('admin'),
		'adminLang' => $adminLang,
		'adminLangSwitcher' => AdminLang::getSwitcherList(),
		'adminUriIsDefault' => Admin::uri() === 'admin',
		'fshopVersion' => FShop::version(),
		'fshopName' => FShop::NAME,
		'adminHooks' => [],
		'adminI18n' => [
			'confirmTitle' => adminT('Confirm action'),
			'confirmMessage' => adminT('Are you sure you want to perform this action?'),
		],
	]);
	$smarty->registerPlugin('modifier', 'adminT', 'adminT');
