<?php

/**
 * Zip-slip / malicious-archive regression checks for Theme ZIP and Backup Pro restore.
 * CLI only: php tools/zip-security-regression.php
 */

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "CLI only\n");
	exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/core/Theme.php';
require_once $root . '/modules/backup-pro/src/Autoloader.php';
BackupPro\Autoloader::register();

$failed = 0;
$passed = 0;

function check($cond, string $msg): void
{
	global $failed, $passed;
	if ($cond) {
		$passed++;
		echo "OK  {$msg}\n";
		return;
	}

	$failed++;
	echo "FAIL  {$msg}\n";
}

function makeZip(string $path, array $entries): void
{
	if (is_file($path)) {
		unlink($path);
	}

	$zip = new ZipArchive();
	if ($zip->open($path, ZipArchive::CREATE) !== true) {
		throw new RuntimeException('zip create failed: ' . $path);
	}

	foreach ($entries as $name => $body) {
		if (is_array($body) && !empty($body['symlink'])) {
			$zip->addFromString($name, (string) ($body['content'] ?? ''));
			$opsys = defined('ZipArchive::OPSYS_UNIX') ? ZipArchive::OPSYS_UNIX : 3;
			$zip->setExternalAttributesName($name, $opsys, 0120000 << 16);
			continue;
		}

		$zip->addFromString($name, (string) $body);
	}

	$zip->close();
}

function rrmdir(string $dir): void
{
	if (!is_dir($dir)) {
		return;
	}

	$it = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ($it as $item) {
		if ($item->isDir()) {
			@rmdir($item->getPathname());
		} else {
			@unlink($item->getPathname());
		}
	}

	@rmdir($dir);
}

$isSafe = new ReflectionMethod('Theme', 'isSafeZipEntry');
$isSafe->setAccessible(true);
$extract = new ReflectionMethod('Theme', 'extractSafeThemeEntries');
$extract->setAccessible(true);

$allow = [
	'header.tpl',
	'footer.tpl',
	'css/style.css',
	'js/app.js',
	'img/logo.png',
	'fonts/site.woff2',
	'theme.schema.json',
	'css/',
];

foreach ($allow as $entry) {
	check($isSafe->invoke(null, $entry, '') === true, "theme allowlist accepts {$entry}");
}

check($isSafe->invoke(null, 'mytheme/header.tpl', 'mytheme/') === true, 'theme allowlist accepts prefixed header.tpl');

$deny = [
	'../shell.php',
	'../../templates/admin/x.tpl',
	'/etc/passwd',
	'C:/Windows/win.ini',
	'//server/share/x.tpl',
	'shell.php',
	'shell.phtml',
	'shell.phar',
	'.htaccess',
	'web.config',
	'foo/../../../etc/passwd',
	"header.tpl\0.php",
	'index.php',
	'css/../../evil.tpl',
	'bin/run.exe',
	'cgi/script.cgi',
	'shell.php:.tpl',
	'css/shell.php:.css',
	'img/x.php:.png',
];

foreach ($deny as $entry) {
	check($isSafe->invoke(null, $entry, '') === false, "theme rejects {$entry}");
}

check($isSafe->invoke(null, 'mytheme/../shell.php', 'mytheme/') === false, 'theme rejects traversal after prefix');
check($isSafe->invoke(null, 'other/header.tpl', 'mytheme/') === false, 'theme rejects entry outside theme root');

$work = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fshop-zip-reg-' . bin2hex(random_bytes(4));
mkdir($work, 0755, true);
$zipPath = $work . DIRECTORY_SEPARATOR . 'theme.zip';
$tempTheme = $work . DIRECTORY_SEPARATOR . 'out';
mkdir($tempTheme, 0755, true);

makeZip($zipPath, [
	'header.tpl' => '<html>',
	'footer.tpl' => '</html>',
	'css/style.css' => 'body{}',
	'js/app.js' => 'void 0;',
	'theme.schema.json' => '{}',
	'img/logo.png' => 'png',
]);

$zip = new ZipArchive();
$zip->open($zipPath);
$err = $extract->invoke(null, $zip, '', $tempTheme);
$zip->close();
check($err === null, 'legitimate theme zip extracts');
check(is_file($tempTheme . '/header.tpl') && is_file($tempTheme . '/css/style.css'), 'legitimate theme files written inside temp');

