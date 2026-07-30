<?php

define('IN_SCRIPT', true);

// Admin oturumu + Admin:: sınıfı gerekir (cron hariç tüm action'lar admin paneli).
require_once dirname(__DIR__) . '/config/admin_bootstrap.php';
require_once dirname(__DIR__) . '/core/marketplace/bootstrap.php';

$action = trim((string) Tools::getValue('action'));

$actions = [
	'cron' => 'cron.php',
	'sync' => 'sync.php',
	'update-price' => 'update-price.php',
	'unlink' => 'unlink.php',
	'refresh' => 'refresh.php',
	'brands' => 'brands.php',
	'categories' => 'categories.php',
	'attributes' => 'attributes.php',
	'fetch-orders' => 'fetch-orders.php',
	'fetch-questions' => 'fetch-questions.php',
		'export-orders' => 'export-orders.php',
	'answer-question' => 'answer-question.php',
	'import-product' => 'import-product.php',
	'link-existing' => 'link-existing.php',
	'update-stock' => 'update-stock.php',
	'order-action' => 'order-action.php',
];

if ($action === '' || !isset($actions[$action])) {
	http_response_code(400);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['success' => false, 'message' => 'Geçersiz işlem'], JSON_UNESCAPED_UNICODE);
	exit;
}

$file = __DIR__ . '/marketplace/' . $actions[$action];

if (!is_file($file)) {
	http_response_code(404);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['success' => false, 'message' => 'Endpoint bulunamadı'], JSON_UNESCAPED_UNICODE);
	exit;
}

require $file;
