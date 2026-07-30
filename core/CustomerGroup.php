<?php

class CustomerGroup
{
	private static bool $schemaReady = false;

	public static function ensureSchema(): void
	{
		if (self::$schemaReady) {
			return;
		}

		self::$schemaReady = true;

		DB::execute(
			'CREATE TABLE IF NOT EXISTS `customer_groups` (
				`id_group` int(11) NOT NULL AUTO_INCREMENT,
				`name` varchar(64) NOT NULL DEFAULT \'\',
				`discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
				`is_default` tinyint(1) NOT NULL DEFAULT 0,
				`active` tinyint(1) NOT NULL DEFAULT 1,
				PRIMARY KEY (`id_group`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		);

		$idGroupCol = DB::execute("SHOW COLUMNS FROM `users` LIKE 'id_group'");

		if (empty($idGroupCol)) {
			DB::execute(
				"ALTER TABLE `users` ADD COLUMN `id_group` int(11) NOT NULL DEFAULT 0 AFTER `active`"
			);
		}

		if ((int) DB::getValue('SELECT COUNT(*) FROM `customer_groups`') === 0) {
			DB::insert('customer_groups', [
				'name' => 'Default',
				'discount_percent' => 0,
				'is_default' => 1,
				'active' => 1,
			]);
		}

		$defaultId = self::getDefaultId();

		if ($defaultId > 0) {
			DB::execute(
				'UPDATE users SET id_group = ? WHERE id_group = 0 OR id_group IS NULL',
				[$defaultId]
			);
		}
	}

	public static function getDefaultId(): int
	{
		self::ensureSchema();

		return (int) DB::getValue(
			'SELECT id_group FROM customer_groups WHERE is_default = 1 ORDER BY id_group ASC LIMIT 1'
		);
	}

	/** @return array<int, array<string, mixed>> */
	public static function getAdminList(): array
	{
		self::ensureSchema();
		$rows = DB::execute(
			'SELECT g.*,
				(SELECT COUNT(*) FROM users u WHERE u.id_group = g.id_group) AS member_count
			 FROM customer_groups g
			 ORDER BY g.is_default DESC, g.name ASC'
		) ?: [];

		return array_map([self::class, 'normalizeRow'], $rows);
	}

	/** @return array<int, array<string, mixed>> */
	public static function getActiveOptions(): array
	{
		self::ensureSchema();
		$rows = DB::execute(
			'SELECT * FROM customer_groups WHERE active = 1 ORDER BY is_default DESC, name ASC'
		) ?: [];

		return array_map([self::class, 'normalizeRow'], $rows);
	}

	public static function getById(int $idGroup): ?array
	{
		self::ensureSchema();

		if ($idGroup <= 0) {
			return null;
		}

		$row = DB::getRowSafe('customer_groups', 'id_group = ?', [$idGroup]);

		return $row ? self::normalizeRow($row) : null;
	}

	public static function getDiscountPercentForUser(int $idUser): float
	{
		self::ensureSchema();

		if ($idUser <= 0) {
			return 0.0;
		}

		$idGroup = (int) DB::getValue('SELECT id_group FROM users WHERE id_user = ? LIMIT 1', [$idUser]);

		if ($idGroup <= 0) {
			$idGroup = self::getDefaultId();
		}

		$group = self::getById($idGroup);

		if (!$group || empty($group['active'])) {
			return 0.0;
		}

		return max(0.0, min(100.0, (float) $group['discount_percent']));
	}

	public static function add(string $name, float $discountPercent, bool $active = true): array
	{
		self::ensureSchema();

		$name = trim(strip_tags($name));
		$discountPercent = round(max(0, min(100, $discountPercent)), 2);

		if ($name === '' || Tools::strlen($name) > 64) {
			return self::fail(adminT('Enter a valid group name'));
		}

		$id = DB::insert('customer_groups', [
			'name' => $name,
			'discount_percent' => $discountPercent,
			'is_default' => 0,
			'active' => $active ? 1 : 0,
		]);

		if (!$id) {
			return self::fail(adminT('Could not create group'));
		}

		return self::ok(adminT('Group created'));
	}

	public static function update(int $idGroup, string $name, float $discountPercent, bool $active = true): array
	{
		self::ensureSchema();

		$group = self::getById($idGroup);

		if (!$group) {
			return self::fail(adminT('Group not found'));
		}

		$name = trim(strip_tags($name));
		$discountPercent = round(max(0, min(100, $discountPercent)), 2);

		if ($name === '' || Tools::strlen($name) > 64) {
			return self::fail(adminT('Enter a valid group name'));
		}

		if (!empty($group['is_default'])) {
			$active = true;
			$discountPercent = 0.0;
		}

		$updated = DB::update(
			'customer_groups',
			[
				'name' => $name,
				'discount_percent' => $discountPercent,
				'active' => $active ? 1 : 0,
			],
			'id_group = :id_group',
			['id_group' => $idGroup]
		);

		if ($updated === false) {
			return self::fail(adminT('Could not update group'));
		}

		return self::ok(adminT('Group updated'));
	}

	public static function delete(int $idGroup): array
	{
		self::ensureSchema();

		$group = self::getById($idGroup);

		if (!$group) {
			return self::fail(adminT('Group not found'));
		}

		if (!empty($group['is_default'])) {
			return self::fail(adminT('Default group cannot be deleted'));
		}

		$defaultId = self::getDefaultId();

		DB::execute('UPDATE users SET id_group = ? WHERE id_group = ?', [$defaultId, $idGroup]);
		DB::execute('DELETE FROM customer_groups WHERE id_group = ?', [$idGroup]);

		return self::ok(adminT('Group deleted'));
	}

	public static function setDefault(int $idGroup): array
	{
		self::ensureSchema();

		$group = self::getById($idGroup);

		if (!$group) {
			return self::fail(adminT('Group not found'));
		}

		DB::execute('UPDATE customer_groups SET is_default = 0');
		DB::update(
			'customer_groups',
			[
				'is_default' => 1,
				'active' => 1,
				'discount_percent' => 0,
			],
			'id_group = :id_group',
			['id_group' => $idGroup]
		);

		return self::ok(adminT('Default group updated'));
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	private static function normalizeRow(array $row): array
	{
		$row['id_group'] = (int) ($row['id_group'] ?? 0);
		$row['name'] = (string) ($row['name'] ?? '');
		$row['discount_percent'] = round((float) ($row['discount_percent'] ?? 0), 2);
		$row['is_default'] = (int) ($row['is_default'] ?? 0) === 1;
		$row['active'] = (int) ($row['active'] ?? 0) === 1;
		$row['member_count'] = (int) ($row['member_count'] ?? 0);

		return $row;
	}

	/** @return array{success:bool,message:string} */
	private static function ok(string $message): array
	{
		return ['success' => true, 'message' => $message];
	}

	/** @return array{success:bool,message:string} */
	private static function fail(string $message): array
	{
		return ['success' => false, 'message' => $message];
	}
}
