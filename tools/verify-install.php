<?php
/**
 * install/ klasörünün eksiksiz olup olmadığını kontrol eder.
 * Kullanım: php tools/verify-install.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);

$required = [
	'install/index.php'           => 'Kurulum sihirbazı giriş noktası',
	'install/schema.sql'          => 'Ana veritabanı şeması (Installer zorunlu)',
	'install/patch_taxes.sql'     => 'Vergi oranları tablosu (kurulumda otomatik)',
	'install/seed_demo.sql'       => 'Demo veri (isteğe bağlı kurulum)',
	'install/assets/install.css'  => 'Kurulum arayüzü stilleri',
	'install/.htaccess'           => 'Web erişim notu / güvenlik',
];

$optional = [
	'install/seed.sql',
	'install/README.md',
	'install/patch_admin.sql',
	'install/patch_addresses.sql',
	'install/patch_categories.sql',
	'install/patch_contact.sql',
	'install/patch_ecommerce_features.sql',
	'install/patch_favorites.sql',
	'install/patch_module_hooks.sql',
	'install/patch_modules.sql',
	'install/patch_orders.sql',
	'install/patch_product_short_description.sql',
	'install/patch_products.sql',
	'install/patch_production.sql',
	'install/patch_settings_contact.sql',
	'install/patch_users.sql',
];

$schemaTables = [
	'settings', 'categories', 'brands', 'products', 'images', 'users', 'orders',
	'order_detail', 'favorites', 'contact_messages', 'user_addresses',
	'user_notifications', 'coupons', 'admins', 'modules', 'module_display_hooks',
	'taxes',
];

echo "FShop install/ doğrulama\n";
echo str_repeat('=', 40) . "\n\n";

$errors = 0;
$warnings = 0;

foreach ($required as $file => $desc) {
	$path = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $file);
	$ok = is_file($path) && filesize($path) > 0;
	echo ($ok ? '[OK] ' : '[EKSIK] ') . $file . ' — ' . $desc . "\n";
	if (!$ok) {
		++$errors;
	}
}

echo "\nİsteğe bağlı / eski yama dosyaları:\n";
foreach ($optional as $file) {
	$path = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $file);
	$ok = is_file($path);
	echo ($ok ? '[OK] ' : '[YOK] ') . $file . "\n";
	if (!$ok) {
		++$warnings;
	}
}

$schemaPath = $root . '/install/schema.sql';
$taxPatchPath = $root . '/install/patch_taxes.sql';
if (is_file($schemaPath)) {
	echo "\nschema.sql tablo kontrolü:\n";
	$schema = file_get_contents($schemaPath) ?: '';
	foreach ($schemaTables as $table) {
		if ($table === 'taxes' && !is_file($taxPatchPath)) {
			$found = (bool) preg_match('/CREATE TABLE (`|IF NOT EXISTS `)' . preg_quote($table, '/') . '`/i', $schema);
		} elseif ($table === 'taxes') {
			$patch = is_file($taxPatchPath) ? (file_get_contents($taxPatchPath) ?: '') : '';
			$found = (bool) preg_match('/CREATE TABLE (`|IF NOT EXISTS `)' . preg_quote($table, '/') . '`/i', $patch)
				|| (bool) preg_match('/CREATE TABLE (`|IF NOT EXISTS `)' . preg_quote($table, '/') . '`/i', $schema);
		} else {
			$found = (bool) preg_match('/CREATE TABLE (`|IF NOT EXISTS `)' . preg_quote($table, '/') . '`/i', $schema);
		}
		echo ($found ? '[OK] ' : '[EKSIK] ') . 'CREATE TABLE `' . $table . "`\n";
		if (!$found) {
			++$errors;
		}
	}
} elseif (is_file($taxPatchPath)) {
	echo "\n[patch] install/patch_taxes.sql mevcut (schema.sql yok — kurulumda patch çalışır)\n";
}

$indexPath = $root . '/install/index.php';
if (is_file($indexPath)) {
	$index = file_get_contents($indexPath) ?: '';
	echo "\nindex.php bağlantıları:\n";
	$needsInstaller = strpos($index, 'core/Installer.php') !== false;
	echo ($needsInstaller ? '[OK] ' : '[EKSIK] ') . "core/Installer.php require\n";
	if (!$needsInstaller) {
		++$errors;
	}
}

$installerPath = $root . '/core/Installer.php';
if (is_file($installerPath)) {
	echo "\nInstaller.php bağımlılıkları:\n";
	$installer = file_get_contents($installerPath) ?: '';
	foreach (['/install/schema.sql', '/install/patch_taxes.sql', '/install/seed_demo.sql'] as $needle) {
		$found = strpos($installer, $needle) !== false;
		echo ($found ? '[OK] ' : '[EKSIK] ') . 'Referans: ' . trim($needle, '/') . "\n";
		if (!$found) {
			++$errors;
		}
	}
}

$envPath = $root . '/config/env.php';
echo "\nKurulum durumu:\n";
if (is_file($envPath)) {
	echo "[BILGI] config/env.php mevcut — site ZATEN kurulu.\n";
	echo "        install/ klasörü günlük çalışma için gerekli değil.\n";
	echo "        Sihirbaz yalnızca env.php silinince tekrar açılır.\n";
} else {
	echo "[BILGI] config/env.php yok — /install/ sihirbazı çalışabilir.\n";
}

echo "\n" . str_repeat('=', 40) . "\n";
if ($errors === 0) {
	echo "Sonuç: install/ kurulum sihirbazı için YETERLI görünüyor.\n";
} else {
	echo "Sonuç: $errors zorunlu eksik/hata bulundu — güncel install/ klasörünü edinin.\n";
}
if ($warnings > 0) {
	echo "Not: $warnings isteğe bağlı dosya yok (patch_*.sql, README) — yeni kurulum için sorun değil.\n";
}
echo "\nGüvenlik: Canlı sitede kurulum tamamlandıysa /install/ web erişimini kapatın.\n";

exit($errors > 0 ? 1 : 0);
