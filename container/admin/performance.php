<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	require_once dirname(__DIR__, 2) . '/core/Performance.php';

	$flash = '';
	$flashType = 'info';
	$config = Performance::getConfig();
	$stats = Performance::getStats();
	$stats['compile_size_kb'] = number_format($stats['compile_bytes'] / 1024, 1);
	$stats['page_size_kb'] = number_format($stats['page_bytes'] / 1024, 1);
	$debugMode = Performance::getDebugMode();
	$debugActive = App::isDebug();
	$envDebug = App::isDebugFromEnv();

	if (Tools::isSubmit('savePerformance')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = Performance::saveConfig([
				Performance::KEY_CACHE => Tools::getValue(Performance::KEY_CACHE),
				Performance::KEY_PAGE_CACHE => Tools::getValue(Performance::KEY_PAGE_CACHE),
				Performance::KEY_PAGE_CACHE_TTL => Tools::getValue(Performance::KEY_PAGE_CACHE_TTL),
				Performance::KEY_GZIP => Tools::getValue(Performance::KEY_GZIP),
				Performance::KEY_HTML_MINIFY => Tools::getValue(Performance::KEY_HTML_MINIFY),
				Performance::KEY_CSS_BUNDLE => Tools::getValue(Performance::KEY_CSS_BUNDLE),
				'perf_debug_mode' => Tools::getValue('perf_debug_mode'),
			]);

			$config = Performance::getConfig();
			$debugMode = Performance::getDebugMode();
			$debugActive = App::isDebug();
			$flash = $result['message'];
			$flashType = $result['success'] ? 'success' : 'danger';
		}
	}

	if (Tools::isSubmit('clearPerformanceCache')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = Performance::clearCaches();
			$stats = $result['stats'] ?? Performance::getStats();
			$stats['compile_size_kb'] = number_format($stats['compile_bytes'] / 1024, 1);
			$stats['page_size_kb'] = number_format($stats['page_bytes'] / 1024, 1);
			$flash = $result['message'];
			$flashType = 'success';
		}
	}

	$smarty->assign([
		'perfConfig' => $config,
		'perfStats' => $stats,
		'perfDebugMode' => $debugMode,
		'perfDebugActive' => $debugActive,
		'perfEnvDebug' => $envDebug,
		'flash' => $flash,
		'flashType' => $flashType,
	]);

	AdminPage::add('performance', 'Performance');
