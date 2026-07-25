<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/AiClient.php';

class AiAssistantModule extends ModuleBase
{
	public string $name = 'ai-assistant';
	public string $title = 'Yapay Zeka Asistanı';
	public string $version = '1.2.0';
	public string $description = 'Admin header: dashboard/sipariş özeti, SEO, CMS, blog ve ürün metni AI desteği';
	public string $author = 'FShop';

	public array $displayHooks = [
		'admin_header' => 'Admin orta alan üstü — sayfa bağlamlı AI bar',
	];

	public array $defaultDisplayHooks = [
		'admin_header',
	];

	public array $adminStylesheets = ['admin.css'];
	public array $adminScripts = [];

	public array $apiActions = [
		'improve-product' => 'api/improve-product.php',
		'page-summary' => 'api/page-summary.php',
		'write-seo' => 'api/write-seo.php',
		'write-cms' => 'api/write-cms.php',
		'write-blog' => 'api/write-blog.php',
		'translate-ui' => 'api/translate-ui.php',
		'test' => 'api/test.php',
	];

	private const SETTINGS = [
		'AI_ASSISTANT_PROVIDER' => 'openai',
		'AI_ASSISTANT_API_KEY' => '',
		'AI_ASSISTANT_BASE_URL' => 'https://api.openai.com/v1',
		'AI_ASSISTANT_MODEL' => 'gpt-4o-mini',
		'AI_ASSISTANT_MAX_TOKENS' => '1600',
		'AI_ASSISTANT_TONE' => 'professional',
		'AI_ASSISTANT_LANG' => 'tr',
	];

	/** AI bar yalnızca bu admin sayfalarında görünür. */
	private const ALLOWED_PAGES = [
		'dashboard',
		'orders',
		'order',
		'cancellations',
		'cancel',
		'returns',
		'return',
		'module-blog',
		'cms-edit',
		'seo',
		'product',
		'translations',
	];

	public function install(): bool
	{
		foreach (self::SETTINGS as $key => $default) {
			if (Settings::get($key) === '') {
				Settings::set($key, $default);
			}
		}

		return true;
	}

	public function uninstall(): bool
	{
		return true;
	}

	public function getAdminPageTitle(): string
	{
		return $this->title;
	}

	public function boot(): void
	{
		$this->ensureDisplayHooks();
	}

	private function ensureDisplayHooks(): void
	{
		if (!Module::isEnabled($this->name)) {
			return;
		}

		$assigned = Module::getAssignedDisplayHooks($this->name);

		// Ürün butonu hook'unu kaldır; yalnızca admin_header kalsın
		$filtered = array_values(array_filter(
			$assigned,
			static function ($hook) {
				return $hook !== 'admin_product_button';
			}
		));

		if (!in_array('admin_header', $filtered, true)) {
			$filtered[] = 'admin_header';
		}

		if ($filtered !== $assigned) {
			Module::setDisplayHooks($this->name, $filtered);
		}
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';
		$flashType = 'success';
		$testResult = null;

		if (Tools::isSubmit('saveAiAssistant')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} else {
				$provider = (string) Tools::getValue('provider', 'openai');
				$presets = self::providerPresets();
				$preset = $presets[$provider] ?? $presets['openai'];

				$baseUrl = trim((string) Tools::getValue('base_url', ''));
				$model = trim((string) Tools::getValue('model', ''));
				$apiKey = trim((string) Tools::getValue('api_key', ''));

				if ($baseUrl === '' && !empty($preset['base_url'])) {
					$baseUrl = $preset['base_url'];
				}

				if ($model === '' && !empty($preset['model'])) {
					$model = $preset['model'];
				}

				Settings::set('AI_ASSISTANT_PROVIDER', $provider);
				Settings::set('AI_ASSISTANT_BASE_URL', rtrim($baseUrl, '/'));
				Settings::set('AI_ASSISTANT_MODEL', $model !== '' ? $model : 'gpt-4o-mini');
				Settings::set('AI_ASSISTANT_MAX_TOKENS', (string) max(256, min(4000, (int) Tools::getValue('max_tokens', 1600))));
				Settings::set('AI_ASSISTANT_TONE', (string) Tools::getValue('tone', 'professional'));
				Settings::set('AI_ASSISTANT_LANG', (string) Tools::getValue('lang', 'tr'));

				if ($apiKey !== '') {
					Settings::set('AI_ASSISTANT_API_KEY', $apiKey);
				}

				$flash = 'Ayarlar kaydedildi';
			}
		}

