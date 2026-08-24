<?php

	if (!defined('IN_ADMIN')) {
		exit;
	}

	$flash = '';
	$flashType = 'success';
	$upgradeLogs = [];

	if (Tools::isSubmit('checkUpdate')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals((string) $adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = UpdateChecker::check(true);
			$flash = (string) ($result['message'] ?? '');
			$flashType = !empty($result['success']) ? 'success' : 'danger';

			if (!empty($result['update_available'])) {
				$flashType = 'warning';
			}
		}
	}

	if (Tools::isSubmit('runFullUpdate')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals((string) $adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$offer = UpdateChecker::getCachedOffer();
			$result = UpdateInstaller::runFullUpdate($offer);
			$flash = (string) ($result['message'] ?? '');
			$flashType = !empty($result['success']) ? 'success' : 'danger';
			$upgradeLogs = is_array($result['logs'] ?? null) ? $result['logs'] : [];
		}
	}

	if (Tools::isSubmit('runUpgrade')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals((string) $adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = Upgrade::runPending();
			$flash = (string) ($result['message'] ?? '');
			$flashType = !empty($result['success']) ? 'success' : 'danger';
			$upgradeLogs = is_array($result['logs'] ?? null) ? $result['logs'] : [];
		}
	}

	$installed = Upgrade::getInstalledVersion();
	$code = Upgrade::getCodeVersion();
	$pending = Upgrade::getPending();
	$upToDate = Upgrade::isUpToDate();
	$changelog = Upgrade::parseChangelogBetween($installed, $code);
	$offer = UpdateChecker::getCachedOffer();
	$remoteAvailable = $offer && version_compare($offer['version'], $code, '>');
	$canZip = class_exists('ZipArchive', false);
	$canHttp = function_exists('curl_init') || (bool) ini_get('allow_url_fopen');

	$smarty->assign([
		'flash' => $flash,
		'flashType' => $flashType,
		'upgradeLogs' => $upgradeLogs,
		'upgradeInstalled' => $installed,
		'upgradeCode' => $code,
		'upgradePending' => $pending,
		'upgradeUpToDate' => $upToDate,
		'upgradeChangelog' => $changelog,
		'updateLastCheck' => Settings::get(UpdateChecker::KEY_LAST_CHECK),
		'updateOffer' => $offer,
		'updateRemoteAvailable' => $remoteAvailable,
		'updateCanZip' => $canZip,
		'updateCanHttp' => $canHttp,
	]);

	AdminPage::add('upgrade', 'System update');
