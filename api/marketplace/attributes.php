<?php

if (!defined('IN_SCRIPT')) {
	exit;
}


header('Content-Type: application/json; charset=utf-8');

if (!Admin::isLoggedIn()) {
	echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim'], JSON_UNESCAPED_UNICODE);
	exit;
}


$categoryId = (int) Tools::getValue('category_id', 0);

if ($categoryId <= 0) {
	echo json_encode(['success' => false, 'message' => 'Kategori ID gerekli'], JSON_UNESCAPED_UNICODE);
	exit;
}

if (!Trendyol\ProductSyncService::isConfigured()) {
	echo json_encode(['success' => false, 'message' => 'API kimlik bilgileri eksik'], JSON_UNESCAPED_UNICODE);
	exit;
}

$result = Trendyol\ProductSyncService::api()->getAttirupes($categoryId);

if (Trendyol\ProductSyncService::isApiError($result)) {
	echo json_encode([
		'success' => false,
		'message' => (string) ($result['message'] ?? 'Özellikler alınamadı'),
	], JSON_UNESCAPED_UNICODE);
	exit;
}

$categoryAttributes = $result['categoryAttributes'] ?? ($result['categoryAttributeList'] ?? []);

echo json_encode([
	'success' => true,
	'categoryId' => $categoryId,
	'categoryAttributes' => is_array($categoryAttributes) ? $categoryAttributes : [],
	'raw' => $result,
], JSON_UNESCAPED_UNICODE);
exit;
