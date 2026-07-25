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

$raw = (string) Tools::getValue('pages_json', '');
$decoded = json_decode($raw, true);

if (!is_array($decoded) || $decoded === []) {
	echo json_encode([
		'success' => false,
		'message' => 'SEO sayfa verisi gerekli',
	], JSON_UNESCAPED_UNICODE);
	exit;
}

$pages = [];

foreach ($decoded as $row) {
	if (!is_array($row)) {
		continue;
	}

	$id = preg_replace('/[^a-z0-9\-_]/i', '', (string) ($row['id'] ?? ''));

	if ($id === '') {
		continue;
	}

	$pages[] = [
		'id' => $id,
		'label' => mb_substr(trim((string) ($row['label'] ?? $id)), 0, 128),
		'title' => mb_substr(trim((string) ($row['title'] ?? '')), 0, 255),
		'description' => mb_substr(trim((string) ($row['description'] ?? '')), 0, 512),
		'default_title' => mb_substr(trim((string) ($row['default_title'] ?? '')), 0, 255),
		'default_desc' => mb_substr(trim((string) ($row['default_desc'] ?? '')), 0, 512),
	];
}

if ($pages === []) {
	echo json_encode([
		'success' => false,
		'message' => 'Geçerli SEO sayfası bulunamadı',
	], JSON_UNESCAPED_UNICODE);
	exit;
}

$lang = (string) Tools::getValue('lang', Settings::get('AI_ASSISTANT_LANG') ?: 'tr');
$result = AiAssistantClient::writeSeoPages($pages, $lang);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
exit;
