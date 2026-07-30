<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	define('IN_SCRIPT', true);
}

require_once dirname(__DIR__, 3) . '/config/settings.php';
require_once dirname(__DIR__) . '/kuveytturk.php';

$baseUrl = KuveytturkModule::getBaseUrl();

function parseKuveytturkResponseXml(string $raw): ?SimpleXMLElement
{
	$xmlStr = $raw;

	for ($i = 0; $i < 3; $i++) {
		$xmlStr = trim($xmlStr);
		if (strpos($xmlStr, '<') === 0 || strpos($xmlStr, '<?xml') === 0) {
			break;
		}
		$xmlStr = urldecode($xmlStr);
	}

	$bom = pack('H*', 'EFBBBF');
	$xmlStr = preg_replace('/^' . $bom . '/', '', $xmlStr);
	$xmlStr = trim($xmlStr);

	$xml = @simplexml_load_string($xmlStr);

	if (!$xml) {
		$xmlStr2 = html_entity_decode($xmlStr, ENT_QUOTES | ENT_XML1, 'UTF-8');
		$xml = @simplexml_load_string($xmlStr2);
	}

	return $xml ?: null;
}

$authResponse = (string) ($_POST['AuthenticationResponse'] ?? $_REQUEST['AuthenticationResponse'] ?? '');
$helper = new KuveytTurkHelper();

if (empty($authResponse)) {
	$_SESSION['kuveytturk_payment_error'] = 'Banka 3D yanıtı alınamadı veya işlem iptal edildi.';
	header('Location: ' . $baseUrl . 'kuveytturk-payment?fail=1');
	exit;
}

$xml = parseKuveytturkResponseXml($authResponse);

if (!$xml) {
	$_SESSION['kuveytturk_payment_error'] = 'Banka 3D yanıtı (XML) ayrıştırılamadı.';
	header('Location: ' . $baseUrl . 'kuveytturk-payment?fail=1');
	exit;
}

$merchantOrderId = $helper->getXmlValue($xml, 'MerchantOrderId');
$amount = $helper->getXmlValue($xml, 'Amount');
$md = $helper->getXmlValue($xml, 'MD');

if (empty($merchantOrderId) || empty($amount) || empty($md)) {
	$_SESSION['kuveytturk_payment_error'] = 'Banka yanıtında eksik parametreler tespit edildi.';
	header('Location: ' . $baseUrl . 'kuveytturk-payment?fail=1');
	exit;
}

$pendingCheckout = KuveytturkModule::getPendingCheckout($merchantOrderId);

if (!$pendingCheckout || empty($pendingCheckout['checkout_data'])) {
	$_SESSION['kuveytturk_payment_error'] = 'Ödeme oturum süresi doldu veya sepet bulunamadı.';
	header('Location: ' . $baseUrl . 'checkout');
	exit;
}

$helper->updateTransactionLog($merchantOrderId, [
	'md' => mb_substr($md, 0, 250),
	'status' => '3d_complete',
]);

$provisionData = [
	'MerchantId' => Settings::get('KUVEYTTURK_MERCHANT_ID'),
	'CustomerId' => Settings::get('KUVEYTTURK_CUSTOMER_ID'),
	'UserName' => Settings::get('KUVEYTTURK_USERNAME'),
	'Password' => Settings::get('KUVEYTTURK_PASSWORD'),
	'MerchantOrderId' => $merchantOrderId,
	'Amount' => $amount,
	'MD' => $md,
	'IdentityTaxNumber' => '11111111111',
	'Description' => 'FShop Order ' . $merchantOrderId,
];

$result = $helper->sendProvisionRequest($provisionData);

$responseCode = (string) ($result['response_code'] ?? '');
$responseMsg = (string) ($result['response_message'] ?? '');

$helper->updateTransactionLog($merchantOrderId, [
	'response_data' => mb_substr((string) ($result['response'] ?? ''), 0, 4000),
	'response_code' => $responseCode,
	'response_message' => $responseMsg,
	'status' => (!empty($result['success']) && $responseCode === '00') ? 'completed' : 'failed',
]);

if (!empty($result['success']) && $responseCode === '00') {
	$checkoutData = $pendingCheckout['checkout_data'];
	$cartSummary = $pendingCheckout['cart_summary'];

	$checkoutData['_payment_done'] = true;
	$checkoutData['_payment_reference'] = $merchantOrderId;
	$checkoutData['payment_method'] = 'kuveytturk';
	$checkoutData['_cart_snapshot'] = $cartSummary;

	if (class_exists('Order')) {
		$createResult = Order::place($checkoutData);

		if (!empty($createResult['success']) && !empty($createResult['id_order'])) {
			$idOrder = (int) $createResult['id_order'];

			$helper->updateTransactionLog($merchantOrderId, ['id_order' => $idOrder]);
			KuveytturkModule::removePendingCheckout($merchantOrderId);

			unset($_SESSION['pending_order_data']);
			if (class_exists('Cart')) {
				Cart::clear();
			}

			header('Location: ' . $baseUrl . 'checkout-success?id=' . $idOrder . '&ref=' . rawurlencode($merchantOrderId));
			exit;
		}
	}

	$_SESSION['kuveytturk_payment_error'] = 'Ödeme alındı ancak sipariş kaydı oluşturulamadı. Lütfen müşteri hizmetleri ile iletişime geçin.';
	header('Location: ' . $baseUrl . 'checkout');
	exit;
}

$errorMessage = !empty($responseMsg) ? $responseMsg : 'KuveytTürk provizyon işlemi onaylanmadı.';
$_SESSION['kuveytturk_payment_error'] = 'Banka Hatası: ' . $errorMessage;
header('Location: ' . $baseUrl . 'kuveytturk-payment?fail=1');
exit;
