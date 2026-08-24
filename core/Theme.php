<?php

class Theme
{
	private const MAX_ZIP_BYTES = 52428800;

	/** Allowed file extensions inside an uploaded theme ZIP (no executables). */
	private const THEME_ZIP_ALLOWED_EXTENSIONS = [
		'tpl', 'css', 'js', 'json', 'map',
		'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'avif', 'bmp',
		'woff', 'woff2', 'ttf', 'otf', 'eot',
		'txt', 'md', 'xml', 'less', 'scss', 'sass',
	];

	private const DEFAULT_COLOR_GROUPS = [
		'marka' => 'Marka',
		'yuzey' => 'Yüzey',
		'metin' => 'Metin',
		'ek' => 'Buton & Header',
		'fiyat' => 'Fiyat & Rozet',
		'footer' => 'Footer',
		'social' => 'Sosyal',
		'kategori' => 'Kategori',
		'gelismis' => 'Gelişmiş',
	];

	public static function templatesPath(): string
	{
		return dirname(__DIR__) . '/templates';
	}

	public static function schemaPath(string $theme): string
	{
		return self::templatesPath() . '/' . self::sanitizeName($theme) . '/theme.schema.json';
	}

	/** @return array<string, mixed>|null */
	public static function loadSchema(string $theme): ?array
	{
		if (!self::isValidName($theme)) {
			return null;
		}

		$path = self::schemaPath($theme);

		if (!is_file($path)) {
			return null;
		}

		$raw = file_get_contents($path);

		if ($raw === false) {
			return null;
		}

		$decoded = json_decode($raw, true);

		return is_array($decoded) ? $decoded : null;
	}

	/** @return array{label: string, description: string, preview: string, edit: string, has_schema: bool} */
	public static function getMeta(string $theme): array
	{
		$schema = self::loadSchema($theme);

		return [
			'label' => (string) ($schema['label'] ?? self::labelFor($theme)),
			'description' => (string) ($schema['description'] ?? ''),
			'preview' => (string) ($schema['preview'] ?? 'theme-preview.png'),
			'edit' => (string) ($schema['edit'] ?? ''),
			'has_schema' => $schema !== null,
		];
	}

	public static function resolveEditModule(string $theme): ?string
	{
		if (!self::isValidName($theme)) {
			return null;
		}

		$edit = self::getMeta($theme)['edit'];

		if ($edit === '' || !preg_match('/^[a-z0-9\-]+$/', $edit)) {
			return null;
		}

		return $edit;
	}

	public static function getPreviewUrl(string $theme, string $domain): string
	{
		$meta = self::getMeta($theme);
		$candidates = array_unique([
			$meta['preview'],
			'theme-preview.png',
			'theme-preview.jpg',
			'theme-preview.webp',
			'preview.png',
			'preview.jpg',
		]);

		foreach ($candidates as $file) {
			$file = ltrim(str_replace(['..', '\\'], '', $file), '/');

			if ($file !== '' && is_file(self::templatesPath() . '/' . $theme . '/' . $file)) {
				return rtrim($domain, '/') . '/templates/' . $theme . '/' . $file;
			}
		}

		return '';
	}

	/** @return array<string, array{name: string, label: string, has_colors: bool}> */
	public static function discover(): array
	{
		$dir = self::templatesPath();
		$themes = [];

		if (!is_dir($dir)) {
			return $themes;
		}

		foreach (scandir($dir) ?: [] as $entry) {
			if ($entry === '.' || $entry === '..' || $entry === 'admin') {
				continue;
			}

			$path = $dir . '/' . $entry;

			if (!is_dir($path) || !is_file($path . '/header.tpl')) {
				continue;
			}

			$meta = self::getMeta($entry);

			$themes[$entry] = [
				'name' => $entry,
				'label' => $meta['label'],
				'description' => $meta['description'],
				'edit_module' => self::resolveEditModule($entry),
				'has_colors' => is_file(self::colorsPath($entry)) || isset(self::loadSchema($entry)['colors']),
				'has_schema' => $meta['has_schema'],
				'screenshot' => self::getScreenshotUrl($entry),
				'preview_url' => self::getScreenshotUrl($entry),
			];
		}

		ksort($themes);

		return $themes;
	}

	public static function labelFor(string $name): string
	{
		$schema = self::loadSchema($name);

		if (is_array($schema) && !empty($schema['label'])) {
			$label = (string) $schema['label'];

			if ($label !== self::sanitizeName($name)) {
				return $label;
			}
		}

		$themeJsonPath = self::templatesPath() . '/' . $name . '/theme.json';

		if (is_file($themeJsonPath)) {
			$data = json_decode((string) file_get_contents($themeJsonPath), true);

			if (is_array($data) && !empty($data['name'])) {
				return (string) $data['name'];
			}
		}

		$labels = [
			'default' => 'Varsayılan Tema',
			'blue' => 'Blue',
			'nova' => 'FriSay Nova',
			'restoran' => 'Restoran',
			'chapan' => 'Chapan',
			'dress' => 'Dress',
			'prime' => 'Prime',
			'fyazilim' => 'Frisay',
		];

		return $labels[$name] ?? ucfirst(str_replace(['-', '_'], ' ', $name));
	}

	public static function colorsPath(string $theme): string
	{
		return self::templatesPath() . '/' . self::sanitizeName($theme) . '/css/colors.css';
	}

	public static function customCssPath(string $theme): string
	{
		return self::templatesPath() . '/' . self::sanitizeName($theme) . '/css/custom.css';
	}

	private static function optionsSettingsKey(string $theme): string
	{
		return 'THEME_OPTIONS_' . self::sanitizeName($theme);
	}

	/** @return array<string, string> */
	public static function discoverHeaderVariants(string $theme): array
	{
		if (!self::isValidName($theme)) {
			return [];
		}

		$dir = self::templatesPath() . '/' . $theme . '/_mini';
		$variants = [];

		if (!is_dir($dir)) {
			return [];
		}

		foreach (scandir($dir) ?: [] as $entry) {
			if (!preg_match('/^header\d+\.tpl$/', $entry)) {
				continue;
			}

			$key = substr($entry, 0, -4);
			$variants[$key] = self::headerVariantLabel($key);
		}

		ksort($variants);

		return $variants;
	}

