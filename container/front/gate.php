<?php
	if (!defined('IN_SCRIPT')) {
		exit;
	}

	if (Customer::isLoggedIn() || Settings::get('SITE_VISIBILITY') !== 'members_only') {
		header('Location: ' . $domain);
		exit;
	}

	$css = false;
	$pageTitle = trim((string) Settings::get('GATE_TITLE'));
	if ($pageTitle === '') {
		$pageTitle = (string) Settings::get('SITE_NAME');
	}

	$gateFeaturesStr = (string) Settings::get('GATE_FEATURES');
	$gateFeatures = [];
	if ($gateFeaturesStr !== '') {
		$gateFeatures = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $gateFeaturesStr) ?: [])));
	}

	$gateAddress = trim((string) Settings::get('CONTACT_ADDRESS'));
	$gateCity = trim((string) Settings::get('CONTACT_CITY'));
	$gateCountry = trim((string) Settings::get('CONTACT_COUNTRY'));
	$gatePhone = trim((string) Settings::get('CONTACT_PHONE'));
	$gatePhoneTel = trim((string) Settings::get('CONTACT_PHONE_TEL'));

	$addressLine = $gateAddress;
	$cityLine = trim($gateCity . ($gateCity !== '' && $gateCountry !== '' ? ' / ' : '') . $gateCountry);
	if ($cityLine !== '') {
		$addressLine = trim($addressLine . ($addressLine !== '' ? ' ' : '') . $cityLine);
	}

	if ($gatePhoneTel === '') {
		$gatePhoneTel = preg_replace('/\D+/', '', $gatePhone) ?: '';
	}

	$smarty->assign([
		'gateTitle' => $pageTitle,
		'gateFeatures' => $gateFeatures,
		'gateAddress' => $addressLine,
		'gatePhone' => $gatePhone,
		'gatePhoneTel' => $gatePhoneTel,
		'gateBgExists' => is_file(dirname(__DIR__, 2) . '/img/gate-bg.jpg'),
		'authMode' => 'gate',
	]);
