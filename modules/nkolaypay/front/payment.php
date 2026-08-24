<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

$returnTokenQ = trim((string) Tools::getValue('rt'));
$hasFailFlag = (bool) Tools::getValue('fail');

if ($returnTokenQ !== '' && $hasFailFlag) {
	header('Location: ' . NkolaypayModule::getResultUrl($returnTokenQ, true));
	exit;
}

if (!Order::hasPendingPayment()) {
	$pendingFromDb = null;

	if ($returnTokenQ !== '') {
		$pendingFromDb = NkolaypayModule::loadPendingByReturnToken($returnTokenQ);
	}

	if ($pendingFromDb) {
		NkolaypayModule::restoreCartFromPending((string) $pendingFromDb['reference']);
	}
}

$cart = Cart::getSummary();

if (!Order::hasPendingPayment() || $cart['empty']) {
	header('Location: ' . $domain . 'checkout');
	exit;
}

$pendingData = $_SESSION['pending_order_data'];

if (!is_array($pendingData) || (string) ($pendingData['payment_method'] ?? '') !== 'nkolaypay') {
	header('Location: ' . $domain . 'checkout');
	exit;
}

/** @var NkolaypayModule|null $nkolaypay */
$nkolaypay = Module::getPaymentModule('nkolaypay');

if (!$nkolaypay || !NkolaypayModule::isConfigured()) {
	header('Location: ' . $domain . 'checkout');
	exit;
}

$reference = trim((string) ($pendingData['_nkolaypay_reference'] ?? ''));

if ($reference === '') {
	$reference = Order::reserveReference();
	$pendingData['_nkolaypay_reference'] = $reference;
	$_SESSION['pending_order_data'] = $pendingData;
}

$returnToken = NkolaypayModule::persistPendingCheckout($reference, $pendingData, $cart);

$previewOrder = NkolaypayModule::buildPreviewOrder($pendingData, $cart);
$paymentError = '';

if (Tools::getValue('fail')) {
	$paymentError = NkolaypayModule::getReturnError($reference);

	if ($paymentError === '') {
		$paymentError = (string) ($_SESSION['nkolaypay_payment_error'] ?? 'Ödeme tamamlanamadı. Lütfen tekrar deneyin.');
	}

	unset($_SESSION['nkolaypay_payment_error']);
}

$gatewayUrl = '';
$gatewayFields = [];
$shouldAutoSubmit = false;

if ($paymentError === '') {
	$paymentResult = $nkolaypay->buildGatewayPost($previewOrder, $returnToken);

	if (!empty($paymentResult['success']) && !empty($paymentResult['gateway_url']) && !empty($paymentResult['fields'])) {
		$gatewayUrl = (string) $paymentResult['gateway_url'];
		$gatewayFields = is_array($paymentResult['fields']) ? $paymentResult['fields'] : [];
		$shouldAutoSubmit = true;
		NkolaypayModule::rememberGatewayAttempt(
			$reference,
			(string) ($gatewayFields['clientRefCode'] ?? ''),
			(float) ($previewOrder['total'] ?? 0)
		);
	} else {
		$paymentError = $paymentResult['message'] ?? 'N Kolay Pay ödeme başlatılamadı';
	}
}

$skipPageRender = true;

$smarty->assign([
	'pageName' => 'nkolaypay-payment',
	'pageTitle' => 'Kart ile Ödeme',
	'pageDesc' => 'N Kolay Pay ile güvenli 3D ödeme',
	'css' => 'checkout.css',
	'js' => false,
	'paymentError' => $paymentError,
	'gatewayUrl' => $gatewayUrl,
	'gatewayFields' => $gatewayFields,
	'shouldAutoSubmit' => $shouldAutoSubmit,
	'order' => $previewOrder,
	'breadcrumb' => [
		['name' => 'Anasayfa', 'url' => $domain],
		['name' => 'Ödeme', 'url' => $domain . 'checkout'],
		['name' => 'Kart ile Ödeme', 'url' => ''],
	],
]);

$smarty->display(_THEME_BASE_DIR_ . 'header.tpl');
$smarty->display('file:' . dirname(__DIR__) . '/assets/templates/front/payment_page.tpl');
$smarty->display(_THEME_BASE_DIR_ . 'footer.tpl');
