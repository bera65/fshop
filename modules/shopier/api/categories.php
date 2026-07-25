<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

if (!class_exists('Admin')) {
	require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

header('Content-Type: application/json; charset=utf-8');

Admin::requireModuleApiAuth();

require_once dirname(__DIR__) . '/lib/ShopierApi.php';
require_once dirname(__DIR__) . '/lib/ProductSyncService.php';

if (!Shopier\ProductSyncService::isConfigured()) {
	echo json_encode(['success' => false, 'message' => 'Shopier API anahtarı tanımlı değil'], JSON_UNESCAPED_UNICODE);
	exit;
}

$all = [];
$page = 1;

do {
	$result = Shopier\ProductSyncService::api()->listCategories([
		'limit' => 50,
		'page' => $page,
		'sort' => 'asc',
	]);

	if (!$result['ok']) {
		echo json_encode(['success' => false, 'message' => $result['message']], JSON_UNESCAPED_UNICODE);
		exit;
	}

	$chunk = $result['data'] ?? [];

	if (!is_array($chunk) || $chunk === []) {
		break;
	}

	foreach ($chunk as $row) {
		if (is_array($row)) {
			$all[] = $row;
		}
	}

	if (count($chunk) < 50) {
		break;
	}

	$page++;
} while ($page <= 20);

echo json_encode([
	'success' => true,
	'categories' => $all,
], JSON_UNESCAPED_UNICODE);
exit;
