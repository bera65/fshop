<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/XmlImportService.php';

class XmlImportModule extends ModuleBase
{
	public string $name = 'xml-import';
	public string $title = 'XML Ürün Aktarım';
	public string $version = '1.0.0';
	public string $description = 'XML dosyası veya URL ile ürün ekleme / güncelleme (stok kodu, barkod veya ürün adı eşleştirme)';
	public string $author = 'FShop';

	public array $apiActions = [
		'cron' => 'api/cron.php',
	];

	public function install(): bool
	{
		if (Settings::get(XmlImportService::SETTING_MATCH_KEY) === '') {
			Settings::set(XmlImportService::SETTING_MATCH_KEY, XmlImportService::MATCH_STOCK_CODE);
		}

		if (Settings::get(XmlImportService::SETTING_FEED_URL) === '') {
			Settings::set(XmlImportService::SETTING_FEED_URL, '');
		}

		if (Settings::get(XmlImportService::SETTING_UPDATE_IMAGES) === '') {
			Settings::set(XmlImportService::SETTING_UPDATE_IMAGES, '0');
		}

		if (Settings::get(XmlImportService::SETTING_LAST_CRON) === '') {
			Settings::set(XmlImportService::SETTING_LAST_CRON, '');
		}

		return true;
	}

	public function uninstall(): bool
	{
		return true;
	}

	public function boot(): void
	{
		$this->registerAdminMenuLink('XML Import', 'catalog', 88);
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken, $domain;

		$flash = '';
		$flashType = 'info';
		$stats = null;
		$matchKey = XmlImportService::normalizeMatchKey(
			(string) Settings::get(XmlImportService::SETTING_MATCH_KEY)
		);
		$feedUrl = (string) Settings::get(XmlImportService::SETTING_FEED_URL);
		$updateImages = Settings::get(XmlImportService::SETTING_UPDATE_IMAGES) === '1';
		$lastCron = (string) Settings::get(XmlImportService::SETTING_LAST_CRON);

		if (Tools::isSubmit('saveXmlImportSettings')) {
			$token = (string) Tools::getValue('token');

			if (!hash_equals((string) $adminToken, $token)) {
				$flash = adminT('Invalid request');
				$flashType = 'danger';
			} else {
				$matchKey = XmlImportService::normalizeMatchKey((string) Tools::getValue('match_key'));
				$feedUrl = trim((string) Tools::getValue('feed_url'));
				$updateImages = Tools::getValue('update_images') === '1';

				Settings::set(XmlImportService::SETTING_MATCH_KEY, $matchKey);
				Settings::set(XmlImportService::SETTING_FEED_URL, $feedUrl);
				Settings::set(XmlImportService::SETTING_UPDATE_IMAGES, $updateImages ? '1' : '0');

				$flash = adminT('Settings saved');
				$flashType = 'success';
			}
		} elseif (Tools::isSubmit('runXmlImport')) {
			$token = (string) Tools::getValue('token');

			if (!hash_equals((string) $adminToken, $token)) {
				$flash = adminT('Invalid request');
				$flashType = 'danger';
			} elseif (Admin::isDemoMode()) {
				$flash = adminT('Demo mode: some edits are not allowed');
				$flashType = 'warning';
			} else {
				$matchKey = XmlImportService::normalizeMatchKey((string) Tools::getValue('match_key', $matchKey));
				$feedUrl = trim((string) Tools::getValue('feed_url', $feedUrl));
				$updateImages = Tools::getValue('update_images') === '1';
				$source = (string) Tools::getValue('import_source', 'file');
				$limit = (int) Tools::getValue('import_limit', 0);

				if ($limit !== 5) {
					$limit = 0;
				}

				Settings::set(XmlImportService::SETTING_MATCH_KEY, $matchKey);
				Settings::set(XmlImportService::SETTING_FEED_URL, $feedUrl);
				Settings::set(XmlImportService::SETTING_UPDATE_IMAGES, $updateImages ? '1' : '0');

				if ($source === 'url') {
					$result = XmlImportService::importFromUrl($feedUrl, $matchKey, $updateImages, $limit);
				} else {
					$result = XmlImportService::importFromUpload($_FILES['xml_file'] ?? [], $matchKey, $updateImages, $limit);
				}

				$flash = (string) ($result['message'] ?? '');
				$flashType = !empty($result['success']) ? 'success' : 'danger';
				$stats = is_array($result['stats'] ?? null) ? $result['stats'] : null;
			}
		}

		$shopToken = (string) Settings::get('SHOP_TOKEN');
		$cronUrl = rtrim((string) $domain, '/') . '/api/module.php?m=xml-import&action=cron&token=' . rawurlencode($shopToken);

		$smarty->assign([
			'pageTitle' => $this->title,
			'flash' => $flash,
			'flashType' => $flashType,
			'xmlMatchKey' => $matchKey,
			'xmlFeedUrl' => $feedUrl,
			'xmlUpdateImages' => $updateImages,
			'xmlImportStats' => $stats,
			'xmlDemoMode' => Admin::isDemoMode(),
			'xmlModuleUrl' => Admin::url($this->getAdminSlug()),
			'xmlCronUrl' => $cronUrl,
			'xmlLastCron' => $lastCron,
		]);
	}
}
