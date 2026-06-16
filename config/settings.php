<?php	
	ob_start();
	require_once(dirname(__FILE__).'/function.php');
	require_once(dirname(__FILE__).'/connection.php');
	require_once(dirname(__FILE__).'/database.php');
	require_once(dirname(__FILE__).'/config.php');

	App::configureSession();

	require_once(dirname(__FILE__).'/../core/Product.php');
	require_once(dirname(__FILE__).'/../core/Cart.php');
	require_once(dirname(__FILE__).'/../core/Customer.php');
	require_once(dirname(__FILE__).'/../core/Order.php');
	require_once(dirname(__FILE__).'/../core/Category.php');
	require_once(dirname(__FILE__).'/../core/Favorite.php');
	require_once(dirname(__FILE__).'/../core/Contact.php');
	require_once(dirname(__FILE__).'/../core/Address.php');
	require_once(dirname(__FILE__).'/../core/Pagination.php');
	require_once(dirname(__FILE__).'/../core/Brand.php');
	require_once(dirname(__FILE__).'/../core/Cms.php');
	require_once(dirname(__FILE__).'/../core/ModuleBase.php');
	require_once(dirname(__FILE__).'/../core/Module.php');
	require_once(dirname(__FILE__).'/../core/Schema.php');
	require_once(dirname(__FILE__).'/../core/Mail.php');
	require_once(dirname(__FILE__).'/../core/SmtpMailer.php');
	require_once(dirname(__FILE__).'/../core/Notification.php');
	require_once(dirname(__FILE__).'/../core/Coupon.php');
	require_once(dirname(__FILE__).'/../core/Theme.php');
	require_once(dirname(__FILE__).'/../core/SiteAssets.php');
	require_once(dirname(__FILE__).'/../core/Seo.php');
	require_once(dirname(__FILE__).'/../core/SchemaOrg.php');

	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}

	App::sendSecurityHeaders();
	Cookie::autoLoginFromRememberCookie();
	$rootDir 	= Settings::get('FOLDER');
	$domain		= Settings::get('DOMAIN');
	$theme		= Settings::get('THEME') ?: 'default';
	Theme::ensureColorsFile($theme);


	$DOCUMENT_ROOT = '';
	$PHP_SELF = '';

	define('_THEME_REEL_DIR_', $rootDir.'templates/'.$theme.'/');
	define('_BASE_DIR_', str_replace($DOCUMENT_ROOT, "", dirname($PHP_SELF)));
	define('_BASE_IMG_DIR_', _BASE_DIR_.'img/');
	define('_BASE_JS_DIR_', _BASE_DIR_.'js/');
	define('_THEME_DIR_', _BASE_DIR_.'templates/');
    define('_THEME_BASE_DIR_', _THEME_DIR_.''.$theme.'/');
	define('_THEME_CSS_DIR_', _THEME_REEL_DIR_.'css/');
	define('_THEME_JS_DIR_', _THEME_REEL_DIR_.'js/');
	define('_THEME_IMG_DIR_', _THEME_REEL_DIR_.'img/');
	define('_MODULE_DIR_', _BASE_DIR_.'modules/');

	date_default_timezone_set('Europe/Istanbul');
	$betik_zd = date_default_timezone_get();
	
	function clearSQL($data)	
	{		
		$data = trim($data);		
		$data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');		
		return $data;	
	}	
	require_once(dirname(__FILE__).'/../libs/Smarty.class.php');
	$smarty = new Smarty\Smarty; 
	$smarty->setTemplateDir(dirname(__FILE__) . '/../templates/');
	$smarty->setCompileDir(dirname(__FILE__) . '/../cache/force/');
	$smarty->setCacheDir(dirname(__FILE__) . '/../cache/cache/');
	$smarty->setConfigDir(dirname(__FILE__) . '/../configs/');
	//$smarty->caching = Smarty::CACHING_LIFETIME_CURRENT;
	$smarty->cache_lifetime = 30;
	$smarty->compile_check = App::isDebug();
	$smarty->force_compile = App::isDebug();
	$smarty->caching = false;

	require_once(dirname(__FILE__).'/page.php');
	$page 		= new Page();
	$saveToken	= md5(date('Y-m-d H:0:0').'RB');

	$sonuc 	= '';	
	if (empty($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}

	$token = $_SESSION['csrf_token'];
	Schema::ensure();
	$cart = Cart::getSummary();
	$customer = Customer::getCurrent();
	$isLoggedIn = Customer::isLoggedIn();
	$notificationCount = $isLoggedIn ? Notification::getUnreadCount(Customer::getId()) : 0;
	$menuCategories = Category::getMenuList();
	$favoriteCount = Favorite::getCount();

	Module::bootstrap('front');
	$moduleAssets = Module::getHeadAssets();

	$smarty->assign(array(
		'base_dir' 			=> _BASE_DIR_,
		'rootDir' 			=> $rootDir,
		'tRealDir' 			=> _THEME_REEL_DIR_,
		'base_img' 			=> _BASE_IMG_DIR_,
		'base_js' 			=> _BASE_JS_DIR_,
		'tpl_dir' 			=> _THEME_BASE_DIR_,
		'css_dir' 			=> _THEME_CSS_DIR_,
		'js_dir' 			=> _THEME_JS_DIR_,
		'img_dir' 			=> _THEME_IMG_DIR_,
		'saveToken'			=> $saveToken,
		'time' 				=> date('Ymdh00'),
		'minute'			=> date('Ymd'),
		'year' 				=> date('Y'),
		'siteName' 			=> Settings::get('SITE_NAME'),
		'contactEmail' 		=> Settings::get('CONTACT_EMAIL') ?: '',
		'contactPhone' 		=> Settings::get('CONTACT_PHONE') ?: '',
		'contactPhoneTel' 	=> Settings::get('CONTACT_PHONE_TEL') ?: '',
		'freeShippingMin' 	=> Settings::get('FREE_SHIPPING_MIN') ?: '0',
		'shippingFee' 		=> Settings::get('SHIPPING_FEE') ?: '0',
		'token' 			=> $token,
		'sonuc' 			=> $sonuc,
		'domain' 			=> $domain,
		'cart' 				=> $cart,
		'customer' 			=> $customer,
		'isLoggedIn'		=> $isLoggedIn,
		'menuCategories' 	=> $menuCategories,
		'favoriteCount' 	=> $favoriteCount,
		'notificationCount' => $notificationCount,
		'cmsFooterLinks' 	=> Cms::getFooterLinks(),
		'siteLogos' => [
			'header' => SiteAssets::resolveLogoUrl('header'),
			'bar' => SiteAssets::resolveLogoUrl('bar'),
			'footer' => SiteAssets::resolveLogoUrl('footer'),
		],
		'moduleAssets' 		=> $moduleAssets,
		'hooks' 			=> Module::getRenderedDisplayHooks(),
	));
