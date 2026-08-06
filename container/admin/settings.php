<?php

	if (!defined('IN_ADMIN')) {

		exit;

	}



	$flash = '';

	$flashType = 'info';

	$editableKeys = Settings::getEditableKeys();

	$settingsTab = (string) Tools::getValue('tab', 'general');

	$allowedTabs = ['general', 'contact', 'email', 'orders', 'returns'];



	if (!in_array($settingsTab, $allowedTabs, true)) {

		$settingsTab = 'general';

	}



	$values = Settings::getAllForAdmin();

	$mailHeaderStored = trim((string) ($values['MAIL_HEADER'] ?? ''));

	$mailFooterStored = trim((string) ($values['MAIL_FOOTER'] ?? ''));

	$values['MAIL_HEADER'] = $mailHeaderStored !== '' ? $values['MAIL_HEADER'] : Mail::getDefaultEmailHeader();

	$values['MAIL_FOOTER'] = $mailFooterStored !== '' ? $values['MAIL_FOOTER'] : Mail::getDefaultEmailFooter();

	$maintenanceStored = trim((string) ($values['SHOP_MAINTENANCE_MESSAGE'] ?? ''));

	$values['SHOP_MAINTENANCE_MESSAGE'] = $maintenanceStored !== ''

		? $values['SHOP_MAINTENANCE_MESSAGE']

		: StoreStatus::getDefaultMaintenanceMessage();

	$mailConfigured = Mail::isConfigured();

	$usesSmtp = Mail::usesSmtp();



	if (Tools::isSubmit('saveSettings')) {

		$postToken = (string) Tools::getValue('token');

		$settingsTab = (string) Tools::getValue('settings_tab', $settingsTab);



		if (!in_array($settingsTab, $allowedTabs, true)) {

			$settingsTab = 'general';

		}



		if (!hash_equals($adminToken, $postToken)) {

			$flash = adminT('Invalid request');

			$flashType = 'danger';

		} else {

			$ok = true;



			foreach (array_keys($editableKeys) as $key) {

				if ($key === 'SMTP_PASS' && trim((string) Tools::getValue('SMTP_PASS')) === '') {

					continue;

				}



				$fieldType = (string) ($editableKeys[$key]['type'] ?? 'text');



				if ($fieldType === 'html') {

					$value = (string) Tools::getValue($key);

				} elseif ($fieldType === 'checkbox') {

					$value = Tools::getValue($key) ? '1' : '0';

				} else {

					$value = trim((string) Tools::getValue($key));

				}



				if ($key === 'ORDER_REF_PREFIX') {

					$value = Order::sanitizeReferencePrefix($value);

				} elseif ($key === 'ORDER_REF_SUFFIX_MODE') {

					$modes = array_keys(Order::getReferenceSuffixModes());

					if (!in_array(strtolower($value), $modes, true)) {

						$value = 'sequential';

					}

				} elseif ($key === 'ORDER_REF_PAD') {

					$value = (string) max(3, min(10, (int) $value ?: 5));

				} elseif ($key === 'GIFT_WRAP_FEE') {

					$value = (string) max(0, round((float) str_replace(',', '.', $value), 2));

				} elseif ($key === 'MAIL_HEADER' && $value === Mail::getDefaultEmailHeader()) {

					$value = '';

				} elseif ($key === 'MAIL_FOOTER' && $value === Mail::getDefaultEmailFooter()) {

					$value = '';

				} elseif ($key === 'SHOP_MAINTENANCE_MESSAGE' && $value === StoreStatus::getDefaultMaintenanceMessage()) {

					$value = '';

				}



				if (!Settings::set($key, $value)) {

					$ok = false;

				}

			}



			if ($ok) {

				header('Location: ' . Admin::url('settings') . '?tab=' . rawurlencode($settingsTab) . '&saved=1');

				exit;

			}



			$flash = adminT('Some settings could not be saved');

			$flashType = 'danger';

			$values = Settings::getAllForAdmin();

			$mailHeaderStored = trim((string) ($values['MAIL_HEADER'] ?? ''));

			$mailFooterStored = trim((string) ($values['MAIL_FOOTER'] ?? ''));

			$values['MAIL_HEADER'] = $mailHeaderStored !== '' ? $values['MAIL_HEADER'] : Mail::getDefaultEmailHeader();

			$values['MAIL_FOOTER'] = $mailFooterStored !== '' ? $values['MAIL_FOOTER'] : Mail::getDefaultEmailFooter();

			$maintenanceStored = trim((string) ($values['SHOP_MAINTENANCE_MESSAGE'] ?? ''));

			$values['SHOP_MAINTENANCE_MESSAGE'] = $maintenanceStored !== ''

				? $values['SHOP_MAINTENANCE_MESSAGE']

				: StoreStatus::getDefaultMaintenanceMessage();

			$mailConfigured = Mail::isConfigured();

			$usesSmtp = Mail::usesSmtp();

		}

	}



	if (Tools::getValue('saved') === '1' && $flash === '') {

		$flash = adminT('Settings saved');

		$flashType = 'success';

	}



	if (Tools::isSubmit('testMail')) {

		$postToken = (string) Tools::getValue('token');



		if (!hash_equals($adminToken, $postToken)) {

			$flash = adminT('Invalid request');

			$flashType = 'danger';

		} elseif (!$mailConfigured) {

			$flash = $usesSmtp

				? adminT('Complete SMTP settings. Enter server, user and password.')

				: adminT('Enter contact or sender email for PHP mail().');

			$flashType = 'danger';

		} else {

			$testTo = trim((string) Tools::getValue('test_email'));

			if ($testTo === '') {

				$testTo = Settings::get('SMTP_FROM_EMAIL') ?: Settings::get('SMTP_USER') ?: Settings::get('CONTACT_EMAIL');

			}



			$driverLabel = $usesSmtp ? 'SMTP' : 'PHP mail()';



			if ($testTo === '' || !filter_var($testTo, FILTER_VALIDATE_EMAIL)) {

				$flash = adminT('Enter a valid email address for the test');

				$flashType = 'danger';

			} elseif (Mail::send($testTo, 'Test Mail - ' . Settings::get('SITE_NAME'), '<p>Bu bir test e-postasıdır. <strong>' . $driverLabel . '</strong> ile gönderildi.</p>')) {

				$flash = adminT('Test email sent') . ' (' . $driverLabel . '): ' . $testTo;

				$flashType = 'success';

			} else {

				$error = Mail::getLastError();

				$flash = adminT('Test email could not be sent') . ' (' . $driverLabel . ')' . ($error !== '' ? ': ' . $error : '');

				$flashType = 'danger';

			}

		}

	}



	if (Tools::isSubmit('saveWebApi') || Tools::isSubmit('regenWebApiKey')) {

		$flash = adminT('Web API management moved: Admin → API menu');

		$flashType = 'info';

	}



	$smarty->assign([

		'settingsTab' => $settingsTab,

		'settingsValues' => $values,

		'settingsKeys' => $editableKeys,

		'shopCurrencyCode' => Currency::getShopCurrency(),

		'shopCurrencyLabel' => Currency::label(),

		'orderRefPreview' => Order::previewReference(),

		'orderRefModes' => Order::getReferenceSuffixModes(),

		'orderRefActiveMode' => Order::getReferenceSettings()['suffix_mode'],

		'flash' => $flash,

		'flashType' => $flashType,

		'mailConfigured' => $mailConfigured,

		'usesSmtp' => $usesSmtp,

		'mailLayoutPlaceholders' => Mail::getLayoutPlaceholders(),

		'mailTemplatePreview' => Mail::previewTemplate(

			'<h2 style="margin:0 0 12px;font-size:18px;">Örnek e-posta</h2>'

			. '<p style="margin:0 0 12px;">Merhaba <strong>Ali Yılmaz</strong>,</p>'

			. '<p style="margin:0 0 20px;">Bu alan her e-postada değişen içeriktir. Sipariş bildirimleri, kampanyalar ve diğer tüm mailler buraya yerleştirilir.</p>'

			. '<p style="margin:0;"><a href="#" style="display:inline-block;padding:12px 22px;background:#1a1a1a;color:#ffffff;text-decoration:none;border-radius:6px;">Örnek buton</a></p>'

		),

		'shopIsActive' => StoreStatus::isActive(),

		'currentClientIp' => StoreStatus::getClientIp(),

		'adminUseEditor' => in_array($settingsTab, ['general', 'email'], true),

		'readOnlySettings' => [

			'DOMAIN' => Settings::get('DOMAIN'),

			'FOLDER' => Settings::get('FOLDER'),

		],

	]);



	AdminPage::add('settings', 'Site settings');