		if (Tools::isSubmit('testAiAssistant')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} else {
				$testResult = AiAssistantClient::chat(
					'Sen kısa yardımcı bir asistansın. Türkçe cevap ver.',
					'FShop AI Asistanı bağlantı testi. Tek cümleyle çalıştığını doğrula.',
					['max_tokens' => 80, 'temperature' => 0.2]
				);
				$flash = !empty($testResult['success'])
					? 'Bağlantı başarılı'
					: ((string) ($testResult['message'] ?? 'Test başarısız'));
				$flashType = !empty($testResult['success']) ? 'success' : 'danger';
			}
		}

		$smarty->assign([
			'moduleTitle' => $this->title,
			'modulePageUrl' => Admin::url($this->getAdminSlug()),
			'flash' => $flash,
			'flashType' => $flashType,
			'testResult' => $testResult,
			'configured' => AiAssistantClient::isConfigured(),
			'provider' => Settings::get('AI_ASSISTANT_PROVIDER') ?: 'openai',
			'baseUrl' => Settings::get('AI_ASSISTANT_BASE_URL'),
			'model' => Settings::get('AI_ASSISTANT_MODEL'),
			'maxTokens' => (int) (Settings::get('AI_ASSISTANT_MAX_TOKENS') ?: 1600),
			'tone' => Settings::get('AI_ASSISTANT_TONE') ?: 'professional',
			'lang' => Settings::get('AI_ASSISTANT_LANG') ?: 'tr',
			'hasApiKey' => trim((string) Settings::get('AI_ASSISTANT_API_KEY')) !== '',
			'providers' => self::providerPresets(),
			'tokenGuides' => self::tokenGuides(),
		]);
	}

	public function renderAdminDisplayHook(string $hook, array $context = []): ?string
	{
		global $domain, $adminToken;

		if ($hook !== 'admin_header' || !in_array($hook, $this->getSupportedDisplayHooks(), true)) {
			return null;
		}

		$pageName = (string) ($context['page_name'] ?? '');

		if ($pageName === '' || !in_array($pageName, self::ALLOWED_PAGES, true)) {
			return null;
		}

		// Blog: yalnızca yazılar sekmesi (kategori sekmesinde gösterme)
		if ($pageName === 'module-blog') {
			$blogTab = (string) Tools::getValue('tab', 'posts');
			if ($blogTab !== 'posts') {
				return null;
			}
		}

		$mode = $this->resolveHeaderMode($pageName);
		$domainBase = rtrim((string) $domain, '/');

		return $this->renderAdminTemplate('admin_header', [
			'configured' => AiAssistantClient::isConfigured(),
			'settingsUrl' => Admin::url('module-ai-assistant'),
			'adminToken' => (string) $adminToken,
			'domain' => $domainBase . '/',
			'pageName' => $pageName,
			'pageTitle' => (string) ($context['page_title'] ?? ''),
			'mode' => $mode['mode'],
			'modeHint' => $mode['hint'],
			'primaryLabel' => $mode['label'],
			'blogEditing' => !empty($mode['blog_editing']),
			'tone' => Settings::get('AI_ASSISTANT_TONE') ?: 'professional',
			'apiSummaryUrl' => $domainBase . '/api/module.php?m=ai-assistant&action=page-summary',
			'apiSeoUrl' => $domainBase . '/api/module.php?m=ai-assistant&action=write-seo',
			'apiCmsUrl' => $domainBase . '/api/module.php?m=ai-assistant&action=write-cms',
			'apiBlogUrl' => $domainBase . '/api/module.php?m=ai-assistant&action=write-blog',
			'apiProductUrl' => $domainBase . '/api/module.php?m=ai-assistant&action=improve-product',
			'apiTranslateUrl' => $domainBase . '/api/module.php?m=ai-assistant&action=translate-ui',
			'targetLang' => (string) Tools::getValue('lang', ''),
		]) ?: null;
	}

	/** @return array{mode:string,hint:string,label:string,blog_editing?:bool} */
	private function resolveHeaderMode(string $pageName): array
	{
		if ($pageName === 'translations') {
			return [
				'mode' => 'translate',
				'hint' => 'Önce İngilizce kaynağı netleştirin; sonra boş çevirileri AI ile doldurun',
				'label' => 'Boşları AI ile çevir',
			];
		}

		if ($pageName === 'seo') {
			return [
				'mode' => 'seo',
				'hint' => 'Meta başlık ve açıklamaları AI ile doldurun',
				'label' => 'SEO metinlerini AI ile yaz',
			];
		}

		if ($pageName === 'cms-edit') {
			return [
				'mode' => 'cms',
				'hint' => 'Aktif dil sekmesindeki CMS içeriğini AI ile yazın',
				'label' => 'CMS içeriğini AI ile yaz',
			];
		}

		if ($pageName === 'product') {
			return [
				'mode' => 'product',
				'hint' => 'Ürün başlığı, açıklama ve SEO alanlarını iyileştirin',
				'label' => 'Ürün metinlerini iyileştir',
			];
		}

		if ($pageName === 'module-blog') {
			$editing = (int) Tools::getValue('edit', 0) > 0;

			return [
				'mode' => 'blog',
				'hint' => $editing
					? 'Mevcut yazıyı AI ile düzenleyin veya fikre göre yeniden yazın'
					: 'Konu/fikir girin; AI blog yazısı üretsin',
				'label' => $editing ? 'Blog yazısını AI ile düzenle' : 'Blog yazısı yaz',
				'blog_editing' => $editing,
			];
		}

		if ($pageName === 'dashboard') {
			return [
				'mode' => 'dashboard',
				'hint' => 'Gösterge paneli verilerine göre analiz',
				'label' => 'Paneli analiz et',
			];
		}

		return [
			'mode' => 'summary',
			'hint' => 'Bu sayfadaki önemli noktaları özetleyin',
			'label' => 'Bu sayfayı özetle',
		];
	}

	/** @return array<string, array{label:string,base_url:string,model:string,docs:string}> */
	public static function providerPresets(): array
	{
		return [
			'openai' => [
				'label' => 'OpenAI',
				'base_url' => 'https://api.openai.com/v1',
				'model' => 'gpt-4o-mini',
				'docs' => 'https://platform.openai.com/api-keys',
			],
			'groq' => [
				'label' => 'Groq',
				'base_url' => 'https://api.groq.com/openai/v1',
				'model' => 'llama-3.3-70b-versatile',
				'docs' => 'https://console.groq.com/keys',
			],
			'openrouter' => [
				'label' => 'OpenRouter',
				'base_url' => 'https://openrouter.ai/api/v1',
				'model' => 'openai/gpt-4o-mini',
				'docs' => 'https://openrouter.ai/keys',
			],
			'custom' => [
				'label' => 'Özel (OpenAI uyumlu)',
				'base_url' => '',
				'model' => '',
				'docs' => '',
			],
		];
	}

	/** @return list<array{title:string,url:string,note:string}> */
	public static function tokenGuides(): array
	{
		return [
			[
				'title' => 'Groq API Key',
				'url' => 'https://console.groq.com/keys',
				'note' => 'Ücretsiz kota ile test. Provider: Groq',
			],
			[
				'title' => 'OpenAI API Key',
				'url' => 'https://platform.openai.com/api-keys',
				'note' => 'gpt-4o-mini ekonomik seçenek',
			],
			[
				'title' => 'OpenRouter API Key',
				'url' => 'https://openrouter.ai/keys',
				'note' => 'Birden fazla modele erişim',
			],
		];
	}
}
