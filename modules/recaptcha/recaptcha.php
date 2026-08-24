<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/RecaptchaService.php';

class RecaptchaModule extends ModuleBase
{
	public string $name = 'recaptcha';
	public string $title = 'Google reCAPTCHA';
	public string $version = '1.0.0';
	public string $description = 'İletişim, giriş, kayıt ve admin giriş formlarında Google reCAPTCHA doğrulaması';
	public string $author = 'FShop';

	public array $displayHooks = [
		'contact_form' => 'İletişim formu — CAPTCHA alanı',
		'auth_login' => 'Giriş formu — CAPTCHA alanı',
		'auth_register' => 'Kayıt formu — CAPTCHA alanı',
		'admin_login' => 'Admin giriş — CAPTCHA alanı',
	];

	public array $defaultDisplayHooks = [
		'contact_form',
		'auth_login',
		'auth_register',
		'admin_login',
	];

	public array $frontScripts = ['front.js'];
	public array $frontStylesheets = ['front.css'];
	public array $adminStylesheets = ['admin.css'];

	public function getFrontScripts(): array
	{
		// Google API betiği front.js içinde sürüme göre yüklenir (v2/v3 karışmasını önler).
		if (!RecaptchaService::isActive()) {
			return [];
		}

		return parent::getFrontScripts();
	}

	public function install(): bool
	{
		return $this->runSqlFile('install.sql');
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	public function boot(): void
	{
		Module::registerHook('form.captcha.validate', static function (string $form, &$error): void {
			RecaptchaService::validateForm($form, $error);
		});

		if (defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
			Module::registerHook('smarty.assign', function ($smarty): void {
				if (!$smarty) {
					return;
				}

				$config = RecaptchaService::getClientConfig();

				if (!$config['active']) {
					return;
				}

				$smarty->assign('recaptchaClientConfig', $config);
				$smarty->assign('recaptchaConfigJson', Security::jsonForHtmlScript($config));
			});
		}
	}

	/**
	 * Admin giriş şablonu bootstrap'tan önce yüklendiği için burada atanır.
	 * @param Smarty\Smarty|null $smarty
	 */
	public static function assignAdminLoginPage($smarty): void
	{
		if (!$smarty || !RecaptchaService::isEnabledFor('admin')) {
			return;
		}

		$module = new self();
		$s = RecaptchaService::getSettings();
		$config = RecaptchaService::getClientConfig();
		$html = $module->renderFrontTemplate('widget', [
			'formKey' => 'admin',
			'version' => $s['version'],
			'siteKey' => $s['site_key'],
		]);

		$smarty->assign([
			'recaptchaAdminLogin' => $html,
			'recaptchaClientConfig' => $config,
			'recaptchaConfigJson' => Security::jsonForHtmlScript($config),
			'recaptchaModuleJs' => $module->getAssetUrl('js/front.js'),
			'recaptchaModuleCss' => $module->getAssetUrl('css/front.css'),
		]);
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		$formMap = [
			'contact_form' => 'contact',
			'auth_login' => 'login',
			'auth_register' => 'register',
			'admin_login' => 'admin',
		];

		if (!isset($formMap[$hook]) || !RecaptchaService::isEnabledFor($formMap[$hook])) {
			return null;
		}

		$s = RecaptchaService::getSettings();
		$html = $this->renderFrontTemplate('widget', [
			'formKey' => $formMap[$hook],
			'version' => $s['version'],
			'siteKey' => $s['site_key'],
		]);

		return $html !== '' ? $html : null;
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		RecaptchaService::ensureSchema();
		$flash = '';
		$flashType = 'success';

		if (Tools::isSubmit('saveRecaptcha')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} elseif (RecaptchaService::saveSettings([
				'enabled' => Tools::getValue('enabled'),
				'version' => Tools::getValue('version'),
				'site_key' => Tools::getValue('site_key'),
				'secret_key' => Tools::getValue('secret_key'),
				'score_threshold' => Tools::getValue('score_threshold'),
				'enable_contact' => Tools::getValue('enable_contact'),
				'enable_login' => Tools::getValue('enable_login'),
				'enable_register' => Tools::getValue('enable_register'),
				'enable_admin' => Tools::getValue('enable_admin'),
			])) {
				$flash = 'reCAPTCHA ayarları kaydedildi';
			} else {
				$flash = 'Ayarlar kaydedilemedi';
				$flashType = 'danger';
			}
		}

		$settings = RecaptchaService::getSettings();

		$smarty->assign([
			'flash' => $flash,
			'flashType' => $flashType,
			'adminToken' => $adminToken,
			'settings' => $settings,
			'isConfigured' => RecaptchaService::isConfigured($settings),
		]);
	}
}
