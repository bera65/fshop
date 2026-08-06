<?php

class Supplier
{
	private static bool $schemaReady = false;

	public static function ensureSchema(): void
	{
		if (self::$schemaReady) {
			return;
		}

		self::$schemaReady = true;

		$tables = DB::execute("SHOW TABLES LIKE 'suppliers'");

		if (empty($tables)) {
			DB::execute(
				"CREATE TABLE `suppliers` (
					`id_supplier` int(11) NOT NULL AUTO_INCREMENT,
					`supplier_name` varchar(128) NOT NULL,
					`active` tinyint(1) NOT NULL DEFAULT 1,
					`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`id_supplier`),
					KEY `active` (`active`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}

		$col = DB::execute("SHOW COLUMNS FROM `products` LIKE 'id_supplier'");

		if (empty($col)) {
			DB::execute(
				"ALTER TABLE `products`
				 ADD COLUMN `id_supplier` int(11) NOT NULL DEFAULT 0 AFTER `id_brand`,
				 ADD KEY `id_supplier` (`id_supplier`)"
			);
		}
	}

	public static function getByIdAdmin(int $id): ?array
	{
		self::ensureSchema();
		$row = DB::getRowSafe('suppliers', 'id_supplier = ?', [$id]);

		return $row ?: null;
	}

	public static function getAdminList(int $activeFilter = -1, int $limit = 100, int $offset = 0): array
	{
		self::ensureSchema();

		$sql = 'SELECT * FROM suppliers WHERE 1=1';
		$params = [];

		if ($activeFilter >= 0) {
			$sql .= ' AND active = ?';
			$params[] = $activeFilter;
		}

		$sql .= ' ORDER BY supplier_name ASC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

		return DB::execute($sql, $params) ?: [];
	}

	public static function countAdmin(int $activeFilter = -1): int
	{
		self::ensureSchema();

		if ($activeFilter >= 0) {
			return (int) DB::getValue('SELECT COUNT(*) FROM suppliers WHERE active = ?', [$activeFilter]);
		}

		return (int) DB::getValue('SELECT COUNT(*) FROM suppliers');
	}

	public static function getOptions(): array
	{
		self::ensureSchema();

		return DB::execute(
			'SELECT id_supplier, supplier_name FROM suppliers WHERE active = 1 ORDER BY supplier_name ASC'
		) ?: [];
	}

	public static function save(array $data, int $id = 0): array
	{
		self::ensureSchema();

		$name = trim(strip_tags((string) ($data['supplier_name'] ?? '')));
		$active = !empty($data['active']) ? 1 : 0;

		if ($name === '') {
			return self::fail('Tedarikçi adı zorunludur');
		}

		$row = [
			'supplier_name' => mb_substr($name, 0, 128),
			'active' => $active,
		];

		if ($id > 0) {
			$ok = DB::update('suppliers', $row, 'id_supplier = :where_id', ['where_id' => $id]);

			if ($ok === false) {
				return self::fail('Tedarikçi güncellenemedi');
			}

			return ['success' => true, 'message' => 'Tedarikçi güncellendi', 'id' => $id];
		}

		$newId = DB::insert('suppliers', $row);

		if (!$newId) {
			return self::fail('Tedarikçi eklenemedi');
		}

		return ['success' => true, 'message' => 'Tedarikçi eklendi', 'id' => (int) $newId];
	}

	public static function delete(int $id): array
	{
		self::ensureSchema();

		if ($id <= 0) {
			return self::fail('Geçersiz tedarikçi');
		}

		$row = self::getByIdAdmin($id);

		if (!$row) {
			return self::fail('Tedarikçi bulunamadı');
		}

		DB::execute('UPDATE products SET id_supplier = 0 WHERE id_supplier = ?', [$id]);
		DB::execute('DELETE FROM suppliers WHERE id_supplier = ? LIMIT 1', [$id]);

		return [
			'success' => true,
			'message' => (string) $row['supplier_name'] . ' tedarikçisi silindi',
		];
	}

	private static function fail(string $message): array
	{
		return ['success' => false, 'message' => $message, 'id' => 0];
	}
}
