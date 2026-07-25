<?php
	require_once __DIR__ . '/config/install_gate.php';

	if (!fshop_is_installed()) {
		fshop_redirect_to_installer();
	}

	define('IN_SCRIPT', true);
	require_once dirname(__FILE__) . '/config/settings.php';

	if (StoreStatus::shouldBlockFront()) {
		StoreStatus::renderMaintenance();
	}

	$container = Security::sanitizeContainerSlug((string) Tools::getValue('container'));

	if ($container === '') {
		$container = 'home';
	}
	Routes::redirectLegacyIfNeeded($container);

	$skipPageRender = false;
	$searchQuery = trim((string) (Tools::getValue('q') ?: Tools::getValue('query')));

	$protected = Routes::protectedPages();

	if (Tools::getValue('login') === '1' && !Customer::isLoggedIn()) {
		header('Location: ' . $domain . 'login');
		exit;
	}

	if (in_array($container, $protected, true) && !Customer::isLoggedIn()) {
		$redirectUrl = rtrim($domain, '/') . '/' . $container;
		$query = $_GET;
		unset($query['container']);
		if ($query !== []) {
			$redirectUrl .= '?' . http_build_query($query);
		}
		$_SESSION['auth_redirect'] = $redirectUrl;
		header('Location: ' . $domain . 'login');
		exit;
	}

	$pageTitle = 'FShop';
	$pageDesc = Settings::get('SITE_NAME');
	$css = $js = false;
	$noLayout = false;

	$filePath = dirname(__FILE__) . '/container/front/' . $container . '.php';

	if (!file_exists($filePath)) {
		$moduleRoute = Module::resolveFrontRoute($container);

		if ($moduleRoute) {
			$filePath = $moduleRoute;
		} elseif (($category = Category::getByLink($container))) {
			if (!defined('CATEGORY_SLUG')) {
				define('CATEGORY_SLUG', $container);
			}
			$container = 'category';
			$filePath = dirname(__FILE__) . '/container/front/category.php';
		} elseif (Cms::exists($container)) {
			if (!defined('CMS_SLUG')) {
				define('CMS_SLUG', $container);
			}
			$container = 'cms';
			$filePath = dirname(__FILE__) . '/container/front/cms.php';
		}
	}

	if (file_exists($filePath)) {
		include $filePath;
		if (!$skipPageRender) {
			Lcp::assignPreload($container);
			$smarty->assign('searchQuery', $searchQuery);
			$page->add($container, $pageTitle, $css, $js, $pageDesc, $noLayout);
		}
	} else {
		http_response_code(404);
		$page->add('404', 'Sayfa Bulunamadı');
	}

	if (ob_get_level() > 0) {
		while (ob_get_level() > 0) {
			ob_end_flush();
		}
	}