	private static function headerVariantLabel(string $key): string
	{
		$labels = [
			'header1' => 'Klasik',
			'header2' => 'Modern (üst bar)',
			'header3' => 'Kompakt',
		];

		return $labels[$key] ?? ucfirst($key);
	}

	/** @return array<string, string> */
	public static function getColorGroups(string $theme): array
	{
		$schema = self::loadSchema($theme);

		if (is_array($schema['color_groups'] ?? null) && $schema['color_groups'] !== []) {
			$groups = [];

			foreach ($schema['color_groups'] as $key => $label) {
				$groups[(string) $key] = (string) $label;
			}

			return $groups;
		}

		return self::DEFAULT_COLOR_GROUPS;
	}

	/** @param array<string, mixed> $field */
	private static function resolveSchemaFieldOptions(string $theme, array $field): array
	{
		if (isset($field['options']) && is_array($field['options']) && $field['options'] !== []) {
			$options = [];

			foreach ($field['options'] as $key => $label) {
				$options[(string) $key] = (string) $label;
			}

			return $options;
		}

		$source = (string) ($field['source'] ?? '');

		if ($source === 'header_variants') {
			return self::discoverHeaderVariants($theme);
		}

		if ($source === 'container_widths') {
			return self::getContainerWidthChoices();
		}

		if ($source === 'fonts') {
			return self::getFontChoices();
		}

		return [];
	}

	/** @return array<string, array{label: string, type: string, default: string, options?: array<string, string>}> */
	public static function getOptionDefinitions(string $theme): array
	{
		$schema = self::loadSchema($theme);
		$defs = [];

		if (is_array($schema['options'] ?? null) && $schema['options'] !== []) {
			foreach ($schema['options'] as $key => $field) {
				if (!is_array($field)) {
					continue;
				}

				$key = (string) $key;
				$options = self::resolveSchemaFieldOptions($theme, $field);
				$type = (string) ($field['type'] ?? 'select');
				$defs[$key] = [
					'label' => (string) ($field['label'] ?? $key),
					'type' => $type,
					'default' => (string) ($field['default'] ?? ($type === 'switch' ? '0' : '')),
					'options' => $options,
				];
			}
		} else {
			$defs = [
				'font' => [
					'label' => 'Yazı tipi',
					'type' => 'select',
					'default' => 'system',
					'options' => self::getFontChoices(),
				],
				'container_width' => [
					'label' => 'Site genişliği',
					'type' => 'select',
					'default' => '1320',
					'options' => self::getContainerWidthChoices(),
				],
			];
		}

		if (self::sanitizeName($theme) === 'nova') {
			$defs['font'] = [
				'label' => 'Yazı tipi',
				'type' => 'select',
				'default' => 'system',
				'options' => ['system' => 'Sistem font yığını'],
			];
			$defs['container_width'] = [
				'label' => 'Site genişliği',
				'type' => 'select',
				'default' => '1320',
				'options' => self::getContainerWidthChoices(),
			];
			$defs['dark_mode'] = [
				'label' => 'Koyu mod varsayılanı',
				'type' => 'select',
				'default' => 'off',
				'options' => ['off' => 'Kapalı', 'on' => 'Açık'],
			];
			$defs['border_radius'] = [
				'label' => 'Köşe yuvarlaklığı',
				'type' => 'select',
				'default' => 'md',
				'options' => ['sm' => 'Küçük (8px)', 'md' => 'Orta (12px)', 'lg' => 'Büyük (16px)'],
			];
			$defs['home_slider'] = [
				'label' => 'Ana sayfa slider',
				'type' => 'select',
				'default' => 'on',
				'options' => ['on' => 'Açık', 'off' => 'Kapalı'],
			];
		}

		$headers = self::discoverHeaderVariants($theme);

		if ($headers !== []) {
			$defaultHeader = isset($headers['header2']) ? 'header2' : (string) array_key_first($headers);
			$existingDefault = (string) ($defs['header']['default'] ?? '');

			if ($existingDefault !== '' && isset($headers[$existingDefault])) {
				$defaultHeader = $existingDefault;
			}

			$defs['header'] = [
				'label' => (string) ($defs['header']['label'] ?? 'Header stili'),
				'type' => 'select',
				'default' => $defaultHeader,
				'options' => $headers,
			];
		} elseif (isset($defs['header'])) {
			unset($defs['header']);
		}

		return $defs;
	}

	/** @return array<string, string> */
	public static function getFontChoices(): array
	{
		return [
			'system' => 'Sistem (Segoe UI)',
			'inter' => 'Inter',
			'poppins' => 'Poppins',
			'nunito' => 'Nunito',
			'roboto' => 'Roboto',
			'open-sans' => 'Open Sans',
		];
	}

	/** @return array<string, string> */
	public static function getContainerWidthChoices(): array
	{
		return [
			'1140' => 'Standart (1140px)',
			'1320' => 'Geniş (1320px)',
			'1440' => 'Çok geniş (1440px)',
			'fluid' => 'Tam genişlik',
		];
	}

	/** @return array{font_family: string, google_font_url: string} */
	public static function resolveFont(string $fontKey): array
	{
		$map = [
			'system' => [
				'font_family' => "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
				'google_font_url' => '',
			],
			'inter' => [
				'font_family' => "'Inter', sans-serif",
				'google_font_url' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
			],
			'poppins' => [
				'font_family' => "'Poppins', sans-serif",
				'google_font_url' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap',
			],
			'nunito' => [
				'font_family' => "'Nunito', sans-serif",
				'google_font_url' => 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap',
			],
			'roboto' => [
				'font_family' => "'Roboto', sans-serif",
				'google_font_url' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap',
			],
			'open-sans' => [
				'font_family' => "'Open Sans', sans-serif",
				'google_font_url' => 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap',
			],
		];

		return $map[$fontKey] ?? $map['system'];
	}

