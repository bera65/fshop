<?php
	if (!defined('IN_SCRIPT')) {
		exit;
	}

	// Kart sayfası: sadece giriş yapmış ve checkout'tan yönlendirilmiş müşteri görebilir
	if (!Customer::isLoggedIn()) {
		$_SESSION['auth_redirect'] = $domain . 'checkout';
		header('Location: ' . $domain . 'login');
		exit;
	}

	if (!Order::hasPendingPayment() || $cart['empty']) {
		header('Location: ' . $domain . 'checkout');
		exit;
	}

	/** @var SanalposModule $sanalpos */
	$sanalpos = Module::getPaymentModule('credit_card');

	if (!$sanalpos) {
		header('Location: ' . $domain . 'checkout');
		exit;
	}

	$checkoutTotals = Coupon::getCheckoutSummary((float) $cart['total']);
	$paymentError = '';
	$cardForm = [
		'holder' => '',
		'number' => '',
		'exp_month' => '',
		'exp_year' => '',
	];

	if (Tools::isSubmit('payCard')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($token, $postToken)) {
			$paymentError = 'Geçersiz istek, sayfayı yenileyip tekrar deneyin';
		} else {
			$cardForm = [
				'holder' => trim((string) Tools::getValue('card_holder')),
				'number' => preg_replace('/[^0-9]/', '', (string) Tools::getValue('card_number')),
				'exp_month' => (string) Tools::getValue('exp_month'),
				'exp_year' => (string) Tools::getValue('exp_year'),
			];
			$cvv = (string) Tools::getValue('cvv');

			if ($cardForm['holder'] === '') {
				$paymentError = 'Kart üzerindeki ismi girin';
			} elseif (!SanalposModule::isValidCardNumber($cardForm['number'])) {
				$paymentError = 'Geçerli bir kart numarası girin';
			} elseif (!SanalposModule::isValidExpiry((int) $cardForm['exp_month'], (int) $cardForm['exp_year'])) {
				$paymentError = 'Son kullanma tarihi geçersiz';
			} elseif (!preg_match('/^[0-9]{3,4}$/', $cvv)) {
				$paymentError = 'Geçerli bir CVV girin';
			} else {
				// 1) Bankaya git
				$bank = $sanalpos->chargeCard([
					'holder' => $cardForm['holder'],
					'number' => $cardForm['number'],
					'exp_month' => (int) $cardForm['exp_month'],
					'exp_year' => (int) $cardForm['exp_year'],
					'cvv' => $cvv,
				], (float) $checkoutTotals['total']);

				if (!$bank['success']) {
					// Banka reddetti: sipariş oluşmaz, müşteri tekrar deneyebilir
					$paymentError = $bank['message'];
				} else {
					// 2) Banka onayladı: siparişi ŞİMDİ oluştur
					$result = Order::placePending();

					if ($result['success']) {
						// Ödeme alındığı için sipariş "Hazırlanıyor" durumuna geçer
						Order::updateStatus((int) $result['id_order'], Order::STATUS_PROCESSING);

						header('Location: ' . $domain . 'checkout-success?id=' . (int) $result['id_order']);
						exit;
					}

					// Banka onayladı ama sipariş oluşmadı (ör. stok bitti).
					// GERÇEK ENTEGRASYONDA burada ödeme iadesi (void/refund) yapılmalı!
					$paymentError = $result['message'];
				}
			}
		}
	}

	// Sayfayı tema header/footer'ı ile birlikte kendimiz basıyoruz
	$skipPageRender = true;

	$smarty->assign([
		'pageName' => 'sanalpos-payment',
		'pageTitle' => 'Kart ile Ödeme',
		'pageDesc' => 'Kart ile Ödeme',
		'css' => 'checkout.css',
		'js' => false,
		'paymentError' => $paymentError,
		'cardForm' => $cardForm,
		'checkoutTotals' => $checkoutTotals,
		'breadcrumb' => [
			['name' => 'Anasayfa', 'url' => $domain],
			['name' => 'Ödeme', 'url' => $domain . 'checkout'],
			['name' => 'Kart ile Ödeme', 'url' => ''],
		],
	]);

	$smarty->display(_THEME_BASE_DIR_ . 'header.tpl');
	$smarty->display('file:' . dirname(__DIR__) . '/assets/templates/front/payment_page.tpl');
	$smarty->display(_THEME_BASE_DIR_ . 'footer.tpl');
