<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

if (!class_exists('Admin')) {
	require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

require_once dirname(__DIR__) . '/lib/AiClient.php';

header('Content-Type: application/json; charset=utf-8');

if (!Admin::isLoggedIn()) {
	echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim'], JSON_UNESCAPED_UNICODE);
	exit;
}

$token = (string) Tools::getValue('token');
$sessionToken = (string) ($_SESSION['admin_csrf_token'] ?? '');

if ($sessionToken === '' || !hash_equals($sessionToken, $token)) {
	echo json_encode(['success' => false, 'message' => 'Geçersiz istek'], JSON_UNESCAPED_UNICODE);
	exit;
}

if (!AiAssistantClient::isConfigured()) {
	echo json_encode([
		'success' => false,
		'message' => 'API anahtarı eksik. Modül ayarlarından ekleyin.',
	], JSON_UNESCAPED_UNICODE);
	exit;
}

$pageName = preg_replace('/[^a-z0-9\-_]/i', '', (string) Tools::getValue('page_name', '')) ?: 'unknown';
$pageTitle = mb_substr(trim((string) Tools::getValue('page_title', '')), 0, 255);
$pageText = mb_substr(trim(strip_tags((string) Tools::getValue('page_text', ''))), 0, 12000);

$result = AiAssistantClient::summarizeAdminPage($pageName, $pageTitle, $pageText);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
exit;
