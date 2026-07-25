<?php

if (!defined('IN_ADMIN')) {
	exit;
}

$flash = '';
$flashType = 'success';

$scope = (string) Tools::getValue('scope', 'storefront');

if (!in_array($scope, ['storefront', 'admin'], true)) {
	$scope = 'storefront';
}

$isAdminScope = $scope === 'admin';
$scopeLanguages = $isAdminScope ? AdminLang::getEditorLanguageList() : Lang::getAdminList();

$targetLang = strtolower(trim((string) Tools::getValue('lang', '')));
$scopeCodes = array_column($scopeLanguages, 'code');

if ($targetLang === '' || !in_array($targetLang, $scopeCodes, true)) {
	$targetLang = in_array('tr', $scopeCodes, true) ? 'tr' : (string) ($scopeCodes[0] ?? 'tr');
}

if ($targetLang === 'en' && count($scopeCodes) > 1) {
	foreach ($scopeCodes as $code) {
		if ($code !== 'en') {
			$targetLang = $code;
			break;
		}
	}
}

$filter = (string) Tools::getValue('filter', 'all');

if ($filter !== 'missing') {
	$filter = 'all';
}

$q = trim((string) Tools::getValue('q', ''));
$page = max(1, (int) Tools::getValue('page', 1));
$perPage = 40;

if (Tools::isSubmit('addUiTranslationKey')) {
	$postToken = (string) Tools::getValue('token');

	if (!hash_equals((string) $adminToken, $postToken)) {
		$flash = adminT('Invalid request');
		$flashType = 'danger';
	} else {
		$scope = (string) Tools::getValue('scope', $scope);
		$isAdminScope = $scope === 'admin';
		$targetLang = strtolower(trim((string) Tools::getValue('lang', $targetLang)));
		$newKey = trim((string) Tools::getValue('new_key', ''));
		$newEn = trim((string) Tools::getValue('new_en', ''));
		$newTranslation = trim((string) Tools::getValue('new_translation', ''));

		$result = $isAdminScope
			? AdminLang::addUiTranslationKey($newKey, $newEn, $targetLang, $newTranslation)
			: Lang::addUiTranslationKey($newKey, $newEn, $targetLang, $newTranslation);

		if (!empty($result['success'])) {
			$flash = adminT('Translation key added');
			$flashType = 'success';
			$q = $newKey;
			$page = 1;
			$filter = 'all';
		} else {
			$flash = adminT((string) ($result['message'] ?? 'Save failed'));
			$flashType = 'danger';
		}
	}
}

if (Tools::isSubmit('saveUiTranslations')) {
	$postToken = (string) Tools::getValue('token');

	if (!hash_equals((string) $adminToken, $postToken)) {
		$flash = adminT('Invalid request');
		$flashType = 'danger';
	} else {
		$scope = (string) Tools::getValue('scope', $scope);
		$isAdminScope = $scope === 'admin';
		$targetLang = strtolower(trim((string) Tools::getValue('lang', $targetLang)));
		$enUpdates = Tools::getValue('en');
		$trUpdates = Tools::getValue('tr');

		$enMap = is_array($enUpdates) ? $enUpdates : [];
		$trMap = is_array($trUpdates) ? $trUpdates : [];

		if ($isAdminScope) {
			$enResult = AdminLang::mergeUiDictionary('en', $enMap);
			$trResult = $targetLang !== 'en'
				? AdminLang::mergeUiDictionary($targetLang, $trMap)
				: ['success' => true, 'message' => ''];
		} else {
			$enResult = Lang::mergeUiDictionary('en', $enMap);
			$trResult = $targetLang !== 'en'
				? Lang::mergeUiDictionary($targetLang, $trMap)
				: ['success' => true, 'message' => ''];
		}

		if (!empty($enResult['success']) && !empty($trResult['success'])) {
			$flash = adminT('Translations saved');
			$flashType = 'success';
		} else {
			$flash = adminT((string) ($enResult['message'] ?? $trResult['message'] ?? 'Save failed'));
			$flashType = 'danger';
		}

		$filter = (string) Tools::getValue('filter', $filter);
		$q = trim((string) Tools::getValue('q', $q));
		$page = max(1, (int) Tools::getValue('page', $page));
	}
}

$workspace = $isAdminScope
	? AdminLang::getUiTranslationWorkspace($targetLang, $filter, $q)
	: Lang::getUiTranslationWorkspace($targetLang, $filter, $q);

$allRows = $workspace['rows'];
$totalFiltered = count($allRows);
$totalPages = max(1, (int) ceil($totalFiltered / $perPage));

if ($page > $totalPages) {
	$page = $totalPages;
}

$offset = ($page - 1) * $perPage;
$pageRows = array_slice($allRows, $offset, $perPage);

$smarty->assign([
	'flash' => $flash,
	'flashType' => $flashType,
	'uiScope' => $scope,
	'uiTargetLang' => $targetLang,
	'uiFilter' => $filter,
	'uiQuery' => $q,
	'uiRows' => $pageRows,
	'uiTotalKeys' => $workspace['total'],
	'uiMissingCount' => $workspace['missing'],
	'uiFilteredCount' => $totalFiltered,
	'uiPage' => $page,
	'uiTotalPages' => $totalPages,
	'uiPerPage' => $perPage,
	'uiLanguages' => $scopeLanguages,
]);

AdminPage::add('translations', 'UI Translations');
