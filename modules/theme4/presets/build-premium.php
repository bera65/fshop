<?php
/**
 * One-shot: build modules/theme4/presets/premium.json
 */
$base = dirname(__DIR__);
$css = file_get_contents($base . '/presets/premium-custom.css');

$layouts = [
	'home' => [
		'rows' => [
			[
				'id' => 'home_slider',
				'class' => 't4p-hero',
				'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
				'cols' => [[
					'id' => 'home_slider_col',
					'class' => '',
					'width' => ['mobile' => 12, 'tablet' => 12, 'desktop' => 12],
					'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
					'widgets' => [['id' => 'w_slider', 'type' => 'hook', 'settings' => ['hook' => 'home_slider']]],
				]],
			],
			[
				'id' => 'home_featured',
				'class' => 't4p-section',
				'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
				'cols' => [[
					'id' => 'home_featured_col',
					'class' => '',
					'width' => ['mobile' => 12, 'tablet' => 12, 'desktop' => 12],
					'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
					'widgets' => [[
						'id' => 'w_cat_win',
						'type' => 'category_products',
						'settings' => [
							'id_category' => 2,
							'limit' => 8,
							'title' => 'Windows Lisansları',
							'show_link' => 1,
						],
					]],
				]],
			],
			[
				'id' => 'home_banners',
				'class' => 't4p-banners',
				'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
				'cols' => [
					[
						'id' => 'bn1', 'class' => '',
						'width' => ['mobile' => 12, 'tablet' => 6, 'desktop' => 3],
						'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
						'widgets' => [['id' => 'wb1', 'type' => 'banner', 'settings' => [
							'image' => 'img/media/Antivirus-Mini-Banner_v2.jpg', 'link' => '/anti-virus', 'alt' => 'Antivirüs',
						]]],
					],
					[
						'id' => 'bn2', 'class' => '',
						'width' => ['mobile' => 12, 'tablet' => 6, 'desktop' => 3],
						'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
						'widgets' => [['id' => 'wb2', 'type' => 'banner', 'settings' => [
							'image' => 'img/media/Antivirus-Mini-Banner_v2.jpg', 'link' => '/office-lisans', 'alt' => 'Office',
						]]],
					],
					[
						'id' => 'bn3', 'class' => '',
						'width' => ['mobile' => 12, 'tablet' => 6, 'desktop' => 3],
						'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
						'widgets' => [['id' => 'wb3', 'type' => 'banner', 'settings' => [
							'image' => 'img/media/Antivirus-Mini-Banner_v2.jpg', 'link' => '/windows-lisance', 'alt' => 'Windows',
						]]],
					],
					[
						'id' => 'bn4', 'class' => '',
						'width' => ['mobile' => 12, 'tablet' => 6, 'desktop' => 3],
						'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
						'widgets' => [['id' => 'wb4', 'type' => 'banner', 'settings' => [
							'image' => 'img/media/Antivirus-Mini-Banner_v2.jpg', 'link' => '/special', 'alt' => 'Kampanyalar',
						]]],
					],
				],
			],
		],
	],
	'header' => [
		'rows' => [
			[
				'id' => 'header_top',
				'class' => 't4p-header-top',
				'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
				'cols' => [
					[
						'id' => 'header_logo', 'class' => '',
						'width' => ['mobile' => 4, 'tablet' => 3, 'desktop' => 3],
						'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
						'widgets' => [['id' => 'w_logo', 'type' => 'logo', 'settings' => [
							'image' => 'img/media/logo.png', 'link' => '', 'alt' => 'Logo', 'caption' => '',
						]]],
					],
					[
						'id' => 'header_search', 'class' => '',
						'width' => ['mobile' => 12, 'tablet' => 6, 'desktop' => 6],
						'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
						'widgets' => [['id' => 'w_search', 'type' => 'search', 'settings' => [
							'placeholder' => 'Ürün, lisans veya kategori ara…',
						]]],
					],
					[
						'id' => 'header_tools', 'class' => '',
						'width' => ['mobile' => 8, 'tablet' => 3, 'desktop' => 3],
						'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
						'widgets' => [['id' => 'w_tools', 'type' => 'header_tools', 'settings' => [
							'show_account' => 1, 'show_favorites' => 1, 'show_cart' => 1,
							'show_notifications' => 1, 'show_menu_btn' => 1,
						]]],
					],
				],
			],
			[
				'id' => 'header_nav',
				'class' => 't4p-header-nav',
				'hide' => ['mobile' => 1, 'tablet' => 0, 'desktop' => 0],
				'cols' => [[
					'id' => 'header_menu', 'class' => '',
					'width' => ['mobile' => 12, 'tablet' => 12, 'desktop' => 12],
					'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
					'widgets' => [['id' => 'w_menu', 'type' => 'hook', 'settings' => ['hook' => 'main_menu']]],
				]],
			],
		],
	],
	'footer' => [
		'rows' => [
			[
				'id' => 'footer_main',
				'class' => 't4p-footer-grid',
				'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
				'cols' => [
					[
						'id' => 'footer_brand', 'class' => '',
						'width' => ['mobile' => 12, 'tablet' => 6, 'desktop' => 3],
						'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
						'widgets' => [
							['id' => 'w_flogo', 'type' => 'logo', 'settings' => [
								'image' => 'img/media/logo.png', 'link' => '', 'alt' => 'Logo', 'caption' => '',
							]],
							['id' => 'w_ftext', 'type' => 'text', 'settings' => [
								'html' => '<p>Orijinal yazılım lisansları, anında teslimat ve güvenli ödeme ile tek adresiniz.</p>',
							]],
						],
					],
					[
						'id' => 'footer_links', 'class' => '',
						'width' => ['mobile' => 6, 'tablet' => 6, 'desktop' => 3],
						'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
						'widgets' => [['id' => 'w_flinks', 'type' => 'links', 'settings' => [
							'title' => 'Hızlı Erişim',
							'items' => [
								['source' => 'page', 'ref' => 'contact', 'label' => 'İletişim', 'url' => ''],
								['source' => 'page', 'ref' => 'special', 'label' => 'Tüm Ürünler', 'url' => ''],
								['source' => 'custom', 'ref' => '', 'label' => 'Yeni Ürünler', 'url' => '/special'],
							],
						]]],
					],
					[
						'id' => 'footer_cats', 'class' => '',
						'width' => ['mobile' => 6, 'tablet' => 6, 'desktop' => 3],
						'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
						'widgets' => [['id' => 'w_fcats', 'type' => 'links', 'settings' => [
							'title' => 'Kategoriler',
							'items' => [
								['source' => 'custom', 'ref' => '', 'label' => 'Antivirüs', 'url' => '/anti-virus'],
								['source' => 'custom', 'ref' => '', 'label' => 'Office Lisansları', 'url' => '/office-lisans'],
								['source' => 'custom', 'ref' => '', 'label' => 'Windows Lisansları', 'url' => '/windows-lisance'],
							],
						]]],
					],
					[
						'id' => 'footer_hook2', 'class' => '',
						'width' => ['mobile' => 12, 'tablet' => 12, 'desktop' => 3],
						'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
						'widgets' => [
							['id' => 'w_fhook', 'type' => 'hook', 'settings' => ['hook' => 'footer']],
							['id' => 'w_p1', 'type' => 'logo', 'settings' => [
								'image' => 'img/media/eczhanem.png', 'link' => '', 'alt' => 'Partner', 'caption' => '',
							]],
							['id' => 'w_p2', 'type' => 'logo', 'settings' => [
								'image' => 'img/media/shopLogo.png', 'link' => '', 'alt' => 'Partner', 'caption' => '',
							]],
							['id' => 'w_p3', 'type' => 'logo', 'settings' => [
								'image' => 'img/media/roundo-logo.png', 'link' => '', 'alt' => 'Partner', 'caption' => '',
							]],
							['id' => 'w_p4', 'type' => 'logo', 'settings' => [
								'image' => 'img/media/sm-icon-48ba39-360x360.png', 'link' => '', 'alt' => 'Partner', 'caption' => '',
							]],
						],
					],
				],
			],
		],
	],
];

