<?php
define('IN_SCRIPT', true);
require_once dirname(__DIR__) . '/config/settings.php';
require_once dirname(__DIR__) . '/core/Customer.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['success' => false, 'message' => 'Method not allowed']);
	exit;
}

$token = Tools::getValue('token') ?: ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

if (!hash_equals($_SESSION['csrf_token'] ?? '', (string) $token)) {
	http_response_code(403);
	echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
	exit;
}

$action = Tools::getValue('action');

$captchaForm = $action === 'register' ? 'register' : ($action === 'login' ? 'login' : '');

if ($captchaForm !== '') {
	$captchaError = fshop_validate_captcha($captchaForm);

	if ($captchaError !== '') {
		http_response_code(403);
		echo json_encode(['success' => false, 'message' => $captchaError]);
		exit;
	}
}

switch ($action) {
	case 'login':
		$remember = Tools::getValue('remember') !== '0';
		$login = (string) Tools::getValue('login');
		if ($login === '') {
			$login = (string) Tools::getValue('phone');
		}
		if ($login === '') {
			$login = (string) Tools::getValue('email');
		}
		$result = Customer::login($login, (string) Tools::getValue('password'), $remember);
		if ($result['success'] && !empty($_SESSION['auth_redirect'])) {
			$result['redirect'] = $_SESSION['auth_redirect'];
			unset($_SESSION['auth_redirect']);
		}
		echo json_encode($result);
		break;

	case 'register':
		$result = Customer::register(
			(string) Tools::getValue('full_name'),
			(string) Tools::getValue('phone'),
			(string) Tools::getValue('password'),
			(string) Tools::getValue('email')
		);
		if ($result['success'] && !empty($_SESSION['auth_redirect'])) {
			$result['redirect'] = $_SESSION['auth_redirect'];
			unset($_SESSION['auth_redirect']);
		}
		echo json_encode($result);
		break;

	case 'logout':
		Customer::logout();
		echo json_encode([
			'success' => true,
			'message' => 'Çıkış yapıldı',
			'user' => null,
		]);
		break;

	case 'me':
		echo json_encode([
			'success' => true,
			'message' => '',
			'user' => Customer::publicUser(Customer::getCurrent()),
		]);
		break;

	default:
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
}
