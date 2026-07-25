<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['success' => false, 'message' => 'Method not allowed']);
	exit;
}

if (!class_exists('Admin')) {
	require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

Admin::requireModuleApiAuth();

try {
	$result = FiyattrendFeedService::regenerate();
	echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
