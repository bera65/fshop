<?php
	if (!defined('IN_SCRIPT')) {
		exit;
	}

	$pageTitle = translate('Contact Us');
	$pageDesc = translate('Get in Touch');
	$contactSeo = Seo::resolvePage('contact', $pageTitle, $pageDesc);
	$pageTitle = $contactSeo['title'];
	$pageDesc = $contactSeo['description'];
	$contactSuccess = '';
	$contactError = '';
	$formData = [
		'full_name' => Customer::isLoggedIn() ? ($customer['user_full_name'] ?? '') : '',
		'email' => '',
		'phone' => Customer::isLoggedIn() ? ($customer['phone'] ?? '') : '',
		'subject' => '',
		'message' => '',
	];

	if (Tools::isSubmit('sendContact')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($token, $postToken)) {
			$contactError = translate('Invalid request, please refresh and try again');
		} else {
			$captchaError = fshop_validate_captcha('contact');

			if ($captchaError !== '') {
				$contactError = $captchaError;
			} else {
			$formData = [
				'full_name' => (string) Tools::getValue('full_name'),
				'email' => (string) Tools::getValue('email'),
				'phone' => (string) Tools::getValue('phone'),
				'subject' => (string) Tools::getValue('subject'),
				'message' => (string) Tools::getValue('message'),
				'website' => (string) Tools::getValue('website'),
			];

			$result = Contact::submit($formData);

			if ($result['success']) {
				$contactSuccess = $result['message'];
				$formData = [
					'full_name' => Customer::isLoggedIn() ? ($customer['user_full_name'] ?? '') : '',
					'email' => '',
					'phone' => Customer::isLoggedIn() ? ($customer['phone'] ?? '') : '',
					'subject' => '',
					'message' => '',
				];
			} else {
				$contactError = $result['message'];
			}
			}
		}
	}

	$smarty->assign([
		'contactSuccess' => $contactSuccess,
		'contactError' => $contactError,
		'formData' => $formData,
		'contactEmail' => Settings::get('CONTACT_EMAIL'),
		'contactPhone' => Settings::get('CONTACT_PHONE'),
		'contactPhoneTel' => Settings::get('CONTACT_PHONE_TEL'),
		'contactAddress' => Settings::get('CONTACT_ADDRESS'),
		'contactCity' => Settings::get('CONTACT_CITY'),
		'contactCountry' => Settings::get('CONTACT_COUNTRY'),
		'openHour' => Settings::get('OPEN_HOUR') ?: '09:00',
		'closeHour' => Settings::get('CLOSE_HOUR') ?: '18:00',
		'breadcrumb' => [
			['name' => translate('Home Page'), 'url' => $domain],
			['name' => translate('Contact Us'), 'url' => ''],
		],
	]);
