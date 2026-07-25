<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$env = require $root . '/config/env.php';

$host = (string) ($env['DB_HOST'] ?? 'localhost');
$port = 3306;

if (strpos($host, ':') !== false) {
	[$host, $portRaw] = explode(':', $host, 2);
	$port = (int) $portRaw;
}

$pdo = new PDO(
	'mysql:host=' . $host . ';port=' . $port . ';dbname=' . ($env['DB_NAME'] ?? '') . ';charset=utf8mb4',
	(string) ($env['DB_USER'] ?? ''),
	(string) ($env['DB_PASS'] ?? ''),
	[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$coreTables = [
	'settings', 'categories', 'brands', 'products', 'images', 'users', 'orders',
	'order_detail', 'favorites', 'contact_messages', 'user_addresses',
	'user_notifications', 'coupons', 'admins', 'modules', 'module_display_hooks', 'taxes',
];

$allTables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) ?: [];
$exportTables = array_values(array_intersect($coreTables, $allTables));

if ($exportTables === []) {
	fwrite(STDERR, "No core tables found.\n");
	exit(1);
}

$out = "-- FShop schema export " . date('Y-m-d') . "\n\n";

foreach ($exportTables as $table) {
	$row = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`')->fetch(PDO::FETCH_ASSOC);
	$create = (string) ($row['Create Table'] ?? '');

	if ($create === '') {
		continue;
	}

	$create = preg_replace('/ AUTO_INCREMENT=\d+/i', '', $create) ?? $create;
	$out .= $create . ";\n\n";
}

$schemaPath = $root . '/install/schema.sql';
file_put_contents($schemaPath, $out);
echo "Wrote {$schemaPath} (" . count($exportTables) . " tables)\n";
