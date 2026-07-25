<?php

class ApiKey
{
	public const PERM_ORDERS_READ = 'orders.read';
	public const PERM_ORDERS_WRITE = 'orders.write';
	public const PERM_PRODUCTS_READ = 'products.read';
	public const PERM_PRODUCTS_CREATE = 'products.create';
	public const PERM_PRODUCTS_UPDATE = 'products.update';
	public const PERM_PRODUCTS_DELETE = 'products.delete';
	public const PERM_CATEGORIES_READ = 'categories.read';
	public const PERM_BRANDS_READ = 'brands.read';

	/** @var array{id:int,name:string,permissions:array<int,string>}|null */
	private static $current = null;

	public static function ensureSchema(): void
	{
		$table = DB::execute("SHOW TABLES LIKE 'api_keys'");

		if (empty($table)) {
			DB::execute(
				"CREATE TABLE `api_keys` (
					`id_api_key` int(11) NOT NULL AUTO_INCREMENT,
					`name` varchar(128) NOT NULL,
					`api_key` varchar(128) NOT NULL,
					`permissions` text NOT NULL,
					`active` tinyint(1) NOT NULL DEFAULT 1,
					`last_used_at` datetime DEFAULT NULL,
					`date_add` datetime NOT NULL,
					`date_upd` datetime NOT NULL,
					PRIMARY KEY (`id_api_key`),
					UNIQUE KEY `api_key` (`api_key`),
					KEY `active` (`active`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}

		self::migrateLegacyKey();
	}

	/** Eski tek WEBAPI_KEY ayarını çoklu tabloya aktarır */
	private static function migrateLegacyKey(): void
	{
		$count = (int) (DB::getValue('SELECT COUNT(*) FROM api_keys') ?: 0);

		if ($count > 0) {
			return;
		}

		$legacy = trim((string) Settings::get('WEBAPI_KEY'));

		if ($legacy === '') {
			return;
		}

		$now = date('Y-m-d H:i:s');
		DB::insert('api_keys', [
			'name' => 'Varsayılan',
			'api_key' => $legacy,
			'permissions' => json_encode(array_keys(self::permissionCatalog()), JSON_UNESCAPED_UNICODE),
			'active' => Settings::get('WEBAPI_ENABLED') === '1' ? 1 : 0,
			'date_add' => $now,
			'date_upd' => $now,
		]);
	}

	/**
	 * @return array<string, string> permission => label
	 */
	public static function permissionCatalog(): array
	{
		return [
			self::PERM_ORDERS_READ => self::t('Read orders / retrieve'),
			self::PERM_ORDERS_WRITE => self::t('Update orders'),
			self::PERM_PRODUCTS_READ => self::t('Read products'),
			self::PERM_PRODUCTS_CREATE => self::t('Create products'),
			self::PERM_PRODUCTS_UPDATE => self::t('Update products'),
			self::PERM_PRODUCTS_DELETE => self::t('Delete products'),
			self::PERM_CATEGORIES_READ => self::t('Read categories'),
			self::PERM_BRANDS_READ => self::t('Read brands'),
		];
	}

	/** @return array<int, string> */
	public static function allPermissionKeys(): array
	{
		return array_keys(self::permissionCatalog());
	}

	public static function generateKey(): string
	{
		return bin2hex(random_bytes(32));
	}

	/**
	 * @param array<int, string> $permissions
	 * @return array{ok:bool,message:string,id?:int,api_key?:string}
	 */
	public static function create(string $name, array $permissions, bool $active = true): array
	{
		self::ensureSchema();

		$name = trim($name);

		if ($name === '') {
			return ['ok' => false, 'message' => self::t('Partner / name required')];
		}

		$permissions = self::sanitizePermissions($permissions);

		if ($permissions === []) {
			return ['ok' => false, 'message' => self::t('Select at least one permission.')];
		}

		$key = self::generateKey();
		$now = date('Y-m-d H:i:s');

		DB::insert('api_keys', [
			'name' => mb_substr($name, 0, 128),
			'api_key' => $key,
			'permissions' => json_encode($permissions, JSON_UNESCAPED_UNICODE),
			'active' => $active ? 1 : 0,
			'date_add' => $now,
			'date_upd' => $now,
		]);

		$id = (int) (DB::getValue('SELECT id_api_key FROM api_keys WHERE api_key = ? LIMIT 1', [$key]) ?: 0);

		if (Settings::get('WEBAPI_ENABLED') !== '1') {
			Settings::set('WEBAPI_ENABLED', '1');
		}

		return [
			'ok' => true,
			'message' => self::t('API key created'),
			'id' => $id,
			'api_key' => $key,
		];
	}

	/**
	 * @param array<int, string> $permissions
	 * @return array{ok:bool,message:string}
	 */
	public static function update(int $id, string $name, array $permissions, bool $active): array
	{
		self::ensureSchema();

		if ($id <= 0 || !self::getById($id)) {
			return ['ok' => false, 'message' => self::t('Not found')];
		}

		$name = trim($name);

		if ($name === '') {
			return ['ok' => false, 'message' => self::t('Partner / name required')];
		}

		$permissions = self::sanitizePermissions($permissions);

		if ($permissions === []) {
			return ['ok' => false, 'message' => self::t('Select at least one permission.')];
		}

		DB::update('api_keys', [
			'name' => mb_substr($name, 0, 128),
			'permissions' => json_encode($permissions, JSON_UNESCAPED_UNICODE),
			'active' => $active ? 1 : 0,
			'date_upd' => date('Y-m-d H:i:s'),
		], 'id_api_key = :where_id', ['where_id' => $id]);

		return ['ok' => true, 'message' => self::t('API key updated')];
	}

	/** @return array{ok:bool,message:string,api_key?:string} */
	public static function regenerate(int $id): array
	{
		self::ensureSchema();

		if ($id <= 0 || !self::getById($id)) {
			return ['ok' => false, 'message' => self::t('Not found')];
		}

		$key = self::generateKey();

		DB::update('api_keys', [
			'api_key' => $key,
			'date_upd' => date('Y-m-d H:i:s'),
		], 'id_api_key = :where_id', ['where_id' => $id]);

		return ['ok' => true, 'message' => self::t('The key has been renewed'), 'api_key' => $key];
	}

	/** @return array{ok:bool,message:string} */
	public static function delete(int $id): array
	{
		self::ensureSchema();

		if ($id <= 0) {
			return ['ok' => false, 'message' => self::t('Invalid record')];
		}

		DB::execute('DELETE FROM api_keys WHERE id_api_key = ?', [$id]);

		return ['ok' => true, 'message' => self::t('API key deleted')];
	}

	/** @return array<string, mixed>|null */
	public static function getById(int $id): ?array
	{
		self::ensureSchema();

		if ($id <= 0) {
			return null;
		}

		$row = DB::getRowSafe('api_keys', 'id_api_key = ?', [$id]);

		return is_array($row) ? self::hydrate($row) : null;
	}

	/** @return array<string, mixed>|null */
	public static function findByKey(string $key): ?array
	{
		self::ensureSchema();

		$key = trim($key);

		if ($key === '') {
			return null;
		}

		$row = DB::getRowSafe('api_keys', 'api_key = ?', [$key]);

		return is_array($row) ? self::hydrate($row) : null;
	}

	/** @return array<int, array<string, mixed>> */
	public static function getList(): array
	{
		self::ensureSchema();

		$rows = DB::execute(
			'SELECT * FROM api_keys ORDER BY date_add DESC, id_api_key DESC'
		) ?: [];

		$out = [];

		foreach ($rows as $row) {
			$out[] = self::hydrate($row);
		}

		return $out;
	}

	/**
	 * @return array{ok:bool,message?:string,key?:array<string,mixed>}
	 */
	public static function authenticateProvidedKey(string $provided): array
	{
		self::ensureSchema();

		if (Settings::get('WEBAPI_ENABLED') !== '1') {
			return ['ok' => false, 'message' => self::t('Web API closed')];
		}

		$row = self::findByKey($provided);

		if (!$row) {
			return ['ok' => false, 'message' => self::t('Invalid API Key')];
		}

		if (empty($row['active'])) {
			return ['ok' => false, 'message' => self::t('This key is disabled')];
		}

		DB::update('api_keys', [
			'last_used_at' => date('Y-m-d H:i:s'),
		], 'id_api_key = :where_id', ['where_id' => (int) $row['id_api_key']]);

		self::$current = [
			'id' => (int) $row['id_api_key'],
			'name' => (string) $row['name'],
			'permissions' => is_array($row['permissions']) ? $row['permissions'] : [],
		];

		return ['ok' => true, 'key' => $row];
	}

	public static function requirePermission(string $permission): void
	{
		$perms = self::$current['permissions'] ?? [];

		if (!in_array($permission, $perms, true)) {
			http_response_code(403);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode([
				'success' => false,
				'message' => self::t('You do not have permission for this operation: ') . $permission,
			], JSON_UNESCAPED_UNICODE);
			exit;
		}
	}

	public static function clearCurrent(): void
	{
		self::$current = null;
	}

	/**
	 * @param array<int, mixed> $permissions
	 * @return array<int, string>
	 */
	public static function sanitizePermissions(array $permissions): array
	{
		$allowed = self::allPermissionKeys();
		$out = [];

		foreach ($permissions as $perm) {
			$perm = (string) $perm;

			if (in_array($perm, $allowed, true) && !in_array($perm, $out, true)) {
				$out[] = $perm;
			}
		}

		return $out;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	private static function hydrate(array $row): array
	{
		$decoded = json_decode((string) ($row['permissions'] ?? '[]'), true);
		$row['permissions'] = is_array($decoded) ? self::sanitizePermissions($decoded) : [];
		$row['id_api_key'] = (int) ($row['id_api_key'] ?? 0);
		$row['active'] = (int) ($row['active'] ?? 0);
		$row['permission_labels'] = [];

		$catalog = self::permissionCatalog();

		foreach ($row['permissions'] as $perm) {
			$row['permission_labels'][] = $catalog[$perm] ?? $perm;
		}

		return $row;
	}
	private static function t(string $message): string
	{
		return function_exists('translate') ? translate($message) : $message;
	}
}
