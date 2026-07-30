<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

require_once dirname(__DIR__) . '/lib/PayPalClient.php';
require_once dirname(__DIR__) . '/paypal.php';

// PayPal return: ?token=ORDER_ID&PayerID=...
$paypalOrderId = trim((string) Tools::getValue('token', ''));

if ($paypalOrderId === '') {
	$paypalOrderId = trim((string) Tools::getValue('paypal_order_id', ''));
}

$result = PaypalModule::completeReturn($paypalOrderId);
$base = rtrim((string) $domain, '/') . '/';

if ($result['ok']) {
	$idOrder = (int) ($result['id_order'] ?? 0);
	$ref = (string) ($result['reference'] ?? '');

	if ($idOrder > 0) {
		header('Location: ' . $base . 'checkout-success?id=' . $idOrder);
		exit;
	}

	if ($ref !== '') {
		header('Location: ' . $base . 'checkout-success?ref=' . rawurlencode($ref));
		exit;
	}

	header('Location: ' . $base . 'checkout-success');
	exit;
}

$msg = rawurlencode(mb_substr((string) ($result['message'] ?? 'Ödeme başarısız'), 0, 180));
header('Location: ' . $base . 'paypal-payment?fail=' . $msg . '&stay=1');
exit;