	/** @return array<string, string> */
	public static function getDefaultOptions(string $theme): array
	{
		$options = [];

		foreach (self::getOptionDefinitions($theme) as $key => $meta) {
			$options[$key] = (string) $meta['default'];
		}

		return $options;
	}

	/** @return array<string, string> */
	public static function getOptions(string $theme): array
	{
		if (!self::isValidName($theme)) {
			return [];
		}

		$defaults = self::getDefaultOptions($theme);
		$raw = Settings::get(self::optionsSettingsKey($theme));

		if ($raw === '') {
			return $defaults;
		}

		$decoded = json_decode($raw, true);

		if (!is_array($decoded)) {
			return $defaults;
		}

		$defs = self::getOptionDefinitions($theme);
		$merged = $defaults;

		foreach ($decoded as $key => $value) {
			if (!isset($defs[$key])) {
				continue;
			}

			$value = trim((string) $value);
			$allowed = $defs[$key]['options'] ?? null;

			if (is_array($allowed) && $allowed !== [] && !isset($allowed[$value])) {
				continue;
			}

			$merged[$key] = $value;
		}

		return $merged;
	}

	/** @param array<string, string> $options */
	public static function saveOptions(string $theme, array $options): array
	{
		if (!self::isValidName($theme)) {
			return ['success' => false, 'message' => 'Geçersiz tema'];
		}

		$defs = self::getOptionDefinitions($theme);
		$normalized = self::getDefaultOptions($theme);

		foreach ($defs as $key => $meta) {
			if (!array_key_exists($key, $options)) {
				if (($meta['type'] ?? '') === 'switch') {
					$normalized[$key] = '0';
				}

				continue;
			}

			$value = trim((string) $options[$key]);

			if (($meta['type'] ?? '') === 'switch') {
				$value = ($value === '1' || $value === 'on' || $value === 'true') ? '1' : '0';
			}

			$allowed = $meta['options'] ?? null;

			if (is_array($allowed) && $allowed !== [] && !isset($allowed[$value])) {
				return ['success' => false, 'message' => 'Geçersiz seçim: ' . $meta['label']];
			}

			$normalized[$key] = $value;
		}

		$json = json_encode($normalized, JSON_UNESCAPED_UNICODE);

		if ($json === false || !Settings::set(self::optionsSettingsKey($theme), $json)) {
			return ['success' => false, 'message' => 'Tema ayarları kaydedilemedi'];
		}

		self::writeCustomCss($theme, $normalized);

		return ['success' => true, 'message' => 'Tema özelleştirmesi kaydedildi'];
	}

	public static function ensureCustomCss(string $theme): void
	{
		if (!self::isValidName($theme)) {
			return;
		}

		$path = self::customCssPath($theme);

		if (is_file($path)) {
			return;
		}

		self::writeCustomCss($theme, self::getOptions($theme));
	}

	/** @param array<string, string> $options */
	private static function writeCustomCss(string $theme, array $options): void
	{
		$path = self::customCssPath($theme);
		$dir = dirname($path);

		if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
			return;
		}

		$font = self::resolveFont($options['font'] ?? 'system');
		$widthKey = $options['container_width'] ?? '1320';
		$widthMap = [
			'1140' => '1140px',
			'1320' => '1320px',
			'1440' => '1440px',
			'fluid' => '100%',
		];
		$maxWidth = $widthMap[$widthKey] ?? '1320px';
		$paddingX = $widthKey === 'fluid' ? '16px' : '12px';

		$radiusMap = [
			'sm' => '8px',
			'md' => '12px',
			'lg' => '16px',
			'none' => '0',
			'light' => '4px',
			'normal' => '6px',
			'large' => '12px',
		];
		$radius = $radiusMap[$options['border_radius'] ?? 'md'] ?? '12px';
		$darkDefault = ($options['dark_mode'] ?? 'off') === 'on' ? 'dark' : 'light';

		$css = implode("\n", [
			'/**',
			' * Tema özelleştirme — Admin > Temalar ekranından düzenlenir.',
			' * Kaynak: settings.THEME_OPTIONS_{tema} (JSON) + bu dosya.',
			' */',
			':root {',
			"\t--theme-font-family: {$font['font_family']};",
			"\t--theme-container-max: {$maxWidth};",
			"\t--theme-container-padding-x: {$paddingX};",
			"\t--container: var(--theme-container-max);",
			"\t--nova-radius: {$radius};",
			'}',
			'',
			'html[data-theme-init="' . $darkDefault . '"] { color-scheme: ' . ($darkDefault === 'dark' ? 'dark' : 'light') . '; }',
			'',
			'body, .prime-body, .fy-body {',
			"\tfont-family: var(--theme-font-family);",
			'}',
			'',
			'.custom-container,',
			'.fy-container,',
			'.prime-container,',
			'.page > .container,',
			'.page .container.custom-container {',
			"\tmax-width: var(--theme-container-max);",
			"\tpadding-left: var(--theme-container-padding-x);",
			"\tpadding-right: var(--theme-container-padding-x);",
			"\tmargin-left: auto;",
			"\tmargin-right: auto;",
			"\twidth: 100%;",
			'}',
			'',
		]);

		$userCssPath = $dir . '/user.css';

		if (is_file($userCssPath)) {
			$userCss = (string) file_get_contents($userCssPath);

			if (trim($userCss) !== '') {
				$css .= "\n/* --- Özel CSS --- */\n" . $userCss . "\n";
			}
		}

