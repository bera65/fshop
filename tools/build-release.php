<?php

/**
 * Publish zip: dist/frisay-{VERSION}.zip
 * CLI only: php tools/build-release.php
 */

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "CLI only\n");
	exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/core/FShop.php';

$version = FShop::VERSION;
$distDir = $root . DIRECTORY_SEPARATOR . 'dist';
$zipPath = $distDir . DIRECTORY_SEPARATOR . 'frisay-' . $version . '.zip';

if (!is_dir($distDir) && !mkdir($distDir, 0755, true)) {
	fwrite(STDERR, "dist/ oluşturulamadı\n");
	exit(1);
}

if (is_file($zipPath)) {
	unlink($zipPath);
}

$zip = new ZipArchive();

if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
	fwrite(STDERR, "ZIP açılamadı: {$zipPath}\n");
	exit(1);
}

$skipNames = [
	'.git' => true,
	'dist' => true,
	'.idea' => true,
	'.vscode' => true,
	'.cursor' => true,
];

$skipFiles = [
	'config/env.php' => true,
	'config/env0.php' => true,
	'config/installed.lock' => true,
	'config/debug.log' => true,
	'cache/news-feed.json' => true,
];

$emptyExceptIndex = [
	'cache/force/',
	'cache/cache/',
	'cache/pages/',
	'cache/updates/',
	'img/products/',
	'img/returns/',
	'img/invoices/',
	'storage/digital/',
	'logs/',
];

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
	RecursiveIteratorIterator::LEAVES_ONLY
);

$added = 0;

/** @var SplFileInfo $file */
foreach ($iterator as $file) {
	if (!$file->isFile()) {
		continue;
	}

	$full = str_replace('\\', '/', $file->getPathname());
	$rel = ltrim(substr($full, strlen(str_replace('\\', '/', $root))), '/');

	if ($rel === '' || strpos($rel, '../') !== false) {
		continue;
	}

	$top = explode('/', $rel, 2)[0];

	if (isset($skipNames[$top])) {
		continue;
	}

	if (isset($skipFiles[$rel])) {
		continue;
	}

	$base = basename($rel);

	if ($base === 'Thumbs.db' || $base === 'Desktop.ini' || substr($rel, -4) === '.bak') {
		continue;
	}

	$skipContent = false;

	foreach ($emptyExceptIndex as $prefix) {
		if (strpos($rel, $prefix) === 0 && $base !== 'index.php') {
			$skipContent = true;
			break;
		}
	}

	if ($skipContent) {
		continue;
	}

	if (!$zip->addFile($file->getPathname(), $rel)) {
		$zip->close();
		fwrite(STDERR, "Eklenemedi: {$rel}\n");
		exit(1);
	}

	$added++;
}

$zip->close();

if (!is_file($zipPath) || filesize($zipPath) < 1000) {
	fwrite(STDERR, "ZIP geçersiz veya çok küçük\n");
	exit(1);
}

$check = new ZipArchive();

if ($check->open($zipPath) !== true) {
	fwrite(STDERR, "ZIP doğrulanamadı\n");
	exit(1);
}

$hasIndex = $check->locateName('index.php') !== false;
$hasEnv = $check->locateName('config/env.php') !== false;
$check->close();

if (!$hasIndex) {
	fwrite(STDERR, "ZIP kökünde index.php yok\n");
	exit(1);
}

if ($hasEnv) {
	fwrite(STDERR, "ZIP config/env.php içeriyor — iptal\n");
	@unlink($zipPath);
	exit(1);
}

$mb = round(filesize($zipPath) / 1048576, 1);
echo "OK  {$zipPath}\n";
echo "{$added} dosya, {$mb} MB, sürüm {$version}\n";
exit(0);
