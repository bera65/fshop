<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

if (!Order::hasPendingPayment() || $cart['empty']) {
	header('Location: ' . $domain . 'checkout');
	exit;
}

$pendingData = $_SESSION['pending_order_data'];

if (!is_array($pendingData) || (string) ($pendingData['payment_method'] ?? '') !== 'paypal') {
	header('Location: ' . $domain . 'checkout');
	exit;
}

/** @var PaypalModule|null $paypal */
$paypal = Module::getPaymentModule('paypal');

if (!$paypal || !PaypalModule::isConfigured()) {
	header('Location: ' . $domain . 'checkout');
	exit;
}

$reference = trim((string) ($pendingData['_paypal_reference'] ?? ''));

if ($reference === '') {
	$reference = Order::reserveReference();
	$pendingData['_paypal_reference'] = $reference;
	$_SESSION['pending_order_data'] = $pendingData;
}

$checkoutTotals = Coupon::getCheckoutSummary((float) $cart['total']);
$previewOrder = PaypalModule::buildPreviewOrder($pendingData, $cart);
$paymentError = trim((string) Tools::getValue('fail'));
$approveUrl = '';

$start = PaypalModule::startCheckout($pendingData, $cart);

if ($start['ok']) {
	$approveUrl = (string) ($start['approve_url'] ?? '');

	if ($approveUrl !== '' && !Tools::isSubmit('stay')) {
		header('Location: ' . $approveUrl);
		exit;
	}
} else {
	$paymentError = $start['message'] ?? 'PayPal başlatılamadı';
}

$skipPageRender = true;

$smarty->assign([
	'pageName' => 'paypal-payment',
	'pageTitle' => 'PayPal ile Ödeme',
	'pageDesc' => 'PayPal güvenli ödeme',
	'css' => 'checkout.css',
	'js' => false,
	'paymentError' => $paymentError,
	'approveUrl' => $approveUrl,
	'checkoutTotals' => $checkoutTotals,
	'order' => $previewOrder,
	'paypalCurrency' => PaypalModule::currency(),
	'breadcrumb' => [
		['name' => 'Anasayfa', 'url' => $domain],
		['name' => 'Ödeme', 'url' => $domain . 'checkout'],
		['name' => 'PayPal', 'url' => ''],
	],
]);

$smarty->display(_THEME_BASE_DIR_ . 'header.tpl');
$smarty->display('file:' . dirname(__DIR__) . '/assets/templates/front/payment_page.tpl');
$smarty->display(_THEME_BASE_DIR_ . 'footer.tpl');
