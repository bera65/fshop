<?php

	if (!defined('IN_SCRIPT')) {

		exit;

	}



	if (Customer::isLoggedIn()) {

		$redirect = !empty($_SESSION['auth_redirect']) ? $_SESSION['auth_redirect'] : $domain . 'my-account';

		unset($_SESSION['auth_redirect']);

		header('Location: ' . $redirect);

		exit;

	}



	$css = 'pages.css';

	$authNotice = '';

	$authError = '';

	$formData = [

		'login' => '',

	];

	if (!empty($_SESSION['auth_flash_notice'])) {
		$authNotice = (string) $_SESSION['auth_flash_notice'];
		unset($_SESSION['auth_flash_notice']);
	}

	if (!empty($_SESSION['auth_flash_error'])) {
		$authError = (string) $_SESSION['auth_flash_error'];
		unset($_SESSION['auth_flash_error']);
	}



	if (!empty($_SESSION['auth_redirect'])) {

		$target = (string) $_SESSION['auth_redirect'];



		if (strpos($target, 'checkout') !== false) {

			$authNotice = translate('Checkout login notice');

		}

	}

	if (Tools::getValue('google_error') === 'config') {
		$authError = translate('Google login not configured');
	} elseif (Tools::getValue('google_error') === '1') {
		$authError = translate('Google login failed');
	}



	if (Tools::isSubmit('loginUser')) {

		$postToken = (string) Tools::getValue('token');



		$loginValue = (string) Tools::getValue('login');
		if ($loginValue === '') {
			$loginValue = (string) Tools::getValue('phone');
		}

		$formData = [

			'login' => $loginValue,

		];



		if (!hash_equals($token, $postToken)) {

			$authError = translate('Invalid request, please refresh and try again');

		} else {

			$captchaError = fshop_validate_captcha('login');

			if ($captchaError !== '') {

				$authError = $captchaError;

			} else {

			$remember = Tools::getValue('remember') !== '0';

			$result = Customer::login(

				$formData['login'],

				(string) Tools::getValue('password'),

				$remember

			);



			if ($result['success']) {

				$redirect = !empty($_SESSION['auth_redirect']) ? $_SESSION['auth_redirect'] : $domain . 'my-account';

				unset($_SESSION['auth_redirect']);

				header('Location: ' . $redirect);

				exit;

			}



			$authError = $result['message'];

			}

		}

	}



	$pageTitle = translate('Login page title');

	$pageDesc = translate('Login page description');



	$smarty->assign([

		'authNotice' => $authNotice,

		'authError' => $authError,

		'formData' => $formData,

		'authMode' => 'login',

		'authAsideExists' => is_file(dirname(__DIR__, 2) . '/img/auth-aside.jpg'),

		'breadcrumb' => [

			['name' => translate('Home Page'), 'url' => $domain],

			['name' => translate('Sign In'), 'url' => ''],

		],

	]);

