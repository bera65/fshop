<?php

class Tax
{
	private static bool $schemaReady = false;

	public static function ensureSchema(): void
	{
		if (self::$schemaReady) {
			return;
		}

		self::$schemaReady = true;

		DB::execute(
			'CREATE TABLE IF NOT EXISTS `taxes` (
				`id_tax` int unsigned NOT NULL AUTO_INCREMENT,
				`name` varchar(64) NOT NULL DEFAULT \'\',
				`rate` decimal(6,2) NOT NULL DEFAULT 0.00,
				`active` tinyint(1) NOT NULL DEFAULT 1,
				`is_default` tinyint(1) NOT NULL DEFAULT 0,
				`position` int unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY (`id_tax`),
				UNIQUE KEY `rate` (`rate`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		);

		if ((int) DB::getValue('SELECT COUNT(*) FROM `taxes`') === 0) {
			self::seedDefaults();
		}
	}

	private static function seedDefaults(): void
	{
		$defaults = [
			['name' => 'KDV %1', 'rate' => 1, 'is_default' => 0, 'position' => 1],
			['name' => 'KDV %10', 'rate' => 10, 'is_default' => 0, 'position' => 2],
			['name' => 'KDV %20', 'rate' => 20, 'is_default' => 1, 'position' => 3],
		];

		foreach ($defaults as $row) {
			DB::insert('taxes', [
				'name' => $row['name'],
				'rate' => $row['rate'],
				'active' => 1,
				'is_default' => $row['is_default'],
				'position' => $row['position'],
			]);
		}
	}

	/** @return array<int, array<string, mixed>> */
	public static function getAdminList(): array
	{
		self::ensureSchema();
		$rows = DB::execute('SELECT * FROM `taxes` ORDER BY `position` ASC, `rate` ASC') ?: [];

		return array_map([self::class, 'normalizeRow'], $rows);
	}

	/** @return array<int, array<string, mixed>> */
	public static function getActiveOptions(): array
	{
		self::ensureSchema();
		$rows = DB::execute(
			'SELECT * FROM `taxes` WHERE `active` = 1 ORDER BY `position` ASC, `rate` ASC'
		) ?: [];

		return array_map([self::class, 'normalizeRow'], $rows);
	}

	public static function getDefaultRate(): float
	{
		self::ensureSchema();
		$row = DB::getRowSafe('taxes', 'is_default = 1 AND active = 1');

		if ($row) {
			return (float) $row['rate'];
		}

		$fallback = DB::getValue('SELECT rate FROM `taxes` WHERE active = 1 ORDER BY position ASC, rate ASC LIMIT 1');

		return $fallback !== false && $fallback !== null ? (float) $fallback : 20.0;
	}

	public static function sanitizeRate(float $rate): float
	{
		self::ensureSchema();

		foreach (self::getActiveOptions() as $tax) {
			if (abs((float) $tax['rate'] - $rate) < 0.001) {
				return (float) $tax['rate'];
			}
		}

		return self::getDefaultRate();
	}

	/** @return array<int, array<string, mixed>> Product form select — includes current rate if missing from list */
	public static function getProductOptions(float $currentRate = 0.0): array
	{
		$options = self::getActiveOptions();
		$found = false;

		foreach ($options as $tax) {
			if (abs((float) $tax['rate'] - $currentRate) < 0.001) {
				$found = true;
				break;
			}
		}

		if (!$found && $currentRate > 0) {
			array_unshift($options, [
				'id_tax' => 0,
				'name' => 'KDV %' . rtrim(rtrim(number_format($currentRate, 2, '.', ''), '0'), '.'),
				'rate' => $currentRate,
				'active' => 0,
				'is_default' => 0,
				'position' => 0,
				'legacy' => true,
			]);
		}

		return $options;
	}

	public static function add(string $name, float $rate, bool $active = true): array
	{
		self::ensureSchema();

		$name = trim(strip_tags($name));
		$rate = round(max(0, $rate), 2);

		if ($name === '') {
			return self::fail('Vergi adı gerekli');
		}

		if ($rate > 100) {
			return self::fail('Vergi oranı 100\'den büyük olamaz');
		}

		if (DB::getRowSafe('taxes', 'rate = ?', [$rate])) {
			return self::fail('Bu oranda bir vergi zaten tanımlı');
		}

		$maxPos = (int) DB::getValue('SELECT COALESCE(MAX(position), 0) FROM `taxes`');

		if (DB::insert('taxes', [
			'name' => $name,
			'rate' => $rate,
			'active' => $active ? 1 : 0,
			'is_default' => 0,
			'position' => $maxPos + 1,
		]) === false) {
			return self::fail('Vergi kaydedilemedi');
		}

		return self::ok('Vergi eklendi');
	}

	public static function update(int $idTax, string $name, float $rate, bool $active): array
	{
		self::ensureSchema();

		if ($idTax <= 0) {
			return self::fail('Geçersiz vergi');
		}

		$row = DB::getRowSafe('taxes', 'id_tax = ?', [$idTax]);

		if (!$row) {
			return self::fail('Vergi bulunamadı');
		}

		$name = trim(strip_tags($name));
		$rate = round(max(0, $rate), 2);
		$oldRate = (float) $row['rate'];
		$isDefault = (int) ($row['is_default'] ?? 0) === 1;

		if ($isDefault) {
			$active = true;
		}

		if ($name === '') {
			return self::fail('Vergi adı gerekli');
		}

		if ($rate > 100) {
			return self::fail('Vergi oranı 100\'den büyük olamaz');
		}

		$duplicate = DB::getRowSafe('taxes', 'rate = ? AND id_tax != ?', [$rate, $idTax]);

		if ($duplicate) {
			return self::fail('Bu oranda başka bir vergi zaten var');
		}

		if ($isDefault && !$active) {
			return self::fail('Varsayılan vergi devre dışı bırakılamaz');
		}

		if (DB::update('taxes', [
			'name' => $name,
			'rate' => $rate,
			'active' => $active ? 1 : 0,
		], 'id_tax = :where_id', ['where_id' => $idTax]) === false) {
			return self::fail('Vergi güncellenemedi');
		}

		if (abs($oldRate - $rate) >= 0.001) {
			DB::execute('UPDATE `products` SET `vat` = ? WHERE ABS(`vat` - ?) < 0.001', [$rate, $oldRate]);
		}

		return self::ok('Vergi güncellendi');
	}

	public static function delete(int $idTax): array
	{
		self::ensureSchema();

		if ($idTax <= 0) {
			return self::fail('Geçersiz vergi');
		}

		$row = DB::getRowSafe('taxes', 'id_tax = ?', [$idTax]);

		if (!$row) {
			return self::fail('Vergi bulunamadı');
		}

		if ((int) ($row['is_default'] ?? 0) === 1) {
			return self::fail('Varsayılan vergi silinemez');
		}

		$total = (int) DB::getValue('SELECT COUNT(*) FROM `taxes`');

		if ($total <= 1) {
			return self::fail('Son vergi silinemez');
		}

		$defaultRate = self::getDefaultRate();
		DB::execute('UPDATE `products` SET `vat` = ? WHERE ABS(`vat` - ?) < 0.001', [$defaultRate, (float) $row['rate']]);

		if (DB::execute('DELETE FROM `taxes` WHERE id_tax = ?', [$idTax]) === false) {
			return self::fail('Vergi silinemedi');
		}

		return self::ok('Vergi silindi. Bu oranı kullanan ürünler varsayılan vergiye taşındı.');
	}

	public static function setDefault(int $idTax): array
	{
		self::ensureSchema();

		$row = DB::getRowSafe('taxes', 'id_tax = ?', [$idTax]);

		if (!$row) {
			return self::fail('Vergi bulunamadı');
		}

		if (!(int) ($row['active'] ?? 0)) {
			return self::fail('Pasif vergi varsayılan yapılamaz');
		}

		DB::execute('UPDATE `taxes` SET is_default = 0');
		DB::update('taxes', ['is_default' => 1], 'id_tax = :where_id', ['where_id' => $idTax]);

		return self::ok('Varsayılan vergi güncellendi');
	}

	public static function countProductsUsingRate(float $rate): int
	{
		self::ensureSchema();

		return (int) DB::getValue('SELECT COUNT(*) FROM `products` WHERE ABS(`vat` - ?) < 0.001', [$rate]);
	}

	/** @param array<string, mixed> $row */
	private static function normalizeRow(array $row): array
	{
		$row['id_tax'] = (int) ($row['id_tax'] ?? 0);
		$row['rate'] = (float) ($row['rate'] ?? 0);
		$row['active'] = (int) ($row['active'] ?? 0);
		$row['is_default'] = (int) ($row['is_default'] ?? 0);
		$row['position'] = (int) ($row['position'] ?? 0);
		$row['product_count'] = self::countProductsUsingRate((float) $row['rate']);

		return $row;
	}

	private static function ok(string $message): array
	{
		return ['success' => true, 'message' => $message];
	}

	private static function fail(string $message): array
	{
		return ['success' => false, 'message' => $message];
	}
}
