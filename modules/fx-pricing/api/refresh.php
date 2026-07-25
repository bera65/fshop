<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

if (!class_exists('Admin')) {
	require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

require_once dirname(__DIR__) . '/lib/FxPricingService.php';

header('Content-Type: application/json; charset=utf-8');

Admin::requireCronTokenOrAdminAuth();

if (!FxPricingService::isEnabled()) {
	echo json_encode(['success' => false, 'message' => 'Modül kapalı'], JSON_UNESCAPED_UNICODE);
	exit;
}

$result = FxPricingService::refreshAll(true);

echo json_encode([
	'success' => true,
	'updated' => $result['products'],
	'rates' => $result['rates'],
	'message' => $result['products'] . ' ürün güncellendi',
], JSON_UNESCAPED_UNICODE);
