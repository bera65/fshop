<?php
/**
 * Ortam ayarları şablonu.
 * Canlı sunucuda: cp config/env.example.php config/env.php
 * Ardından değerleri düzenleyin.
 */
return [
	// local = geliştirme, production = canlı
	'APP_ENV' => 'production',
	'APP_DEBUG' => false,

	'DB_HOST' => 'localhost',
	'DB_NAME' => 'fshop',
	'DB_USER' => 'fshop_user',
	'DB_PASS' => 'GÜÇLÜ_ŞİFRE_BURAYA',

	// Apache RewriteBase (.htaccess) — kök dizinde / , alt klasörde /fshop/
	'REWRITE_BASE' => '/',

	// Public admin URL slug (fiziksel klasör her zaman admin/ kalır).
	// Örnek: bo_9xK2m7 → https://site.com/bo_9xK2m7/
	// Değiştirdikten sonra admin panele bir kez girin (.htaccess senkronlanır) veya
	// Admin::syncHtaccessRewrite() çalışsın. Eski /admin yolu kapanır.
	'ADMIN_URI' => 'admin',
];
