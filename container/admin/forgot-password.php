<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	$error = '';
	$success = '';
	$formEmail = '';

	if (Tools::isSubmit('adminForgotPassword')) {
		$postToken = (string) Tools::getValue('token');
		$formEmail = trim((string) Tools::getValue('email'));

		if (!hash_equals($adminToken, $postToken)) {
			$error = adminT('Invalid request');
		} else {
			$result = Admin::requestPasswordReset($formEmail);

			if ($result['success']) {
				$success = $result['message'];
				$formEmail = '';
			} else {
				$error = $result['message'];
			}
		}
	}

	$smarty->assign([
		'loginError' => $error,
		'loginSuccess' => $success,
		'formEmail' => $formEmail,
	]);

	AdminPage::add('forgot-password', 'Forgot password', true);
