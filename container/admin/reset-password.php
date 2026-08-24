<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	$error = '';
	$success = '';
	$resetToken = trim((string) ($_GET['rt'] ?? Tools::getValue('reset_token')));

	if ($resetToken === '' || !Admin::findValidPasswordReset($resetToken)) {
		$error = adminT('Invalid or expired reset link');
		$resetToken = '';
	}

	if ($error === '' && Tools::isSubmit('adminResetPassword')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$error = adminT('Invalid request');
		} else {
			$result = Admin::resetPassword(
				$resetToken,
				(string) Tools::getValue('password'),
				(string) Tools::getValue('password2')
			);

			if ($result['success']) {
				$success = $result['message'];
				$resetToken = '';
			} else {
				$error = $result['message'];
			}
		}
	}

	$smarty->assign([
		'loginError' => $error,
		'loginSuccess' => $success,
		'resetToken' => $resetToken,
	]);

	AdminPage::add('reset-password', 'Reset Password', true);
