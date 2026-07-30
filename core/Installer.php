<?php

class Installer
{
	public static function rootPath(): string
	{
		return dirname(__DIR__);
	}

	public static function lockPath(): string
	{
		return self::rootPath() . '/config/installed.lock';
	}

	public static function isInstalled(): bool
	{
		return is_file(self::rootPath() . '/config/env.php');
	}

	public static function requirements(): array
	{
		$checks = [
			[
				'label' => 'PHP 7.4+',
				'ok' => version_compare(PHP_VERSION, '7.4.0', '>='),
				'hint' => 'You: ' . PHP_VERSION,
			],
			[
				'label' => 'PDO MySQL',
				'ok' => extension_loaded('pdo_mysql'),
				'hint' => 'The pdo_mysql extension is required.',
			],
			[
				'label' => 'mbstring',
				'ok' => extension_loaded('mbstring'),
				'hint' => 'The mbstring extension is required.',
			],
			[
				'label' => 'GD',
				'ok' => extension_loaded('gd'),
				'hint' => 'GD is required for product image processing.',
			],
			[
				'label' => 'config/ writable',
				'ok' => is_writable(self::rootPath() . '/config'),
				'hint' => 'env.php and the installation lock must be created',
			],
			[
				'label' => 'cache/ writable',
				'ok' => is_writable(self::rootPath() . '/cache'),
				'hint' => 'Required for Smarty cache',
			],
			[
				'label' => 'img/products/ writable',
				'ok' => is_dir(self::rootPath() . '/img/products') && is_writable(self::rootPath() . '/img/products'),
				'hint' => 'Product images are required',
			],
		];

		$ok = true;

		foreach ($checks as $check) {
			if (!$check['ok']) {
				$ok = false;
				break;
			}
		}

		return ['ok' => $ok, 'items' => $checks];
	}

	public static function testDatabase(array $config): array
	{
		try {
			$pdo = self::pdo($config, false);
			$pdo->query('SELECT 1');

			return ['success' => true, 'message' => 'Database connection successful'];
		} catch (Throwable $e) {
			return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
		}
	}

