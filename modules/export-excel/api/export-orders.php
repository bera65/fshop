<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

if (!class_exists('Admin')) {
	require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

require_once dirname(__DIR__) . '/lib/ExportExcelService.php';

if (!Admin::isLoggedIn()) {
	http_response_code(403);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim'], JSON_UNESCAPED_UNICODE);
	exit;
}

$token = (string) Tools::getValue('token');
$sessionToken = (string) ($_SESSION['admin_csrf_token'] ?? '');

if ($sessionToken === '' || !hash_equals($sessionToken, $token)) {
	http_response_code(403);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['success' => false, 'message' => 'Geçersiz istek'], JSON_UNESCAPED_UNICODE);
	exit;
}

$status = (int) Tools::getValue('status');
$filters = Order::normalizeAdminFilters([
	'reference' => Tools::getValue('reference'),
	'customer' => Tools::getValue('customer'),
	'date_from' => Tools::getValue('date_from'),
	'date_to' => Tools::getValue('date_to'),
]);

ExportExcelService::exportOrders($status, $filters);