$probe = dirname($tempTheme) . DIRECTORY_SEPARATOR . 'fshop-zipslip-probe.php';
@unlink($probe);
rrmdir($tempTheme);
mkdir($tempTheme, 0755, true);
makeZip($zipPath, [
	'header.tpl' => '<html>',
	'footer.tpl' => '</html>',
	'../fshop-zipslip-probe.php' => '<?php echo "pwn";',
]);
$zip = new ZipArchive();
$zip->open($zipPath);
$err = $extract->invoke(null, $zip, '', $tempTheme);
$zip->close();
check($err !== null, 'theme zip-slip entry is rejected');
check(!is_file($probe), 'theme zip-slip did not write outside temp');
check(!is_file($tempTheme . '/fshop-zipslip-probe.php'), 'theme zip-slip did not land in temp as php');

rrmdir($tempTheme);
mkdir($tempTheme, 0755, true);
makeZip($zipPath, [
	'header.tpl' => '<html>',
	'footer.tpl' => '</html>',
	'shell.php' => '<?php echo 1;',
]);
$zip = new ZipArchive();
$zip->open($zipPath);
$err = $extract->invoke(null, $zip, '', $tempTheme);
$zip->close();
check($err !== null, 'theme rejects executable php member');
check(!is_file($tempTheme . '/shell.php'), 'theme did not write shell.php');

rrmdir($tempTheme);
mkdir($tempTheme, 0755, true);
makeZip($zipPath, [
	'header.tpl' => '<html>',
	'footer.tpl' => '</html>',
	'/tmp/abs.tpl' => 'x',
]);
$zip = new ZipArchive();
$zip->open($zipPath);
$err = $extract->invoke(null, $zip, '', $tempTheme);
$zip->close();
check($err !== null, 'theme rejects absolute zip path');

rrmdir($tempTheme);
mkdir($tempTheme, 0755, true);
makeZip($zipPath, [
	'header.tpl' => '<html>',
	'footer.tpl' => '</html>',
	'link.tpl' => ['symlink' => true, 'content' => '/etc/passwd'],
]);
$zip = new ZipArchive();
$zip->open($zipPath);
$err = $extract->invoke(null, $zip, '', $tempTheme);
$zip->close();
check($err !== null, 'theme rejects symlink zip entry');

rrmdir($tempTheme);
mkdir($tempTheme, 0755, true);
makeZip($zipPath, [
	'header.tpl' => '<html>',
	'footer.tpl' => '</html>',
	'shell.php:.tpl' => '<?php echo 123;',
]);
$zip = new ZipArchive();
$zip->open($zipPath);
$err = $extract->invoke(null, $zip, '', $tempTheme);
$zip->close();
check($err !== null, 'theme rejects NTFS ADS zip entry');
check(!is_file($tempTheme . '/shell.php'), 'theme ADS entry did not create shell.php');

$restore = new BackupPro\Service\RestoreService();
$storage = new BackupPro\Service\StorageService();
$projectRoot = BackupPro\Service\StorageService::getProjectRoot();
$projectProbe = $projectRoot . DIRECTORY_SEPARATOR . 'fshop-zipslip-probe.php';
@unlink($projectProbe);

$backupZip = $work . DIRECTORY_SEPARATOR . 'backup.zip';
makeZip($backupZip, [
	'database.sql' => "SELECT 1;\n",
	'../fshop-zipslip-probe.php' => '<?php echo "pwn";',
	'index.php' => '<?php echo "pwn";',
	'core/Product.php' => '<?php echo "pwn";',
]);

$extracted = $restore->restoreZipChunk($backupZip, 0, 100000, $projectRoot);
check($extracted === 0, 'restoreZipChunk extracts zero files into project root');
check(!is_file($projectProbe), 'restore zip-slip did not write project probe');
check(!is_file($projectRoot . DIRECTORY_SEPARATOR . 'fshop-zipslip-restore-index.php'), 'restore did not write extra php (control)');

