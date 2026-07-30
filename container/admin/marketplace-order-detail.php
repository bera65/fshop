<?php

if (!defined('IN_ADMIN')) {
	exit;
}

$query = http_build_query(array_filter([
	'platform' => (string) Tools::getValue('platform', ''),
	'order_number' => (string) Tools::getValue('order_number', ''),
	'package_id' => (string) Tools::getValue('package_id', ''),
	'open' => '1',
], static function ($v) {
	return $v !== null && $v !== '';
}));

header('Location: ' . Admin::url('marketplace-orders' . ($query !== '' ? '?' . $query : '')));
exit;
