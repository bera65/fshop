<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

if (!Order::hasPendingPayment() || $cart['empty']) {
	header('Location: ' . $domain . 'checkout');
	exit;
}

$pendingData = $_SESSION['pending_order_data'];

if (!is_array($pendingData) || (string) ($pendingData['payment_method'] ?? '') !== 'iyzico') {
	header('Location: ' . $domain . 'checkout');
	exit;
}

/** @var IyzicoModule|null $iyzico */
$iyzico = Module::getPaymentModule('iyzico');

if (!$iyzico || !IyzicoModule::isConfigured()) {
	header('Location: ' . $domain . 'checkout');
	exit;
}

$reference = trim((string) ($pendingData['_iyzico_reference'] ?? ''));

if ($reference === '') {
	$reference = Order::reserveReference();
	$pendingData['_iyzico_reference'] = $reference;
	$_SESSION['pending_order_data'] = $pendingData;
}

IyzicoModule::persistPendingCheckout($reference, $pendingData, $cart);

$previewOrder = IyzicoModule::buildPreviewOrder($pendingData, $cart);
$paymentError = Tools::getValue('fail') ? 'Ödeme tamamlanamadı. Lütfen tekrar deneyin.' : '';
$checkoutFormContent = '';
$paymentPageUrl = '';

if ($paymentError === '') {
	$formResult = $iyzico->initializeCheckoutForm($previewOrder, $pendingData, $cart);

	if (!empty($formResult['success'])) {
		$checkoutFormContent = (string) ($formResult['checkoutFormContent'] ?? '');
		$paymentPageUrl = (string) ($formResult['paymentPageUrl'] ?? '');
	} else {
		$paymentError = (string) ($formResult['message'] ?? 'iyzico ödeme ekranı yüklenemedi');
	}
}

$skipPageRender = true;

$smarty->assign([
	'pageName' => 'iyzico-payment',
	'pageTitle' => 'Kart ile Ödeme',
	'pageDesc' => 'iyzico ile güvenli ödeme',
	'css' => 'checkout.css',
	'js' => false,
	'paymentError' => $paymentError,
	'checkoutFormContent' => $checkoutFormContent,
	'paymentPageUrl' => $paymentPageUrl,
	'formClass' => IyzicoModule::getFormClass(),
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
