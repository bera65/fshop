<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

$request = array_merge($_GET, $_POST);
$result = NkolaypayModule::handleBrowserReturn($request);
$reference = (string) ($result['reference'] ?? '');
$returnToken = (string) ($result['return_token'] ?? '');

if (empty($result['success'])) {
	$message = (string) ($result['message'] ?? 'Ödeme sonucu başarısız');

	if ($reference !== '') {
		NkolaypayModule::saveReturnError($reference, $message);
		NkolaypayModule::restoreCartFromPending($reference);
	}

	if ($returnToken !== '') {
		header('Location: ' . NkolaypayModule::getResultUrl($returnToken, true));
		exit;
	}

	$_SESSION['nkolaypay_payment_error'] = $message;
	header('Location: ' . rtrim($domain, '/') . '/nkolaypay-payment?fail=1');
	exit;
}

Order::clearPendingPayment();

$idOrder = (int) ($result['id_order'] ?? 0);

if ($idOrder <= 0 && $reference !== '') {
	$idOrder = (int) DB::getValue('SELECT id_order FROM orders WHERE reference = ? LIMIT 1', [$reference]);
}

$target = $idOrder > 0
	? rtrim($domain, '/') . '/checkout-success?id=' . $idOrder
	: rtrim($domain, '/') . '/checkout';

header('Location: ' . $target);
exit;
