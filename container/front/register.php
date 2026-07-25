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

		'full_name' => '',

		'phone' => '',

		'email' => '',

	];



	if (!empty($_SESSION['auth_redirect'])) {

		$target = (string) $_SESSION['auth_redirect'];



		if (strpos($target, 'checkout') !== false) {

			$authNotice = translate('Checkout login notice');

		}

	}



	if (Tools::isSubmit('registerUser')) {

		$postToken = (string) Tools::getValue('token');



		$formData = [

			'full_name' => (string) Tools::getValue('full_name'),

			'phone' => (string) Tools::getValue('phone'),

			'email' => (string) Tools::getValue('email'),

		];



		if (!hash_equals($token, $postToken)) {

			$authError = translate('Invalid request, please refresh and try again');

		} else {

			$captchaError = fshop_validate_captcha('register');

			if ($captchaError !== '') {

				$authError = $captchaError;

			} else {

			$password = (string) Tools::getValue('password');

			$password2 = (string) Tools::getValue('password2');



			if ($password !== $password2) {

				$authError = translate('Passwords do not match');

			} else {

				$result = Customer::register(

					$formData['full_name'],

					$formData['phone'],

					$password,

					$formData['email']

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

	}



	$pageTitle = translate('Register page title');

	$pageDesc = translate('Register page description');



	$smarty->assign([

		'authNotice' => $authNotice,

		'authError' => $authError,

		'formData' => $formData,

		'authMode' => 'register',

		'breadcrumb' => [

			['name' => translate('Home Page'), 'url' => $domain],

			['name' => translate('Sign Up'), 'url' => ''],

		],

	]);