	public static function install(array $data): array
	{
		if (self::isInstalled()) {
			return ['success' => false, 'message' => 'The system is already installed'];
		}

		$dbHost = trim((string) ($data['db_host'] ?? 'localhost'));
		$dbName = trim((string) ($data['db_name'] ?? ''));
		$dbUser = trim((string) ($data['db_user'] ?? ''));
		$dbPass = (string) ($data['db_pass'] ?? '');
		$siteName = trim((string) ($data['site_name'] ?? 'FriSay'));
		$siteUrl = rtrim(trim((string) ($data['site_url'] ?? '')), '/') . '/';
		$rewriteBase = trim((string) ($data['rewrite_base'] ?? '/'));
		$adminName = trim((string) ($data['admin_name'] ?? 'Admin'));
		$adminEmail = trim((string) ($data['admin_email'] ?? ''));
		$adminPass = (string) ($data['admin_password'] ?? '');
		$withDemo = !empty($data['install_demo']);
		$shopLang = strtolower(trim((string) ($data['shop_lang'] ?? 'en')));
		$adminLang = strtolower(trim((string) ($data['admin_lang'] ?? 'en')));
		$theme = trim((string) ($data['theme'] ?? 'blue'));

		if (!in_array($shopLang, ['tr', 'en'], true)) {
			$shopLang = 'en';
		}

		if (!in_array($adminLang, ['tr', 'en'], true)) {
			$adminLang = 'en';
		}

		if (!in_array($theme, ['shopmore'], true)) {
			$theme = 'shopmore';
		}

		if ($dbName === '' || $dbUser === '') {
			return ['success' => false, 'message' => 'Database name and user are required.'];
		}

		if ($siteUrl === '/' || !filter_var($siteUrl, FILTER_VALIDATE_URL)) {
			return ['success' => false, 'message' => 'Enter a valid site address (including https)'];
		}

		if ($adminEmail === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
			return ['success' => false, 'message' => 'Enter a valid admin email address.'];
		}

		if (strlen($adminPass) < 8) {
			return ['success' => false, 'message' => 'The admin password must be at least 8 characters long.'];
		}

		if ($rewriteBase === '') {
			$rewriteBase = '/';
		}

		if ($rewriteBase[0] !== '/') {
			$rewriteBase = '/' . $rewriteBase;
		}

		if ($rewriteBase !== '/' && substr($rewriteBase, -1) !== '/') {
			$rewriteBase .= '/';
		}

		$dbConfig = [
			'db_host' => $dbHost,
			'db_name' => $dbName,
			'db_user' => $dbUser,
			'db_pass' => $dbPass,
		];

		$test = self::testDatabase($dbConfig);

		if (!$test['success']) {
			return $test;
		}

		try {
			$pdo = self::pdo($dbConfig, true);
			self::runSqlFile($pdo, self::rootPath() . '/install/schema.sql');
			self::runSqlFileIfExists($pdo, self::rootPath() . '/install/patch_taxes.sql');

			if ($withDemo) {
				self::runSqlFile($pdo, self::rootPath() . '/install/seed_demo.sql');
			}

			$shopToken = bin2hex(random_bytes(16));
			$webApiKey = bin2hex(random_bytes(32));
			$folder = $rewriteBase;

			self::setSetting($pdo, 'DOMAIN', $siteUrl);
			self::setSetting($pdo, 'FOLDER', $folder);
			self::setSetting($pdo, 'SITE_NAME', $siteName);
			self::setSetting($pdo, 'SHOP_TOKEN', $shopToken);
			self::setSetting($pdo, 'WEBAPI_ENABLED', '1');
			self::setSetting($pdo, 'WEBAPI_KEY', $webApiKey);
			self::setSetting($pdo, 'THEME', $theme);
			self::setSetting($pdo, 'DEFAULT_LANG', $shopLang);
			self::setSetting($pdo, 'SHOP_LANGUAGES', 'tr,en');
			self::setSetting($pdo, 'ADMIN_DEFAULT_LANG', $adminLang);
			self::setSetting($pdo, 'PRODUCT_LIMIT', '5000');
			self::setSetting($pdo, 'FREE_SHIPPING_MIN', '500');
			self::setSetting($pdo, 'SHIPPING_FEE', '79.90');
			self::setSetting($pdo, 'HAVALE', '3');
			self::setSetting($pdo, 'CARGO_DAY', '3');
			self::setSetting($pdo, 'CONTACT_EMAIL', $adminEmail);
			self::setSetting($pdo, 'CONTACT_PHONE', '0555 000 00 00');
			self::setSetting($pdo, 'CONTACT_PHONE_TEL', '+905550000000');
			self::setSetting($pdo, 'MAIL_DRIVER', 'php');

			$adminHash = password_hash($adminPass, PASSWORD_DEFAULT);
			$stmt = $pdo->prepare('UPDATE admins SET full_name = ?, email = ?, password = ?, active = 1 WHERE id_admin = 1');
			$stmt->execute([$adminName, $adminEmail, $adminHash]);

			if ($stmt->rowCount() === 0) {
				$insert = $pdo->prepare('INSERT INTO admins (full_name, email, password, active) VALUES (?, ?, ?, 1)');
				$insert->execute([$adminName, $adminEmail, $adminHash]);
			}

			self::writeEnv([
				'APP_ENV' => 'production',
				'APP_DEBUG' => false,
				'DB_HOST' => $dbHost,
				'DB_NAME' => $dbName,
				'DB_USER' => $dbUser,
				'DB_PASS' => $dbPass,
				'REWRITE_BASE' => $rewriteBase,
				'ADMIN_URI' => 'admin',
			]);

			self::updateRewriteBase($rewriteBase);

			if (is_file(self::rootPath() . '/core/Admin.php')) {
				require_once self::rootPath() . '/config/app.php';
				require_once self::rootPath() . '/core/Admin.php';

				if (class_exists('App', false)) {
					App::boot();
				}

				Admin::syncHtaccessRewrite();
			}
			file_put_contents(self::lockPath(), date('c') . PHP_EOL);

			if ($withDemo) {
				self::refreshDemoCurrencyPrices($dbConfig);
			}

			return [
				'success' => true,
				'message' => 'Installation complete.',
				'admin_email' => $adminEmail,
				'shop_token' => $shopToken,
				'cron_url' => rtrim($siteUrl, '/') . str_replace('//', '/', $folder . 'api/cron.php?action=currency&token=' . $shopToken),
			];
		} catch (Throwable $e) {
			return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
		}
	}

