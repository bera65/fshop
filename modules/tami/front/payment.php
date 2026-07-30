<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

if (!Order::hasPendingPayment() || $cart['empty']) {
	header('Location: ' . $domain . 'checkout');
	exit;
}

$pendingData = $_SESSION['pending_order_data'];

if (!is_array($pendingData) || (string) ($pendingData['payment_method'] ?? '') !== 'tami') {
	header('Location: ' . $domain . 'checkout');
	exit;
}

/** @var TamiModule|null $tami */
$tami = Module::getPaymentModule('tami');

if (!$tami || !TamiModule::isConfigured()) {
	header('Location: ' . $domain . 'checkout');
	exit;
}

$reference = trim((string) ($pendingData['_tami_reference'] ?? ''));

if ($reference === '') {
	$reference = Order::reserveReference();
	$pendingData['_tami_reference'] = $reference;
	$_SESSION['pending_order_data'] = $pendingData;
}

$checkoutTotals = Coupon::getCheckoutSummary((float) $cart['total']);
$previewOrder = TamiModule::buildPreviewOrder($pendingData, $cart);
$paymentError = '';
$cardForm = [
	'holder' => '',
	'number' => '',
	'exp_month' => '',
	'exp_year' => '',
	'installment' => '1',
];
$maxInstallment = max(1, (int) (Settings::get('TAMI_MAX_INSTALLMENT') ?: 1));

if (Tools::isSubmit('payTami')) {
	$postToken = (string) Tools::getValue('token');

	if (!hash_equals($token, $postToken)) {
		$paymentError = 'Geçersiz istek, sayfayı yenileyip tekrar deneyin';
	} else {
		$cardForm = [
			'holder' => trim((string) Tools::getValue('card_holder')),
			'number' => preg_replace('/[^0-9]/', '', (string) Tools::getValue('card_number')),
			'exp_month' => (string) Tools::getValue('exp_month'),
			'exp_year' => (string) Tools::getValue('exp_year'),
			'installment' => (string) max(1, (int) Tools::getValue('installment', 1)),
		];
		$cvv = (string) Tools::getValue('cvv');

		if ($cardForm['holder'] === '') {
			$paymentError = 'Kart üzerindeki ismi girin';
		} elseif (!TamiModule::isValidCardNumber($cardForm['number'])) {
			$paymentError = 'Geçerli bir kart numarası girin';
		} elseif (!TamiModule::isValidExpiry((int) $cardForm['exp_month'], (int) $cardForm['exp_year'])) {
			$paymentError = 'Son kullanma tarihi geçersiz';
		} elseif (!preg_match('/^[0-9]{3,4}$/', $cvv) && $cvv !== '') {
			// Tami test kartlarında CVV boş olabilir
			$paymentError = 'Geçerli bir CVV girin';
		} else {
			$orderId = TamiModule::makeOrderId($reference);
			$callbackUrl = rtrim((string) $domain, '/') . '/api/module.php?m=tami&action=callback';

			TamiModule::persistPendingCheckout(
				$orderId,
				$reference,
				$pendingData,
				$cart,
				(float) $checkoutTotals['total']
			);

			$_SESSION['tami_last_order_id'] = $orderId;

			$start = TamiModule::startPayment(
				[
					'holder' => $cardForm['holder'],
					'number' => $cardForm['number'],
					'exp_month' => (int) $cardForm['exp_month'],
					'exp_year' => (int) $cardForm['exp_year'],
					'cvv' => $cvv,
					'installment' => (int) $cardForm['installment'],
				],
				$previewOrder,
				$orderId,
				$callbackUrl
			);

			if (!$start['ok']) {
				$paymentError = $start['message'] ?? 'Ödeme başlatılamadı';
			} else {
				// 3D HTML bankaya yönlendirir (auto-submit form)
				header('Content-Type: text/html; charset=utf-8');
				echo $start['html'];
				exit;
			}
		}
	}
}

$failMsg = trim((string) Tools::getValue('fail'));

if ($failMsg !== '') {
	$paymentError = $failMsg;
}

$skipPageRender = true;

$smarty->assign([
	'pageName' => 'tami-payment',
	'pageTitle' => 'Kart ile Ödeme',
	'pageDesc' => 'Tami ile güvenli 3D ödeme',
	'css' => 'checkout.css',
	'js' => false,
	'paymentError' => $paymentError,
	'cardForm' => $cardForm,
	'checkoutTotals' => $checkoutTotals,
	'tamiMaxInstallment' => $maxInstallment,
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