		file_put_contents($path, $css);
	}

	/** @return array<string, mixed> */
	public static function getResolvedOptions(string $theme): array
	{
		$options = self::getOptions($theme);
		$font = self::resolveFont($options['font'] ?? 'system');
		$header = $options['header'] ?? '';

		if ($header === '' || !is_file(self::templatesPath() . '/' . $theme . '/_mini/' . $header . '.tpl')) {
			$variants = self::discoverHeaderVariants($theme);
			$header = isset($variants['header2']) ? 'header2' : ($variants ? (string) array_key_first($variants) : '');
		}

		$widthKey = (string) ($options['container_width'] ?? '1320');
		$widthMap = [
			'1140' => '1140px',
			'1320' => '1320px',
			'1440' => '1440px',
			'fluid' => '100%',
		];

		return array_merge($options, [
			'header' => $header,
			'font_family' => $font['font_family'],
			'google_font_url' => $font['google_font_url'],
			'container_max' => $widthMap[$widthKey] ?? '1320px',
		]);
	}

	public static function isValidName(string $theme): bool
	{
		return (bool) preg_match('/^[a-z0-9_-]+$/', $theme)
			&& is_dir(self::templatesPath() . '/' . $theme);
	}

	public static function sanitizeName(string $theme): string
	{
		return preg_replace('/[^a-z0-9_-]/', '', strtolower($theme)) ?: 'default';
	}

	/** @return array<string, array{label: string, default: string, group: string}> */
	public static function getColorDefinitions(?string $theme = null): array
	{
		$base = [
			'brand-primary' => ['label' => 'Ana renk (buton)', 'default' => '#1a1a1a', 'group' => 'marka'],
			'brand-primary-dark' => ['label' => 'Ana renk koyu', 'default' => '#000000', 'group' => 'marka'],
			'brand-accent' => ['label' => 'Vurgu rengi', 'default' => '#d6001c', 'group' => 'marka'],
			'brand-accent-light' => ['label' => 'Vurgu açık', 'default' => '#fdeaec', 'group' => 'marka'],
			'surface' => ['label' => 'Sayfa arka planı', 'default' => '#ffffff', 'group' => 'yuzey'],
			'surface-soft' => ['label' => 'Yumuşak arka plan', 'default' => '#f6f6f6', 'group' => 'yuzey'],
			'border-color' => ['label' => 'Kenarlık rengi', 'default' => '#ebebeb', 'group' => 'yuzey'],
			'text-primary' => ['label' => 'Ana metin', 'default' => '#1a1a1a', 'group' => 'metin'],
			'text-secondary' => ['label' => 'İkincil metin', 'default' => '#767676', 'group' => 'metin'],
			'link-color' => ['label' => 'Bağlantı rengi', 'default' => '#1a1a1a', 'group' => 'metin'],
			'link-hover-color' => ['label' => 'Bağlantı hover rengi', 'default' => '#d6001c', 'group' => 'metin'],
			'color-dark' => ['label' => 'Koyu ton', 'default' => '#000000', 'group' => 'ek'],
			'color1' => ['label' => 'Buton / aksan 1', 'default' => '#1a1a1a', 'group' => 'ek'],
			'color1-hover' => ['label' => 'Buton hover 1', 'default' => '#000000', 'group' => 'ek'],
			'color2' => ['label' => 'Header arka plan', 'default' => '#f6f6f6', 'group' => 'ek'],
			'color3' => ['label' => 'Aksan 3', 'default' => '#1a1a1a', 'group' => 'ek'],
			'color3-hover' => ['label' => 'Aksan 3 hover', 'default' => '#000000', 'group' => 'ek'],
			'price-color' => ['label' => 'Fiyat rengi', 'default' => '#1a1a1a', 'group' => 'fiyat'],
			'old-price-color' => ['label' => 'Eski fiyat rengi', 'default' => '#767676', 'group' => 'fiyat'],
			'discount-bg' => ['label' => 'İndirim rozeti arka plan', 'default' => '#d6001c', 'group' => 'fiyat'],
			'discount-color' => ['label' => 'İndirim rozeti metni', 'default' => '#ffffff', 'group' => 'fiyat'],
			'footer-bg' => ['label' => 'Footer arka plan', 'default' => '#0c0c0c', 'group' => 'footer'],
			'footer-heading' => ['label' => 'Footer başlık', 'default' => '#ffffff', 'group' => 'footer'],
			'footer-text' => ['label' => 'Footer metin', 'default' => '#a3a3a3', 'group' => 'footer'],
			'footer-accent' => ['label' => 'Footer vurgu', 'default' => '#ffffff', 'group' => 'footer'],
			'footer-border' => ['label' => 'Footer kenarlık rengi', 'default' => '#0c0c0c', 'group' => 'footer'],
			'social-icon-color' => ['label' => 'Sosyal medya ikon rengi', 'default' => '#a3a3a3', 'group' => 'social'],
			'social-icon-hover' => ['label' => 'Sosyal medya ikon hover', 'default' => '#ffffff', 'group' => 'social'],
			'social-icon-bg' => ['label' => 'Sosyal medya ikon arka planı', 'default' => 'rgba(255,255,255,0.1)', 'group' => 'social'],
			'social-icon-bg-hover' => ['label' => 'Sosyal medya ikon arka plan hover', 'default' => '#d6001c', 'group' => 'social'],
			'theme-color' => ['label' => 'Tarayıcı tema rengi (theme-color)', 'default' => '#213c8b', 'group' => 'gelismis'],
			'active-category-color' => ['label' => 'Aktif kategori rengi', 'default' => '#d6001c', 'group' => 'kategori'],
			'category-hover-color' => ['label' => 'Kategori hover rengi', 'default' => '#d6001c', 'group' => 'kategori'],
			'mobile-menu-bg' => ['label' => 'Mobil menü arka plan', 'default' => '#ffffff', 'group' => 'kategori'],
			'mobile-category-icon-color' => ['label' => 'Mobil kategori ikon rengi', 'default' => '#213c8b', 'group' => 'kategori'],
		];

		if ($theme === null || !self::isValidName($theme)) {
			return $base;
		}

		$schema = self::loadSchema($theme);

		if (is_array($schema['colors'] ?? null) && $schema['colors'] !== []) {
			$defs = [];

			foreach ($schema['colors'] as $key => $field) {
				if (!is_array($field)) {
					continue;
				}

				$defs[(string) $key] = [
					'label' => (string) ($field['label'] ?? $key),
					'default' => (string) ($field['default'] ?? '#000000'),
					'group' => (string) ($field['group'] ?? 'marka'),
				];
			}

			return $defs;
		}

		return $base;
	}

	/** @return array<string, string> */
	public static function getColors(string $theme): array
	{
		self::ensureColorsFile($theme);

		$defs = self::getColorDefinitions($theme);
		$values = [];

		foreach ($defs as $key => $meta) {
			$values[$key] = $meta['default'];
		}

		$file = self::colorsPath($theme);

		if (!is_file($file)) {
			return $values;
		}

		$content = file_get_contents($file);

		if ($content === false) {
			return $values;
		}

		if (preg_match_all('/--([a-z0-9-]+)\s*:\s*([^;]+);/i', $content, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$key = $match[1];

				if (isset($defs[$key])) {
					$values[$key] = trim($match[2]);
				}
			}
		}

		return $values;
	}

	/** @param array<string, string> $colors */
	public static function saveColors(string $theme, array $colors): array
	{
		if (!self::isValidName($theme)) {
			return ['success' => false, 'message' => 'Geçersiz tema'];
		}

		$defs = self::getColorDefinitions($theme);
		$current = self::getColors($theme);
		$normalized = [];

		foreach ($defs as $key => $meta) {
			$value = trim((string) ($colors[$key] ?? ''));

			if ($value === '') {
				$value = (string) ($current[$key] ?? $meta['default']);
			}

			if (!self::isValidColor($value)) {
				return ['success' => false, 'message' => 'Geçersiz renk: ' . $meta['label']];
			}

			$normalized[$key] = $value;
		}

		$css = self::buildColorsCss($theme, $normalized);
		$path = self::colorsPath($theme);
		$dir = dirname($path);

		if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
			return ['success' => false, 'message' => 'Tema klasörü oluşturulamadı'];
		}

		if (file_put_contents($path, $css) === false) {
			return ['success' => false, 'message' => 'colors.css yazılamadı'];
		}

		return ['success' => true, 'message' => 'Tema renkleri kaydedildi'];
	}

	public static function setActiveTheme(string $theme): array
	{
		if (!self::isValidName($theme)) {
			return ['success' => false, 'message' => 'Geçersiz tema'];
		}

		if (!Settings::set('THEME', $theme)) {
			return ['success' => false, 'message' => 'Tema kaydedilemedi'];
		}

		self::ensureColorsFile($theme);
		self::ensureCustomCss($theme);

		return ['success' => true, 'message' => 'Aktif tema güncellendi'];
	}

	public static function ensureColorsFile(string $theme): void
	{
		if (!self::isValidName($theme)) {
			return;
		}

		$path = self::colorsPath($theme);

		if (is_file($path)) {
			return;
		}

		$defs = self::getColorDefinitions($theme);
		$defaults = [];

		foreach ($defs as $key => $meta) {
			$defaults[$key] = $meta['default'];
		}

		$dir = dirname($path);

		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		file_put_contents($path, self::buildColorsCss($theme, $defaults));
	}

	/** @param array<string, string> $colors */
	private static function buildColorsCss(string $theme, array $colors): string
	{
		$defs = self::getColorDefinitions($theme);
		$schema = self::loadSchema($theme);
		$lines = [
			'/**',
			' * Tema renkleri — Admin > Temalar ekranından düzenlenir.',
			' */',
			':root {',
		];

		foreach ($defs as $key => $meta) {
			$value = $colors[$key] ?? $meta['default'];
			$lines[] = "\t--{$key}: {$value};";
		}

		$path = self::colorsPath($theme);

		if (is_file($path)) {
			$existing = (string) file_get_contents($path);

			if (preg_match_all('/--([a-z0-9-]+)\s*:\s*([^;]+);/i', $existing, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$key = $match[1];

					if (isset($defs[$key])) {
						continue;
					}

					$lines[] = "\t--{$key}: " . trim($match[2]) . ';';
				}
			}
		}

		if (is_array($schema['color_aliases'] ?? null)) {
			foreach ($schema['color_aliases'] as $alias => $source) {
				$alias = (string) $alias;
				$source = (string) $source;
				$lines[] = "\t--{$alias}: var(--{$source});";
			}
		} elseif (!isset($schema['colors'])) {
			$lines[] = "\t--surface-muted: var(--surface);";
			$lines[] = "\t--color-text: var(--text-primary);";
			$lines[] = "\t--text-gray: var(--text-secondary);";
		}

		$lines[] = '}';
		$lines[] = '';

		$extra = (string) ($schema['color_extra_css'] ?? '');

		if ($extra !== '') {
			$lines[] = $extra;
			$lines[] = '';
		} elseif (self::sanitizeName($theme) === 'nova') {
			$lines[] = self::novaDarkModeColorBlock();
			$lines[] = '';
		}

		return implode("\n", $lines);
	}

	private static function novaDarkModeColorBlock(): string
	{
		return implode("\n", [
			'html[data-theme="dark"] {',
			"\t--nova-bg: #0B1220;",
			"\t--nova-surface: #111827;",
			"\t--nova-surface-soft: #1F2937;",
			"\t--nova-text: #F9FAFB;",
			"\t--nova-text-muted: #9CA3AF;",
			"\t--nova-border: #374151;",
			"\t--nova-primary-soft: rgba(37, 99, 235, 0.15);",
			"\t--nova-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.35);",
			"\t--nova-shadow-md: 0 8px 24px rgba(0, 0, 0, 0.35);",
			"\t--nova-shadow-lg: 0 16px 40px rgba(0, 0, 0, 0.45);",
			"\tcolor-scheme: dark;",
			'}',
		]);
	}

	private static function isValidColor(string $value): bool
	{
		if ($value === '') {
			return false;
		}

		if (preg_match('/^var\(--[a-z0-9-]+\)$/i', $value)) {
			return true;
		}

		if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value)) {
			return true;
		}

		if (preg_match('/^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+(?:\s*,\s*(?:0?\.\d+|1))?\s*\)$/i', $value)) {
			return true;
		}

		return (bool) preg_match('/^[a-z]+$/i', $value);
	}

	/** @param array<string, mixed> $file $_FILES entry */
	public static function installFromZip(array $file, string $themeName = ''): array
	{
		if (!class_exists('ZipArchive')) {
			return ['success' => false, 'message' => 'Sunucuda ZipArchive eklentisi yok'];
		}

		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
			return ['success' => false, 'message' => 'ZIP dosyası seçilmedi'];
		}

		if (!empty($file['error'])) {
			return ['success' => false, 'message' => 'ZIP yüklenemedi'];
		}

		if (($file['size'] ?? 0) > self::MAX_ZIP_BYTES) {
			return ['success' => false, 'message' => 'ZIP dosyası en fazla 50 MB olabilir'];
		}

		$zip = new ZipArchive();
		$opened = $zip->open($file['tmp_name']);

		if ($opened !== true) {
			return ['success' => false, 'message' => 'ZIP dosyası açılamadı'];
		}

		$rootPrefix = self::detectZipThemeRoot($zip);

		if ($rootPrefix === null) {
			$zip->close();

			return ['success' => false, 'message' => 'ZIP içinde header.tpl bulunamadı'];
		}

		$folderName = $themeName !== '' ? self::sanitizeName($themeName) : self::sanitizeName(trim($rootPrefix, '/'));

		if ($folderName === '' || $folderName === 'admin') {
			$zip->close();

			return ['success' => false, 'message' => 'Tema adı gerekli (ZIP kök dizindeyse klasör adını girin)'];
		}

		if (!self::isValidThemeName($folderName)) {
			$zip->close();

			return ['success' => false, 'message' => 'Tema adı yalnızca küçük harf, rakam, tire ve alt çizgi içerebilir'];
		}

		$targetDir = self::templatesPath() . '/' . $folderName;

		if (is_dir($targetDir)) {
			$zip->close();

			return ['success' => false, 'message' => 'Bu isimde bir tema zaten var: ' . $folderName];
		}

		$tempDir = sys_get_temp_dir() . '/fshop-theme-' . bin2hex(random_bytes(8));

		if (!mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
			$zip->close();

			return ['success' => false, 'message' => 'Geçici klasör oluşturulamadı'];
		}

		$extractError = self::extractSafeThemeEntries($zip, $rootPrefix, $tempDir);
		$zip->close();

		if ($extractError !== null) {
			self::removeDirectory($tempDir);

			return ['success' => false, 'message' => $extractError];
		}

		if (!is_file($tempDir . '/header.tpl')) {
			self::removeDirectory($tempDir);

			return ['success' => false, 'message' => 'Tema geçersiz: header.tpl eksik'];
		}

		if (!is_file($tempDir . '/footer.tpl')) {
			self::removeDirectory($tempDir);

			return ['success' => false, 'message' => 'Tema geçersiz: footer.tpl eksik'];
		}

		if (!rename($tempDir, $targetDir)) {
			self::removeDirectory($tempDir);

			return ['success' => false, 'message' => 'Tema klasörü oluşturulamadı'];
		}

		self::ensureColorsFile($folderName);
		self::ensureCustomCss($folderName);

		return [
			'success' => true,
			'message' => 'Tema yüklendi: ' . $folderName,
			'theme' => $folderName,
		];
	}

	public static function copyTheme(string $source, string $newName, string $newLabel = ''): array
	{
		$source = self::sanitizeName($source);
		$newName = self::sanitizeName($newName);

		if (!self::isValidName($source)) {
			return ['success' => false, 'message' => 'Kaynak tema bulunamadı'];
		}

		if ($newName === '' || !self::isValidThemeName($newName)) {
			return ['success' => false, 'message' => 'Geçersiz yeni tema adı'];
		}

		if ($newName === $source) {
			return ['success' => false, 'message' => 'Yeni tema adı kaynakla aynı olamaz'];
		}

		$sourceDir = self::templatesPath() . '/' . $source;
		$targetDir = self::templatesPath() . '/' . $newName;

		if (is_dir($targetDir)) {
			return ['success' => false, 'message' => 'Bu isimde bir tema zaten var'];
		}

		if (!self::copyDirectory($sourceDir, $targetDir)) {
			return ['success' => false, 'message' => 'Tema kopyalanamadı'];
		}

		if ($newLabel !== '') {
			self::updateSchemaLabel($newName, $newLabel);
		}

		$sourceOptions = Settings::get(self::optionsSettingsKey($source));

		if ($sourceOptions !== '') {
			Settings::set(self::optionsSettingsKey($newName), $sourceOptions);
		}

		self::ensureColorsFile($newName);
		self::ensureCustomCss($newName);

		return [
			'success' => true,
			'message' => 'Tema kopyalandı: ' . $newName,
			'theme' => $newName,
		];
	}

	public static function isValidThemeName(string $theme): bool
	{
		return (bool) preg_match('/^[a-z][a-z0-9_-]*$/', $theme) && $theme !== 'admin';
	}

	private static function updateSchemaLabel(string $theme, string $label): void
	{
		$path = self::schemaPath($theme);

		if (!is_file($path)) {
			return;
		}

		$schema = self::loadSchema($theme);

		if ($schema === null) {
			return;
		}

		$schema['label'] = $label;
		$json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

		if ($json !== false) {
			file_put_contents($path, $json . "\n");
		}
	}

	private static function detectZipThemeRoot(ZipArchive $zip): ?string
	{
		$hasRootHeader = false;
		$topDirs = [];

		for ($i = 0; $i < $zip->numFiles; $i++) {
			$entry = str_replace('\\', '/', (string) $zip->getNameIndex($i));

			if ($entry === 'header.tpl' || $entry === './header.tpl') {
				$hasRootHeader = true;
			}

			if (strpos($entry, '/') !== false) {
				$top = explode('/', $entry, 2)[0];

				if ($top !== '' && $top !== '__MACOSX') {
					$topDirs[$top] = true;
				}
			}
		}

		if ($hasRootHeader) {
			return '';
		}

		if (count($topDirs) === 1) {
			$dir = (string) array_key_first($topDirs);
			$prefix = $dir . '/';

			for ($i = 0; $i < $zip->numFiles; $i++) {
				$entry = str_replace('\\', '/', (string) $zip->getNameIndex($i));

				if ($entry === $prefix . 'header.tpl') {
					return $prefix;
				}
			}
		}

		return null;
	}

	/**
	 * Write ZIP members into $tempDir. Returns an error message, or null on success.
	 */
	private static function extractSafeThemeEntries(ZipArchive $zip, string $rootPrefix, string $tempDir): ?string
	{
		$tempReal = realpath($tempDir);

		if ($tempReal === false) {
			return 'Geçici klasör oluşturulamadı';
		}

		for ($i = 0; $i < $zip->numFiles; $i++) {
			$entry = str_replace('\\', '/', (string) $zip->getNameIndex($i));

			if ($entry === '' || strpos($entry, '__MACOSX/') === 0) {
				continue;
			}

			$baseName = basename($entry);

			if ($baseName === '.DS_Store') {
				continue;
			}

			if (self::isZipEntrySymlink($zip, $i) || !self::isSafeZipEntry($entry, $rootPrefix)) {
				return 'ZIP içinde güvenli olmayan dosya yolu: ' . $entry;
			}

			$relative = substr($entry, strlen($rootPrefix));
			$relative = ltrim(str_replace('\\', '/', (string) $relative), '/');

			if ($relative === '' || substr($relative, -1) === '/') {
				continue;
			}

			$ext = strtolower((string) pathinfo($relative, PATHINFO_EXTENSION));

			if ($ext === '') {
				$stat = $zip->statIndex($i);
				if (is_array($stat) && (int) ($stat['size'] ?? 0) === 0) {
					continue;
				}

				return 'ZIP içinde güvenli olmayan dosya yolu: ' . $entry;
			}

			$dest = $tempReal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
			$destDir = dirname($dest);

			if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
				return 'Tema dosyaları çıkarılamadı';
			}

			$destDirReal = realpath($destDir);

			if ($destDirReal === false || !self::isPathInside($destDirReal, $tempReal)) {
				return 'ZIP içinde güvenli olmayan dosya yolu: ' . $entry;
			}

			$contents = $zip->getFromIndex($i);

			if ($contents === false || file_put_contents($dest, $contents) === false) {
				return 'Tema dosyaları yazılamadı';
			}

			$writtenReal = realpath($dest);

			if ($writtenReal === false || !self::isPathInside($writtenReal, $tempReal)) {
				@unlink($dest);

				return 'ZIP içinde güvenli olmayan dosya yolu: ' . $entry;
			}
		}

		return null;
	}

	private static function isSafeZipEntry(string $entry, string $rootPrefix): bool
	{
		$entry = str_replace('\\', '/', $entry);

		if ($entry === '' || strpos($entry, "\0") !== false || strpos($entry, ':') !== false) {
			return false;
		}

		if ($entry[0] === '/' || strpos($entry, '//') === 0 || preg_match('#^[a-zA-Z]:#', $entry)) {
			return false;
		}

		if ($rootPrefix !== '' && strpos($entry, $rootPrefix) !== 0) {
			return false;
		}

		if (strpos($entry, '__MACOSX/') === 0) {
			return false;
		}

		$parts = explode('/', $entry);
		$last = count($parts) - 1;

		foreach ($parts as $i => $part) {
			if ($part === '.' || $part === '..') {
				return false;
			}

			if ($part === '' && $i !== $last) {
				return false;
			}
		}

		if (substr($entry, -1) === '/') {
			return true;
		}

		$base = strtolower(basename($entry));

		if ($base === '.htaccess' || $base === '.htpasswd' || $base === 'web.config') {
			return false;
		}

		$ext = strtolower((string) pathinfo($base, PATHINFO_EXTENSION));

		if ($ext === '' || !in_array($ext, self::THEME_ZIP_ALLOWED_EXTENSIONS, true)) {
			return false;
		}

		return true;
	}

	private static function isZipEntrySymlink(ZipArchive $zip, int $index): bool
	{
		if (!method_exists($zip, 'getExternalAttributesIndex')) {
			return false;
		}

		$opsys = 0;
		$attr = 0;

		if (!$zip->getExternalAttributesIndex($index, $opsys, $attr)) {
			return false;
		}

		$unix = defined('ZipArchive::OPSYS_UNIX') ? ZipArchive::OPSYS_UNIX : 3;

		if ((int) $opsys !== (int) $unix) {
			return false;
		}

		$mode = ($attr >> 16) & 0xFFFF;

		return ($mode & 0170000) === 0120000;
	}

	private static function isPathInside(string $path, string $root): bool
	{
		$path = rtrim(str_replace('\\', '/', $path), '/');
		$root = rtrim(str_replace('\\', '/', $root), '/');

		if (DIRECTORY_SEPARATOR === '\\') {
			$path = strtolower($path);
			$root = strtolower($root);
		}

		return $path === $root || strpos($path, $root . '/') === 0;
	}

	private static function copyDirectory(string $source, string $target): bool
	{
		if (!is_dir($source)) {
			return false;
		}

		if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
			return false;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $item) {
			/** @var SplFileInfo $item */
			$subPath = substr($item->getPathname(), strlen($source) + 1);
			$dest = $target . DIRECTORY_SEPARATOR . $subPath;

			if ($item->isDir()) {
				if (!is_dir($dest) && !mkdir($dest, 0755, true) && !is_dir($dest)) {
					return false;
				}
			} elseif (!copy($item->getPathname(), $dest)) {
				return false;
			}
		}

		return true;
	}

	private static function removeDirectory(string $dir): void
	{
		if (!is_dir($dir)) {
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($iterator as $item) {
			/** @var SplFileInfo $item */
			if ($item->isDir()) {
				rmdir($item->getPathname());
			} else {
				unlink($item->getPathname());
			}
		}

		rmdir($dir);
	}

	public static function getScreenshotUrl(string $theme): string
	{
		$domain = rtrim((string) Settings::get('DOMAIN'), '/') . '/';
		$preview = self::getPreviewUrl($theme, $domain);

		if ($preview !== '') {
			return $preview;
		}

		$dir = self::templatesPath() . '/' . self::sanitizeName($theme);
		$extensions = ['png', 'jpg', 'jpeg', 'webp'];

		foreach ($extensions as $ext) {
			$file = $dir . '/screenshot.' . $ext;

			if (is_file($file)) {
				return $domain . 'templates/' . $theme . '/screenshot.' . $ext . '?v=' . filemtime($file);
			}
		}

		$label = self::labelFor($theme);
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 250" width="100%" height="100%">'
			. '<rect width="100%" height="100%" fill="#f1f5f9"/>'
			. '<rect x="15" y="15" width="370" height="220" rx="6" fill="#ffffff" stroke="#e2e8f0" stroke-width="2"/>'
			. '<circle cx="200" cy="100" r="32" fill="#eef2ff"/>'
			. '<path d="M188 100a12 12 0 1 1 24 0M178 116h44c0-7-5.5-12-12-12h-20c-6.5 0-12 5-12 12z" fill="#2563EB"/>'
			. '<text x="50%" y="165" dominant-baseline="middle" text-anchor="middle" font-family="Segoe UI,sans-serif" font-size="18" font-weight="600" fill="#1e293b">'
			. htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</text>'
			. '<text x="50%" y="195" dominant-baseline="middle" text-anchor="middle" font-family="Segoe UI,sans-serif" font-size="12" font-weight="500" fill="#64748b">'
			. htmlspecialchars('/templates/' . $theme, ENT_QUOTES, 'UTF-8') . '</text>'
			. '</svg>';

		return 'data:image/svg+xml;base64,' . base64_encode($svg);
	}

	public static function getUserCss(string $theme): string
	{
		if (!self::isValidName($theme)) {
			return '';
		}

		$path = self::templatesPath() . '/' . self::sanitizeName($theme) . '/css/user.css';

		if (is_file($path)) {
			return (string) file_get_contents($path);
		}

		return '';
	}

	public static function saveUserCss(string $theme, string $css): array
	{
		if (!self::isValidName($theme)) {
			return ['success' => false, 'message' => 'Geçersiz tema'];
		}

		$path = self::templatesPath() . '/' . self::sanitizeName($theme) . '/css/user.css';
		$dir = dirname($path);

		if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
			return ['success' => false, 'message' => 'Tema CSS klasörü oluşturulamadı'];
		}

		if (file_put_contents($path, $css) === false) {
			return ['success' => false, 'message' => 'user.css dosyası yazılamadı'];
		}

		self::writeCustomCss($theme, self::getOptions($theme));

		return ['success' => true, 'message' => 'Özel CSS başarıyla kaydedildi'];
	}

	public static function deleteTheme(string $theme): array
	{
		$theme = self::sanitizeName($theme);

		if (!self::isValidName($theme)) {
			return ['success' => false, 'message' => 'Geçersiz tema'];
		}

		if (in_array($theme, ['admin', 'blue', 'restoran'], true)) {
			return ['success' => false, 'message' => 'Sistem teması silinemez'];
		}

		$activeTheme = Settings::get('THEME') ?: 'default';

		if ($theme === $activeTheme) {
			return ['success' => false, 'message' => 'Aktif kullanılan tema silinemez'];
		}

		$dir = self::templatesPath() . '/' . $theme;

		if (!is_dir($dir)) {
			return ['success' => false, 'message' => 'Tema bulunamadı'];
		}

		self::removeDirectory($dir);

		return ['success' => true, 'message' => 'Tema başarıyla silindi'];
	}

	public static function addTheme(string $themeName, string $themeLabel, ?string $cloneFrom = null, ?array $zipFile = null): array
	{
		$themeName = self::sanitizeName($themeName);

		if ($themeName === '' || $themeName === 'admin') {
			return ['success' => false, 'message' => 'Geçersiz tema klasör adı'];
		}

		$destDir = self::templatesPath() . '/' . $themeName;

		if (is_dir($destDir)) {
			return ['success' => false, 'message' => 'Bu isimde bir tema zaten mevcut'];
		}

		if ($zipFile !== null) {
			$result = self::installFromZip($zipFile, $themeName);

			if (!$result['success']) {
				return $result;
			}

			$schemaPath = $destDir . '/theme.schema.json';

			if (!is_file($schemaPath)) {
				file_put_contents($schemaPath, json_encode([
					'label' => $themeLabel !== '' ? $themeLabel : ucfirst($themeName),
					'description' => '',
					'preview' => 'theme-preview.png',
				], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
			}

			file_put_contents($destDir . '/theme.json', json_encode([
				'name' => $themeLabel !== '' ? $themeLabel : ucfirst($themeName),
				'created_at' => date('Y-m-d H:i:s'),
			], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

			return ['success' => true, 'message' => 'Tema başarıyla yüklendi'];
		}

		if ($cloneFrom !== null && $cloneFrom !== '') {
			$result = self::copyTheme($cloneFrom, $themeName, $themeLabel);

			if ($result['success']) {
				file_put_contents($destDir . '/theme.json', json_encode([
					'name' => $themeLabel !== '' ? $themeLabel : ucfirst($themeName),
					'created_at' => date('Y-m-d H:i:s'),
				], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
			}

			return $result;
		}

		return ['success' => false, 'message' => 'Geçersiz parametreler'];
	}
}
