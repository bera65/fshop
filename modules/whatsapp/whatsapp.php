<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

class WhatsappModule extends ModuleBase
{
	public string $name = 'whatsapp';
	public string $title = 'Chat';
	public string $version = '1.0.0';
	public string $description = 'Whatsapp kullanarak iletişime geçin';
	public string $author = 'FShop';

	public array $displayHooks = [
		'footer' => 'Canlı Destek',
	];

	public array $defaultDisplayHooks = ['footer'];

	public array $frontStylesheets = ['whatsapp.css'];

	public array $adminStylesheets = ['admin.css'];

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
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'footer') {
			return null;
		}
		$getNumber = DB::getRow('whatsapp', 'id = 1', 'phone');
		$html = $this->renderFrontTemplate('footer', [
			'whatsappImageUrl' 	=> $this->getAssetUrl('img/whatsapp.png'),
			'whatsappLink' 		=> $getNumber,
		]);

		return $html !== '' ? $html : null;
	}
	public function adminPage(): void
	{
		global $smarty, $adminToken;

		
		$smarty->assign([
			'flash' => 'Deneme',
		]);
	}
}
