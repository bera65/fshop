<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	require_once dirname(__DIR__, 2) . '/core/Seo.php';

	$flash = '';
	$flashType = 'info';
	$pageDefs = Seo::getPageDefinitions();
	$pageValues = Seo::getAllPageValues();
	$schemaOrg = Seo::getSchemaOrgFields();

	if (Tools::isSubmit('saveSeo')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$input = [];

			foreach (array_keys($pageDefs) as $pageId) {
				$input[$pageId . '_title'] = (string) Tools::getValue('seo_' . $pageId . '_title');
				$input[$pageId . '_description'] = (string) Tools::getValue('seo_' . $pageId . '_description');
			}

			$result = Seo::savePages($input);

			if ($result['success'] && !Seo::saveTitleSuffix((string) Tools::getValue('seo_title_suffix'))) {
				$result = ['success' => false, 'message' => adminT('SEO settings could not be saved')];
			}

			if ($result['success'] && !Seo::saveSchemaOrg([
				'SCHEMA_ORG_STREET' => (string) Tools::getValue('schema_org_street'),
				'SCHEMA_ORG_CITY' => (string) Tools::getValue('schema_org_city'),
				'SCHEMA_ORG_POSTAL' => (string) Tools::getValue('schema_org_postal'),
				'SCHEMA_ORG_LAT' => (string) Tools::getValue('schema_org_lat'),
				'SCHEMA_ORG_LNG' => (string) Tools::getValue('schema_org_lng'),
				'SCHEMA_FACEBOOK_URL' => (string) Tools::getValue('schema_facebook_url'),
				'SCHEMA_INSTAGRAM_URL' => (string) Tools::getValue('schema_instagram_url'),
				'SCHEMA_YOUTUBE_URL' => (string) Tools::getValue('schema_youtube_url'),
			])) {
				$result = ['success' => false, 'message' => adminT('Schema.org settings could not be saved')];
			}

			if ($result['success']) {
				$pageValues = Seo::getAllPageValues();
				$schemaOrg = Seo::getSchemaOrgFields();
				$flash = $result['message'];
				$flashType = 'success';
			} else {
				$flash = $result['message'];
				$flashType = 'danger';
			}
		}
	}

	$smarty->assign([
		'seoPages' => $pageDefs,
		'seoValues' => $pageValues,
		'seoTitleSuffix' => Seo::getTitleSuffix(),
		'siteNameSetting' => Settings::get('SITE_NAME'),
		'schemaOrg' => $schemaOrg,
		'flash' => $flash,
		'flashType' => $flashType,
	]);

	AdminPage::add('seo', 'SEO settings');
