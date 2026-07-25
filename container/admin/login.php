<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	$error = '';

	if (Tools::isSubmit('adminLogin')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$error = adminT('Invalid request');
		} else {
			$captchaError = fshop_validate_captcha('admin');

			if ($captchaError !== '') {
				$error = $captchaError;
			} else {
			$result = Admin::login(
				(string) Tools::getValue('email'),
				(string) Tools::getValue('password')
			);

			if ($result['success']) {
				header('Location: ' . Admin::url());
				exit;
			}

			$error = $result['message'];
			}
		}
	}

	$smarty->assign('loginError', $error);

	if (Module::isEnabled('recaptcha')) {
		$recaptchaFile = Module::path('recaptcha') . '/recaptcha.php';

		if (is_file($recaptchaFile)) {
			require_once $recaptchaFile;
			RecaptchaModule::assignAdminLoginPage($smarty);
		}
	}

	AdminPage::add('login', 'Admin login', true);
