<?php	
	ob_start();
	require_once(dirname(__FILE__).'/function.php');
	require_once(dirname(__FILE__).'/connection.php');
	require_once(dirname(__FILE__).'/database.php');
	require_once(dirname(__FILE__).'/config.php');

	App::configureSession();

	require_once(dirname(__FILE__).'/../core/Lang.php');

	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}

	Lang::handleSwitchRequest();

	if (empty($_SESSION['selectLang'])) {
		$_SESSION['selectLang'] = Lang::getDefault();
	}

	$selectLang = clearSQL((string) $_SESSION['selectLang']);

	require_once(dirname(__FILE__).'/../lang/lang.php');
	require_once(dirname(__FILE__).'/../core/Security.php');
	require_once(dirname(__FILE__).'/../core/RateLimit.php');
	require_once(dirname(__FILE__).'/../core/Product.php');
	require_once(dirname(__FILE__).'/../core/ProductLog.php');
	require_once(dirname(__FILE__).'/../core/SaleUnit.php');
	require_once(dirname(__FILE__).'/../core/StockAnalysis.php');
	require_once(dirname(__FILE__).'/../core/ProductVariation.php');
	require_once(dirname(__FILE__).'/../core/ProductOption.php');
	require_once(dirname(__FILE__).'/../core/VirtualProduct.php');
	require_once(dirname(__FILE__).'/../core/Cart.php');
	require_once(dirname(__FILE__).'/../core/Customer.php');
	require_once(dirname(__FILE__).'/../core/CustomerGroup.php');
	require_once(dirname(__FILE__).'/../core/GroupPricing.php');
	require_once(dirname(__FILE__).'/../core/Order.php');
	require_once(dirname(__FILE__).'/../core/Cargo.php');
	require_once(dirname(__FILE__).'/../core/ReturnRequest.php');
	require_once(dirname(__FILE__).'/../core/CancelRequest.php');
	require_once(dirname(__FILE__).'/../core/AdminNotification.php');
	require_once(dirname(__FILE__).'/../core/Category.php');
	require_once(dirname(__FILE__).'/../core/Favorite.php');
	require_once(dirname(__FILE__).'/../core/Contact.php');
	require_once(dirname(__FILE__).'/../core/Address.php');
	require_once(dirname(__FILE__).'/../core/Pagination.php');
	require_once(dirname(__FILE__).'/../core/CatalogFilter.php');
	require_once(dirname(__FILE__).'/../core/Coupon.php');
	require_once(dirname(__FILE__).'/../core/CartPromotion.php');
	require_once(dirname(__FILE__).'/../core/Brand.php');
	require_once(dirname(__FILE__).'/../core/Cms.php');
	require_once(dirname(__FILE__).'/../core/Currency.php');
	require_once(dirname(__FILE__).'/../core/Tax.php');
	Currency::handleSwitchRequest();
	require_once(dirname(__FILE__).'/../core/ModuleBase.php');
	require_once(dirname(__FILE__).'/../core/Module.php');
	require_once(dirname(__FILE__).'/../core/Captcha.php');
	require_once(dirname(__FILE__).'/../core/Schema.php');
	require_once(dirname(__FILE__).'/../core/Mail.php');
	require_once(dirname(__FILE__).'/../core/StoreStatus.php');
	require_once(dirname(__FILE__).'/../core/SmtpMailer.php');
	require_once(dirname(__FILE__).'/../core/Notification.php');
	require_once(dirname(__FILE__).'/../core/Routes.php');
	require_once(dirname(__FILE__).'/../core/Theme.php');
	require_once(dirname(__FILE__).'/../core/SiteAssets.php');
	require_once(dirname(__FILE__).'/../core/Performance.php');
	require_once(dirname(__FILE__).'/../core/Lcp.php');
	require_once(dirname(__FILE__).'/../core/CanonicalHost.php');

	Performance::ensureDefaults();
	App::configureErrors();
	require_once(dirname(__FILE__).'/../core/Seo.php');
	require_once(dirname(__FILE__).'/../core/SchemaOrg.php');

	App::sendSecurityHeaders();
	Cookie::autoLoginFromRememberCookie();
	$rootDir 	= Settings::get('FOLDER');
	$domain		= Settings::get('DOMAIN');
	CanonicalHost::redirectIfNeeded();
	$theme		= Settings::get('THEME') ?: 'default';
	$previewTheme = (string) Tools::getValue('theme_preview');

	if ($previewTheme !== '' && Theme::isValidName($previewTheme)) {
		$theme = $previewTheme;
	}
	Theme::ensureColorsFile($theme);
	Theme::ensureCustomCss($theme);
	$themeOptions = Theme::getResolvedOptions($theme);

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

	require_once(dirname(__FILE__).'/../libs/Smarty.class.php');
	require_once(dirname(__FILE__).'/smarty_setup.php');
	$smarty = new Smarty\Smarty;
	$smarty->setTemplateDir(dirname(__FILE__) . '/../templates/');
	fshop_configure_smarty($smarty);

	require_once(dirname(__FILE__).'/page.php');
	$page 		= new Page();
	$saveToken	= md5(date('Y-m-d H:0:0').'RB');

	$sonuc 	= '';

	$token 			= $_SESSION['csrf_token'] ?? '';
	if ($token === '') {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		$token = $_SESSION['csrf_token'];
	}

	Schema::ensure();
	Cms::ensureSchema();
	$cart = Cart::getSummary();
	$customer = Customer::getCurrent();
	$isLoggedIn = Customer::isLoggedIn();
	$notificationCount = $isLoggedIn ? Notification::getUnreadCount(Customer::getId()) : 0;
	$headerNotifications = $isLoggedIn ? Notification::getListForUser(Customer::getId(), 8) : [];
	$menuCategories = Category::getMenuListWithChildren();
	$favoriteCount = Favorite::getCount();

	Module::bootstrap('front');
	$moduleAssets = Module::getHeadAssets();
	
	function translate($text)
	{
		return Lang::translate((string) $text);
	}

	$cartI18n = [
		'empty' => translate('Cart is empty'),
		'startShopping' => translate('Start shopping'),
		'inStock' => translate('In stock'),
		'outOfStock' => translate('Out of Stock'),
		'remove' => translate('Delete'),
		'total' => translate('Total'),
		'free' => translate('Free'),
		'decrease' => translate('Down'),
		'increase' => translate('Up'),
		'stockLimit' => translate('You have reached the maximum number of products'),
		'clearConfirm' => translate('Remove all items from the cart?'),
		'connectionError' => translate('Could not connect to the server'),
		'genericError' => translate('An error occurred'),
		'selectVariation' => translate('Select product options'),
		'enterMeasure' => translate('Please enter width and length'),
		'required' => translate('Required'),
		'addToCart' => translate('Add To Cart'),
		'productAddedTitle' => translate('Product added to cart'),
		'productAddedLead' => translate('Great! Product added to your cart.'),
		'cartItemsCount' => translate('There are %d items in your cart.'),
		'goToCart' => translate('Go to cart'),
		'continueShopping' => translate('Continue shopping'),
		'productLabel' => translate('Product'),
	];
	
	$cargoHints = Cargo::getDisplayHints();

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
		'contactAddress'	=> Settings::get('CONTACT_ADDRESS') ?: '',
		'contactPhone' 		=> Settings::get('CONTACT_PHONE') ?: '',
		'contactPhoneTel' 	=> Settings::get('CONTACT_PHONE_TEL') ?: '',
		'freeShippingMin' 	=> $cargoHints['free_shipping_min'],
		'shippingFee' 		=> $cargoHints['shipping_fee'],
		'postalCode' 		=> Settings::get('POSTAL_CODE') ?: '0',
		'contactCity' 		=> Settings::get('CONTACT_CITY') ?: '',
		'addressCountry' 	=> Settings::get('CONTACT_COUNTRY') ?: '',
		'latitude' 			=> Settings::get('LATITUDE') ?: '11111111',
		'longitude' 		=> Settings::get('LONGITUDE') ?: '1111111',
		'facebookLink' 		=> Settings::get('FACEBOOK_LINK') ?: '',
		'xLink' 			=> Settings::get('X_LINK') ?: '',
		'instagramLink' 	=> Settings::get('INSTAGRAM_LINK') ?: '',
		'youtubeLink' 		=> Settings::get('YOUTUBE_LINK') ?: '',
		'linkedinLink' 		=> Settings::get('LINKEDIN_LINK') ?: '',
		'pinterestLink' 	=> Settings::get('PINTEREST_LINK') ?: '',
		'tiktokLink' 		=> Settings::get('TIKTOK_LINK') ?: '',
		'socialLinks' => [
			'facebook' => Settings::get('FACEBOOK_LINK') ?: '',
			'instagram' => Settings::get('INSTAGRAM_LINK') ?: '',
			'x' => Settings::get('X_LINK') ?: '',
			'youtube' => Settings::get('YOUTUBE_LINK') ?: '',
			'linkedin' => Settings::get('LINKEDIN_LINK') ?: '',
			'pinterest' => Settings::get('PINTEREST_LINK') ?: '',
			'tiktok' => Settings::get('TIKTOK_LINK') ?: '',
		],
		'themeColors'		=> Theme::getColors($theme),
		'openHour' 			=> Settings::get('OPEN_HOUR') ?: '09:00',
		'closeHour' 		=> Settings::get('CLOSE_HOUR') ?: '18:00',
		'selectLang' 		=> $selectLang ?? 'en',
		'token' 			=> $token,
		'sonuc' 			=> $sonuc,
		'domain' 			=> $domain,
		'cart' 				=> $cart,
		'customer' 			=> $customer,
		'isLoggedIn'		=> $isLoggedIn,
		'menuCategories' 	=> $menuCategories,
		'favoriteCount' 	=> $favoriteCount,
		'notificationCount' => $notificationCount,
		'headerNotifications' => $headerNotifications,
		'cmsFooterLinks' 	=> Cms::getFooterLinks(),
		'shopLanguages' 	=> Lang::getAvailable(),
		'defaultLang' 		=> Lang::getDefault(),
		'langSwitcher' 		=> Lang::getSwitcherList(),
		'displayCurrency' 	=> Currency::getDisplayCurrency(),
		'shopCurrencyCode' 	=> Currency::getShopCurrency(),
		'siteLogos' => [
			'header' => SiteAssets::resolveLogoUrl('header'),
			'bar' => SiteAssets::resolveLogoUrl('bar'),
			'footer' => SiteAssets::resolveLogoUrl('footer'),
		],
		'footerDescription' => Settings::getFooterDescription(),
		'moduleAssets' 		=> $moduleAssets,
		'hooks' 			=> Module::getRenderedDisplayHooks(),
		'cartI18n' 			=> $cartI18n,
		'cartI18nJson' 		=> json_encode($cartI18n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
		'newsletterApiUrl' 	=> rtrim($domain, '/') . '/api/module.php?m=newsletter&action=subscribe',
		'themeOptions'		=> $themeOptions,
		'activeTheme'		=> $theme,
	));
	$smarty->registerPlugin('modifier', 'translate', 'translate');

	Performance::bootstrapFront();