$bundle = [
	'format' => 'fshop-theme4',
	'version' => 1,
	'module_version' => '2.2.0',
	'name' => 'Premium License Store',
	'exported_at' => date('c'),
	'settings' => [
		'site_width' => '1420px',
		'font_family' => "'Plus Jakarta Sans', system-ui, sans-serif",
		'logo' => 'img/media/logo.png',
		'favicon' => 'img/media/shopLogo.png',
		'copyright' => '© {year} {site}. Tüm hakları saklıdır.',
	],
	'layouts' => $layouts,
	'colors' => [
		'primary' => '#0A4A86',
		'primary-hover' => '#07355F',
		'primary-light' => '#EEF4FB',
		'secondary' => '#0F172A',
		'text' => '#0F172A',
		'text-muted' => '#64748B',
		'border' => '#E2E8F0',
		'background' => '#F5F7FA',
		'surface' => '#FFFFFF',
		'white' => '#FFFFFF',
		'danger' => '#DC2626',
	],
	'structural' => [
		'radius' => '14px',
		'radius-lg' => '18px',
		'radius-pill' => '999px',
		'shadow' => '0 10px 30px rgba(15, 23, 42, 0.06)',
		'shadow-nav' => '0 8px 28px rgba(15, 23, 42, 0.06)',
		'container' => '1420px',
		'transition' => '220ms ease',
		'font' => "'Plus Jakarta Sans', system-ui, sans-serif",
	],
	'custom_css' => $css,
	'custom_js' => "/** Theme4 Premium */\n",
];

$out = $base . '/presets/premium.json';
file_put_contents($out, json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Wrote {$out} (" . filesize($out) . " bytes)\n";
