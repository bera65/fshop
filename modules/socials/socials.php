<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

class SocialsModule extends ModuleBase
{
	public string $name = 'socials';
	public string $title = 'Social Media';
	public string $version = '1.0.0';
	public string $description = 'Sosyal medyada paylaş';
	public string $author = 'FShop';

	public array $displayHooks = [
		'product_detail' => 'Sosyal Medya',
	];

	public array $defaultDisplayHooks = ['product_detail'];

	public array $frontStylesheets = ['social.css'];

	public function install(): bool
	{
		return true;
	}

	public function uninstall(): bool
	{
		return true;
	}

	public function boot(): void
	{
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'product_detail') {
			return null;
		}
		$html = $this->renderFrontTemplate('product_detail', [
			'link' 		=> '',
			'facebook' 	=> $this->getAssetUrl('img/facebook.png'),
			'x' 		=> $this->getAssetUrl('img/x.png'),
			'whatsapp'	=> $this->getAssetUrl('img/whatsapp.png'),
			'pin'		=> $this->getAssetUrl('img/pin.png'),
		]);

		return $html !== '' ? $html : null;
	}
}
