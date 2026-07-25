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

$mode = (string) Tools::getValue('mode', 'translate');
$targetLang = strtolower(trim((string) Tools::getValue('target_lang', 'tr')));
$rawItems = Tools::getValue('items', '');

if (is_string($rawItems)) {
	$decodedItems = json_decode($rawItems, true);
} else {
	$decodedItems = $rawItems;
}

if (!is_array($decodedItems)) {
	echo json_encode([
		'success' => false,
		'message' => 'Geçersiz çeviri listesi',
	], JSON_UNESCAPED_UNICODE);
	exit;
}

$result = AiAssistantClient::translateUiStrings($decodedItems, $targetLang, $mode);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
exit;
