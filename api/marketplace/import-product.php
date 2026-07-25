<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

use Trendyol\ProductImportService;

header('Content-Type: application/json; charset=utf-8');

if (!Admin::isLoggedIn()) {
	http_response_code(401);
	echo json_encode(['success' => false, 'message' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
	exit;
}

$token = (string) Tools::getValue('token');
$sessionToken = (string) ($_SESSION['admin_csrf_token'] ?? '');

if ($sessionToken === '' || !hash_equals($sessionToken, $token)) {
	http_response_code(403);
	echo json_encode(['success' => false, 'message' => 'Geçersiz istek'], JSON_UNESCAPED_UNICODE);
	exit;
}

$result = ProductImportService::import(
	(string) Tools::getValue('source'),
	(string) Tools::getValue('barcode'),
	[
		'category_mode' => (string) Tools::getValue('category_mode', 'create'),
		'brand_mode' => (string) Tools::getValue('brand_mode', 'create'),
		'id_category' => (int) Tools::getValue('id_category'),
		'id_brand' => (int) Tools::getValue('id_brand'),
	]
);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
exit;
