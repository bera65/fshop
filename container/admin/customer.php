<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	$idUser = (int) Tools::getValue('id');
	$customer = Customer::getByIdAdmin($idUser);
	$flash = '';
	$flashType = 'info';

	if (!$customer) {
		http_response_code(404);
		AdminPage::add('404', 'Customer not found');
		return;
	}

	if (Tools::isSubmit('toggleActive')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = Customer::setActive($idUser, !(int) $customer['active']);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';

			if (!empty($result['success'])) {
				$customer = Customer::getByIdAdmin($idUser);
			}
		}
	}

	if (Tools::isSubmit('saveCustomer')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = Customer::updateByAdmin(
				$idUser,
				(string) Tools::getValue('user_full_name'),
				(string) Tools::getValue('phone'),
				(string) Tools::getValue('email'),
				(int) Tools::getValue('id_group')
			);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';
			$customer = Customer::getByIdAdmin($idUser) ?: $customer;

			if (empty($result['success'])) {
				$customer['user_full_name'] = (string) Tools::getValue('user_full_name');
				$customer['phone'] = (string) Tools::getValue('phone');
				$customer['email'] = (string) Tools::getValue('email');
			}
		}
	}

	if (Tools::isSubmit('saveCustomerPassword')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = Customer::setPasswordByAdmin(
				$idUser,
				(string) Tools::getValue('password'),
				(string) Tools::getValue('password2')
			);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';
		}
	}

	$contactRedirectUrl = '';

	if (Tools::isSubmit('sendCustomerContact')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = CustomerContact::send(
				$customer,
				(string) Tools::getValue('contact_channel'),
				(string) Tools::getValue('contact_message')
			);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';

			if (!empty($result['success']) && !empty($result['mode']) && $result['mode'] === 'redirect' && !empty($result['url'])) {
				$contactRedirectUrl = (string) $result['url'];
			}
		}
	}

	$smarty->assign([
		'customer' => $customer,
		'flash' => $flash,
		'flashType' => $flashType,
		'customerGroups' => class_exists('CustomerGroup', false) ? CustomerGroup::getActiveOptions() : [],
		'customerContactWapioReady' => CustomerContact::isWapioReady(),
		'customerHasPhone' => trim((string) ($customer['phone'] ?? '')) !== '',
		'customerHasEmail' => trim((string) ($customer['email'] ?? '')) !== '' && filter_var((string) $customer['email'], FILTER_VALIDATE_EMAIL),
		'contactRedirectUrl' => $contactRedirectUrl,
	]);

	AdminPage::add('customer', $customer['user_full_name']);