	private static function pdo(array $config, bool $useDatabase): PDO
	{
		$host = $config['db_host'] ?? 'localhost';
		$name = $config['db_name'] ?? '';
		$user = $config['db_user'] ?? '';
		$pass = $config['db_pass'] ?? '';
		$dsn = $useDatabase
			? 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4'
			: 'mysql:host=' . $host . ';charset=utf8mb4';

		$pdo = new PDO($dsn, $user, $pass, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		]);

		$pdo->exec("SET NAMES utf8mb4");

		return $pdo;
	}

	private static function runSqlFile(PDO $pdo, string $path): void
	{
		if (!is_file($path)) {
			throw new RuntimeException('SQL file not found: ' . $path);
		}

		self::executeSqlStatements($pdo, (string) file_get_contents($path));
	}

	private static function runSqlFileIfExists(PDO $pdo, string $path): void
	{
		if (!is_file($path)) {
			return;
		}

		self::executeSqlStatements($pdo, (string) file_get_contents($path));
	}

	private static function executeSqlStatements(PDO $pdo, string $sql): void
	{
		$sql = preg_replace('/^\s*--.*$/m', '', $sql);
		$statements = preg_split('/;\s*[\r\n]+/', $sql);

		foreach ($statements as $statement) {
			$statement = trim($statement);

			if ($statement === '') {
				continue;
			}

			$pdo->exec($statement);
		}
	}

	private static function setSetting(PDO $pdo, string $title, string $value): void
	{
		$stmt = $pdo->prepare('SELECT id FROM settings WHERE title = ? LIMIT 1');
		$stmt->execute([$title]);
		$row = $stmt->fetch();

		if ($row) {
			$update = $pdo->prepare('UPDATE settings SET value = ? WHERE title = ?');
			$update->execute([$value, $title]);

			return;
		}

		$insert = $pdo->prepare('INSERT INTO settings (title, value) VALUES (?, ?)');
		$insert->execute([$title, $value]);
	}

	private static function writeEnv(array $env): void
	{
		$path = self::rootPath() . '/config/env.php';
		$export = var_export($env, true);
		$content = "<?php\nreturn " . $export . ";\n";

		if (file_put_contents($path, $content) === false) {
			throw new RuntimeException('config/env.php could not be written');
		}
	}

	private static function updateRewriteBase(string $rewriteBase): void
	{
		$htaccess = self::rootPath() . '/.htaccess';

		if (!is_file($htaccess) || !is_writable($htaccess)) {
			return;
		}

		$content = file_get_contents($htaccess);
		$content = preg_replace(
			'/RewriteBase\s+.+$/m',
			'RewriteBase ' . $rewriteBase,
			$content,
			1,
			$count
		);

		if ($count === 0) {
			return;
		}

		file_put_contents($htaccess, $content);
	}

	private static function refreshDemoCurrencyPrices(array $dbConfig): void
	{
		require_once self::rootPath() . '/config/function.php';
		require_once self::rootPath() . '/config/database.php';
		require_once self::rootPath() . '/core/Product.php';

		global $db;
		$host = $dbConfig['db_host'] ?? 'localhost';
		$name = $dbConfig['db_name'] ?? '';
		$user = $dbConfig['db_user'] ?? '';
		$pass = $dbConfig['db_pass'] ?? '';

		try {
			$db = new PDO(
				'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4',
				$user,
				$pass,
				[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
			);
			Product::refreshCurrencyPrices();
		} catch (Throwable $e) {
			// Demo kurulumda kur API erişilemezse ürünler kayıt sonrası cron ile güncellenir.
		}
	}
}
