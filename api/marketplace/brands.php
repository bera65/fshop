<?php

if (!defined('IN_SCRIPT')) {
	exit;
}


header('Content-Type: application/json; charset=utf-8');

if (!Admin::isLoggedIn()) {
	echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim'], JSON_UNESCAPED_UNICODE);
	exit;
}


$name = trim((string) Tools::getValue('name', ''));

if ($name === '') {
	echo json_encode(['success' => false, 'message' => 'Marka adı gerekli'], JSON_UNESCAPED_UNICODE);
	exit;
}

if (!Trendyol\ProductSyncService::isConfigured()) {
	echo json_encode(['success' => false, 'message' => 'API kimlik bilgileri eksik'], JSON_UNESCAPED_UNICODE);
	exit;
}

$result = Trendyol\ProductSyncService::api()->getBrand($name);

if (Trendyol\ProductSyncService::isApiError($result)) {
	echo json_encode([
		'success' => false,
		'message' => (string) ($result['message'] ?? 'Marka araması başarısız'),
	], JSON_UNESCAPED_UNICODE);
	exit;
}

$brands = [];

if (isset($result['brands']) && is_array($result['brands'])) {
	$brands = $result['brands'];
} elseif (is_array($result)) {
	$brands = $result;
}

echo json_encode([
	'success' => true,
	'brands' => $brands,
], JSON_UNESCAPED_UNICODE);
exit;
