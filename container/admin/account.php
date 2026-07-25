<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	$flash = '';
	$flashType = 'info';
	$current = Admin::getCurrent();

	if (!$current) {
		header('Location: ' . Admin::url('login'));
		exit;
	}

	if (Tools::isSubmit('saveProfile')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = Admin::updateProfile(
				(int) $current['id_admin'],
				(string) Tools::getValue('full_name'),
				(string) Tools::getValue('email')
			);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';

			if (!empty($result['success'])) {
				$current = Admin::getCurrent() ?: $current;
				$smarty->assign([
					'adminUser' => $current,
					'adminInitial' => mb_strtoupper(mb_substr($current['full_name'], 0, 1, 'UTF-8')),
				]);
			}
		}
	}

	if (Tools::isSubmit('savePassword')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = Admin::changePassword(
				(int) $current['id_admin'],
				(string) Tools::getValue('current_password'),
				(string) Tools::getValue('new_password'),
				(string) Tools::getValue('confirm_password')
			);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';
		}
	}

	if (Tools::isSubmit('createAdmin')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = Admin::createAdmin(
				(string) Tools::getValue('new_full_name'),
				(string) Tools::getValue('new_email'),
				(string) Tools::getValue('new_password'),
				(string) Tools::getValue('new_confirm_password')
			);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';
		}
	}

	if (Tools::isSubmit('toggleAdmin')) {
		$postToken = (string) Tools::getValue('token');
		$idToggle = (int) Tools::getValue('id_admin');
		$activate = (int) Tools::getValue('activate') === 1;

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = Admin::setActive($idToggle, $activate);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';
		}
	}

	if (Tools::isSubmit('deleteAdmin')) {
		$postToken = (string) Tools::getValue('token');
		$idDelete = (int) Tools::getValue('id_admin');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = Admin::deleteAdmin($idDelete);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';
		}
	}

	$smarty->assign([
		'flash' => $flash,
		'flashType' => $flashType,
		'account' => $current,
		'adminList' => Admin::getList(),
		'demoMode' => Admin::isDemoMode(),
	]);

	AdminPage::add('account', adminT('My Account'));
