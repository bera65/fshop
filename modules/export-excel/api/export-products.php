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

$query = trim((string) Tools::getValue('q'));
$idCategory = (int) Tools::getValue('category');
$idBrand = (int) Tools::getValue('brand');
$activeFilter = Tools::getIsset('active') ? (int) Tools::getValue('active') : -1;

ExportExcelService::exportProducts($query, $idCategory, $idBrand, $activeFilter);