$staging = $storage->createRestoreStagingDir();
check($staging !== null && is_dir($staging), 'restore staging dir created');
$stagingNorm = str_replace('\\', '/', (string) $staging);
$projectNorm = str_replace('\\', '/', $projectRoot);
$tempBase = BackupPro\Service\StorageService::getRestoreStagingBase();
$tempNorm = str_replace('\\', '/', (string) $tempBase);
check($tempBase !== null && strpos($stagingNorm, rtrim($tempNorm, '/') . '/') === 0, 'restore staging is under sys_get_temp_dir()');
check(strpos($stagingNorm, rtrim($projectNorm, '/') . '/') !== 0, 'restore staging is outside project/docroot');
check(strpos($stagingNorm, '/backups/') === false, 'restore staging is not under backups/');
check((bool) preg_match('/fshop-restore-[a-f0-9]{32}$/', basename($staging)), 'restore staging name is unique fshop-restore-*');

$staging2 = $storage->createRestoreStagingDir();
check($staging2 !== null && is_dir($staging2) && $staging2 !== $staging, 'concurrent restore staging dirs are isolated');

$dump = $restore->extractVerifiedSqlDump($backupZip, $staging);
check(is_string($dump) && is_file($dump), 'verified dump extracted');
check(basename($dump) === 'database.sql', 'dump basename is database.sql');
check(strpos(file_get_contents($dump), 'SELECT 1') !== false, 'dump contents come from database.sql');
check($restore->isVerifiedSqlDumpPath($dump) === true, 'staging database.sql is accepted as restore dump');
check(!is_file($staging . DIRECTORY_SEPARATOR . 'index.php'), 'staging has no index.php from zip');
check(!is_file($staging . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Product.php'), 'staging has no app php from zip');
check(!is_file($projectProbe), 'extractVerifiedSqlDump did not zip-slip into project');
check($restore->extractVerifiedSqlDump($backupZip, $staging2) !== null && !is_file($staging2 . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Product.php'), 'second staging only receives dump, not app files');

$nestedZip = $work . DIRECTORY_SEPARATOR . 'nested-dump.zip';
makeZip($nestedZip, [
	'foo/database.sql' => 'DROP TABLE users;',
	'../database.sql' => 'DROP TABLE users;',
]);
$dumpNested = $restore->extractVerifiedSqlDump($nestedZip, $staging);
check($dumpNested === null, 'nested or traversed dump names are not accepted');

$outsideSql = $work . DIRECTORY_SEPARATOR . 'database.sql';
file_put_contents($outsideSql, "SELECT 1;\n");
check($restore->isVerifiedSqlDumpPath($outsideSql) === false, 'SQL outside restore staging is rejected');

$schema = $projectRoot . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'schema.sql';
if (is_file($schema)) {
	check($restore->isVerifiedSqlDumpPath($schema) === false, 'install/schema.sql is not a restore dump');
}

$backupDir = rtrim($storage->getBackupDir(), '/\\');
$planted = $backupDir . DIRECTORY_SEPARATOR . 'database.sql';
$plantedExisted = is_file($planted);
file_put_contents($planted, "SELECT 1;\n");
check($restore->isVerifiedSqlDumpPath($planted) === false, 'backups/database.sql is not a restore dump');
check($restore->executeSqlDumpChunk($planted) === false, 'executeSqlDumpChunk refuses backups/database.sql');
if (!$plantedExisted) {
	@unlink($planted);
}

$oldStyle = $backupDir . DIRECTORY_SEPARATOR . 'restore-staging-' . bin2hex(random_bytes(4));
@mkdir($oldStyle, 0755, true);
if (is_dir($oldStyle)) {
	check($restore->extractVerifiedSqlDump($backupZip, $oldStyle) === null, 'extract refuses backups/restore-staging-* as staging');
	@rmdir($oldStyle);
}

check($restore->executeSqlDumpChunk($outsideSql) === false, 'executeSqlDumpChunk refuses SQL outside restore staging');

$storage->removeRestoreStagingDir($staging);
$storage->removeRestoreStagingDir($staging2);
check(!is_dir($staging), 'restore staging removed');
check(!is_dir($staging2), 'second restore staging removed');

rrmdir($work);
@unlink($projectProbe);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
