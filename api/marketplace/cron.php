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

// send=0 → pazaryerine stok/fiyat gönderme; yalnızca FShop stok düşebilir (yardımda yok)
$send = Tools::getValue('send', null);

if ($send !== null && (string) $send === '0') {
	Marketplace::setAllowMarketplaceStockPush(false);
}

$type = strtolower(trim((string) Tools::getValue('type', 'orders')));
$platform = strtolower(trim((string) Tools::getValue('platform', 'trendyol')));

if (!in_array($platform, ['trendyol', 'hepsiburada', 'n11'], true)) {
	$platform = 'trendyol';
}

if ($type === 'questions' || $type === 'qna') {
	if ($platform === 'hepsiburada') {
		$result = Hepsiburada\QuestionService::syncQuestions(1, 50);
	} elseif ($platform === 'n11') {
		$result = N11\QuestionService::syncQuestions(0, 50);
	} else {
		$result = Trendyol\QuestionService::syncQuestions(0, 50);
	}
} else {
	$start = trim((string) Tools::getValue('start_date', ''));
	$end = trim((string) Tools::getValue('end_date', ''));

	if ($platform === 'hepsiburada') {
		$result = Hepsiburada\OrderService::syncOrders(
			$start !== '' ? $start : null,
			$end !== '' ? $end : null
		);
	} elseif ($platform === 'n11') {
		$result = N11\OrderService::syncOrders();
	} else {
		$result = Trendyol\OrderService::syncOrders(
			$start !== '' ? $start : null,
			$end !== '' ? $end : null
		);
	}
}

echo json_encode([
	'success' => $result['ok'],
	'message' => $result['message'],
	'count' => $result['count'] ?? 0,
	'stock_updates' => $result['stock_updates'] ?? 0,
	'platform' => $platform,
	'stock_push' => Marketplace::allowMarketplaceStockPush(),
], JSON_UNESCAPED_UNICODE);
exit;
