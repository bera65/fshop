<?php
	if (!defined('IN_SCRIPT')) {
		exit;
	}

	$css = 'pages.css';
	$idOrder = (int) Tools::getValue('id');
	if ($idOrder <= 0) {
		$idOrder = (int) Tools::getValue('id_order');
	}
	if ($idOrder <= 0) {
		$idOrder = (int) Tools::getValue('order');
	}

	$reference = trim((string) Tools::getValue('ref'));

	// PayTR / KuveytTürk gibi ödemelerde önce callback siparişi oluşturur; ref ile kısa süre bekleyebiliriz
	if ($idOrder <= 0 && $reference !== '') {
		for ($attempt = 0; $attempt < 8; $attempt++) {
			$idOrder = (int) DB::getValue('SELECT id_order FROM orders WHERE reference = ? LIMIT 1', [$reference]);

			if ($idOrder > 0) {
				break;
			}

			usleep(500000);
		}
	}

	// Payment return: grant only when this browser session started the checkout.
	// Knowing a public order reference is not enough (IDOR).
	if ($idOrder > 0) {
		$orderRef = (string) DB::getValue('SELECT reference FROM orders WHERE id_order = ? LIMIT 1', [$idOrder]);
		$pending = $_SESSION['pending_order_data'] ?? null;
		$allowGrant = false;

		if (is_array($pending) && $orderRef !== '') {
			foreach ($pending as $key => $value) {
				if (!is_scalar($value)) {
					continue;
				}

				$keyName = strtolower((string) $key);

				if ($keyName !== 'reference' && substr($keyName, -10) !== '_reference') {
					continue;
				}

				$candidate = trim((string) $value);

				if ($candidate !== '' && hash_equals($orderRef, $candidate)) {
					$allowGrant = true;
					break;
				}
			}
		}

		if ($allowGrant) {
			Order::grantGuestOrderAccess($idOrder);
		}
	}

	$order = Order::getByIdForViewer($idOrder);

	if ($order) {
		Order::clearPendingPayment();
		Cart::clear();
	}

	if (!$order) {
		http_response_code(404);
		$skipPageRender = true;
		$page->add('404', translate('Page Not Found'));
		return;
	}

	$idOrder = (int) $order['id_order'];
	$target = $domain . 'my-account?order=' . $idOrder;

	if (Customer::isLoggedIn()) {
		header('Location: ' . $target);
		exit;
	}

	$_SESSION['auth_redirect'] = $target;
	header('Location: ' . $domain . 'login');
	exit;
