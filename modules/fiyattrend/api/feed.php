<?php

/**
 * FiyatTrend XML Feed
 * URL: /api/module.php?m=fiyattrend&action=feed&token=TOKEN
 */

if (!defined('IN_SCRIPT')) {
	exit;
}

$requestToken = trim((string) Tools::getValue('token', ''));
$savedToken = FiyattrendFeedService::getFeedToken();

if ($savedToken === '' || !hash_equals($savedToken, $requestToken)) {
	http_response_code(401);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['success' => false, 'message' => 'Geçersiz token']);
	exit;
}

if (!FiyattrendFeedService::isEnabled()) {
	http_response_code(503);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['success' => false, 'message' => 'Feed devre dışı']);
	exit;
}

try {
	$xml = FiyattrendFeedService::buildFeed();

	header('Content-Type: application/xml; charset=UTF-8');
	header('Content-Disposition: inline; filename="fiyattrend-feed.xml"');
	header('X-Feed-Generator: FShop FiyatTrend Module');
	header('Cache-Control: public, max-age=' . ((int) (Settings::get('FT_CACHE_TTL') ?: 360) * 60));

	echo $xml;
} catch (Exception $e) {
	http_response_code(500);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['success' => false, 'message' => 'Feed üretim hatası: ' . $e->getMessage()]);
}
