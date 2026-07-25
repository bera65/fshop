<?php

class AdminPage
{
	public static function add(string $pageName, string $pageTitle = '', bool $noLayout = false): void
	{
		global $smarty;

		self::assignPageMeta($pageName, $pageTitle);

		if ($noLayout) {
			$smarty->display(_ADMIN_THEME_DIR_ . $pageName . '.tpl');
			return;
		}

		$smarty->display(_ADMIN_THEME_DIR_ . 'layout/header.tpl');
		$smarty->display(_ADMIN_THEME_DIR_ . $pageName . '.tpl');
		$smarty->display(_ADMIN_THEME_DIR_ . 'layout/footer.tpl');
	}

	public static function addModule(ModuleBase $module): void
	{
		self::addModulePage($module, $module->getAdminSlug(), $module->getAdminPageTitle(), 'admin');
	}

	public static function addModulePage(
		ModuleBase $module,
		string $pageName,
		string $pageTitle = '',
		string $template = 'admin'
	): void {
		global $smarty;

		self::assignPageMeta($pageName, $pageTitle);

		$smarty->assign([
			'moduleName' => $module->name,
			'moduleDetailUrl' => Admin::url('module?name=' . rawurlencode($module->name)),
			'moduleConfigUrl' => Admin::url($module->getAdminSlug()),
			'moduleAdminAssets' => $module->getAdminAssets(),
		]);

		$smarty->display(_ADMIN_THEME_DIR_ . 'layout/header.tpl');

		$templatePath = $module->getAdminTemplatePath($template);

		if (is_file($templatePath)) {
			$smarty->display('file:' . $templatePath);
		} else {
			$smarty->display(_ADMIN_THEME_DIR_ . 'module-config-empty.tpl');
		}

		$smarty->display(_ADMIN_THEME_DIR_ . 'layout/footer.tpl');
	}

	private static function assignPageMeta(string $pageName, string $pageTitle): void
	{
		global $smarty;

		$existingHooks = $smarty->getTemplateVars('adminHooks');
		if (!is_array($existingHooks)) {
			$existingHooks = [];
		}

		$layoutHooks = Module::renderAdminHooks(['admin_header', 'admin_footer'], [
			'page_name' => $pageName,
			'page_title' => $pageTitle,
		]);

		$smarty->assign([
			'pageName' => $pageName,
			'pageTitle' => $pageTitle !== '' && function_exists('adminT') ? adminT($pageTitle) : $pageTitle,
			'moduleNavActive' => $pageName === 'modules' || $pageName === 'module',
			'adminHooks' => array_merge($layoutHooks, $existingHooks),
		]);
	}
}
