<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

global $domain;

$code = trim((string) Tools::getValue('code'));
$state = trim((string) Tools::getValue('state'));
$expectedState = (string) ($_SESSION['google_oauth_state'] ?? '');

unset($_SESSION['google_oauth_state']);

if ($code === '' || $state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
	$_SESSION['auth_flash_error'] = translate('Google login failed');
	header('Location: ' . $domain . 'login');
	exit;
}

$exchange = GoogleLoginModule::exchangeCode($code);

if (empty($exchange['success'])) {
	$_SESSION['auth_flash_error'] = translate('Google login failed');
	header('Location: ' . $domain . 'login');
	exit;
}

$result = Customer::authWithGoogle(
	(string) $exchange['google_id'],
	(string) $exchange['email'],
	(string) $exchange['name']
);

if (empty($result['success'])) {
	$_SESSION['auth_flash_error'] = (string) ($result['message'] ?? translate('Google login failed'));
	header('Location: ' . $domain . 'login');
	exit;
}

if (!empty($result['pending']) || !Customer::isLoggedIn()) {
	$_SESSION['auth_flash_notice'] = (string) ($result['message'] ?? translate('Your registration was received. You can sign in after admin approval.'));
	header('Location: ' . $domain . 'register');
	exit;
}

$redirect = !empty($_SESSION['auth_redirect']) ? (string) $_SESSION['auth_redirect'] : $domain . 'my-account';
unset($_SESSION['auth_redirect']);

header('Location: ' . $redirect);
exit;
