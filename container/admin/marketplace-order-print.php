<?php

if (!defined('IN_ADMIN')) {
	exit;
}

$keys = [];

$bulk = trim((string) Tools::getValue('keys', ''));

if ($bulk !== '') {
	$parts = preg_split('/[|,]+/', $bulk) ?: [];

	foreach ($parts as $part) {
		$part = trim((string) $part);

		if ($part === '') {
			continue;
		}

		$chunks = explode('::', $part);

		if (count($chunks) < 2) {
			continue;
		}

		$keys[] = [
			'platform' => (string) ($chunks[0] ?? ''),
			'order_number' => (string) ($chunks[1] ?? ''),
			'package_id' => (string) ($chunks[2] ?? ''),
		];
	}
} else {
	$keys[] = [
		'platform' => (string) Tools::getValue('platform', ''),
		'order_number' => (string) Tools::getValue('order_number', ''),
		'package_id' => (string) Tools::getValue('package_id', ''),
	];
}

$orders = MarketplaceAdmin::getMarketplaceOrdersForPrint($keys);

if (!$orders) {
	http_response_code(404);
	AdminPage::add('404', 'Sipariş bulunamadı', true);
	return;
}

$smarty->assign([
	'printOrders' => $orders,
	'printAuto' => Tools::getValue('auto') === '1',
	'printSiteName' => Settings::get('SITE_NAME'),
	'domain' => rtrim((string) Settings::get('DOMAIN'), '/') . '/',
]);

$title = count($orders) === 1
	? (adminT('Marketplace Order #') . (string) ($orders[0]['order_number'] ?? ''))
	: (adminT('Marketplace Orders') . ' (' . count($orders) . ')');

AdminPage::add('marketplace-order-print', $title, true);
