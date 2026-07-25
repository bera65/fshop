<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

header('Content-Type: application/json; charset=utf-8');

$token = trim((string) Tools::getValue('token', ''));

if ($token === '') {
	$token = trim((string) ($_SERVER['HTTP_X_CRON_TOKEN'] ?? ''));
}

$shopToken = (string) Settings::get('SHOP_TOKEN');

if ($shopToken === '' || !hash_equals($shopToken, $token)) {
	http_response_code(403);
	echo json_encode(['success' => false, 'message' => 'Geçersiz token'], JSON_UNESCAPED_UNICODE);
	exit;
}

require_once dirname(__DIR__) . '/lib/XmlImportService.php';

$result = XmlImportService::importFromSavedSettings();

Settings::set(XmlImportService::SETTING_LAST_CRON, date('Y-m-d H:i:s'));

http_response_code(!empty($result['success']) ? 200 : 400);
echo json_encode([
	'success' => !empty($result['success']),
	'message' => (string) ($result['message'] ?? ''),
	'stats' => $result['stats'] ?? null,
	'ran_at' => date('Y-m-d H:i:s'),
], JSON_UNESCAPED_UNICODE);
exit;
