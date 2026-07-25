<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	require_once dirname(__DIR__, 2) . '/core/Theme.php';
	require_once dirname(__DIR__, 2) . '/core/SiteAssets.php';

	$flash = '';
	$flashType = 'info';
	$activeTheme = Settings::get('THEME') ?: 'default';

	if (!Theme::isValidName($activeTheme)) {
		$activeTheme = 'default';
	}

	$editTheme = (string) Tools::getValue('theme', '');

	if ($editTheme === '' || !Theme::isValidName($editTheme)) {
		$editTheme = $activeTheme;
	}

	$editModule = Theme::resolveEditModule($editTheme);

	if ($editModule !== null) {
		header('Location: ' . Admin::url('module-' . $editModule));
		exit;
	}

	$redirectUrl = Admin::url('theme-customize') . '?theme=' . rawurlencode($editTheme);

	if (Tools::isSubmit('uploadLogo')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = SiteAssets::uploadLogo(
				(string) Tools::getValue('logo_key'),
				$_FILES['logo_file'] ?? []
			);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';
		}
	}

	if (Tools::isSubmit('saveFooterDescription')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} elseif (Settings::set('FOOTER_DESCRIPTION', trim((string) Tools::getValue('footer_description')))) {
			header('Location: ' . $redirectUrl . '&saved=1');
			exit;
		} else {
			$flash = adminT('Some settings could not be saved');
			$flashType = 'danger';
		}
	}

	if (Tools::isSubmit('saveThemeCustomize')) {
		$postToken = (string) Tools::getValue('token');
		$formTheme = (string) Tools::getValue('edit_theme');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} elseif (!Theme::isValidName($formTheme)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$editTheme = $formTheme;
			$redirectUrl = Admin::url('theme-customize') . '?theme=' . rawurlencode($editTheme);

			$optionDefs = Theme::getOptionDefinitions($editTheme);
			$colorDefs = Theme::getColorDefinitions($editTheme);
			$options = [];
			$colors = [];

			foreach (array_keys($optionDefs) as $key) {
				$options[$key] = (string) Tools::getValue('opt_' . $key);
			}

			foreach (array_keys($colorDefs) as $key) {
				$colors[$key] = (string) Tools::getValue('color_' . $key);
			}

			$optionsResult = Theme::saveOptions($editTheme, $options);
			$colorsResult = Theme::saveColors($editTheme, $colors);

			if ($optionsResult['success'] && $colorsResult['success']) {
				header('Location: ' . $redirectUrl . '&saved=1');
				exit;
			}

			$flash = !$optionsResult['success']
				? $optionsResult['message']
				: $colorsResult['message'];
			$flashType = 'danger';
		}
	}

	if (Tools::getValue('saved') === '1' && $flash === '') {
		$flash = adminT('Settings saved');
		$flashType = 'success';
	}

	$themeMeta = Theme::getMeta($editTheme);
	$themeOptionDefs = Theme::getOptionDefinitions($editTheme);
	$themeOptions = Theme::getOptions($editTheme);
	$headerVariants = Theme::discoverHeaderVariants($editTheme);
	$colorDefs = Theme::getColorDefinitions($editTheme);
	$colorGroups = Theme::getColorGroups($editTheme);
	$themeColors = Theme::getColors($editTheme);
	$colorPickerValues = [];

	foreach ($themeColors as $key => $value) {
		$colorPickerValues[$key] = preg_match('/^#[0-9a-f]{6}$/i', $value)
			? $value
			: ($colorDefs[$key]['default'] ?? '#000000');
	}

	$themes = Theme::discover();
	$previewUrl = $themes[$editTheme]['preview_url'] ?? '';

	$smarty->assign([
		'flash' => $flash,
		'flashType' => $flashType,
		'editTheme' => $editTheme,
		'activeTheme' => $activeTheme,
		'themeMeta' => $themeMeta,
		'themeOptionDefs' => $themeOptionDefs,
		'themeOptions' => $themeOptions,
		'headerVariants' => $headerVariants,
		'colorDefs' => $colorDefs,
		'colorGroups' => $colorGroups,
		'themeColors' => $themeColors,
		'colorPickerValues' => $colorPickerValues,
		'previewUrl' => $previewUrl,
		'siteLogos' => SiteAssets::getLogos(),
		'footerDescription' => trim((string) Settings::get('FOOTER_DESCRIPTION')),
		'footerDescriptionDefault' => Settings::getFooterDescriptionDefault(),
	]);

	AdminPage::add('theme-customize', 'Theme customize');
