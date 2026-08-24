<?php
	require_once dirname(__DIR__) . '/config/install_gate.php';

	if (!fshop_is_installed()) {
		fshop_redirect_to_installer();
	}

	define('IN_SCRIPT', true);
	require_once dirname(__DIR__) . '/config/admin_bootstrap.php';

	$container = Security::sanitizeContainerSlug((string) Tools::getValue('container'));

	if ($container === '') {
		$container = 'dashboard';
	}
	$publicPages = ['login', 'forgot-password', 'reset-password'];

	if (!in_array($container, $publicPages, true) && !Admin::isLoggedIn()) {
		header('Location: ' . Admin::url('login'));
		exit;
	}

	if (in_array($container, $publicPages, true) && Admin::isLoggedIn()) {
		header('Location: ' . Admin::url());
		exit;
	}

	$pageTitle = 'Yönetim Paneli';
	$filePath = dirname(__DIR__) . '/container/admin/' . $container . '.php';

	if (!file_exists($filePath)) {
		$moduleName = Module::resolveAdminModuleName($container);

		if ($moduleName) {
			Module::dispatchAdminPage($moduleName);
			if (ob_get_level() > 0) {
				ob_end_flush();
			}
			exit;
		}

		$moduleRoute = Module::resolveAdminRoute($container);

		if ($moduleRoute) {
			$filePath = $moduleRoute;
		}
	}

	if (file_exists($filePath)) {
		include $filePath;
	} else {
		http_response_code(404);
		$pageTitle = 'Sayfa Bulunamadı';
		AdminPage::add('404', $pageTitle);
	}

	if (ob_get_level() > 0) {
		ob_end_flush();
	}
