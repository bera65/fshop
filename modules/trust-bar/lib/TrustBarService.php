<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

class TrustBarService
{
	private const SETTINGS_TABLE = 'trust_bar_settings';
	private const ITEMS_TABLE = 'trust_bar_items';
	private const ROW_ID = 1;

	/** @var string[] */
	public const ICONS = [
		'shipping' => 'Kargo',
		'returns' => 'İade',
		'secure' => 'Güvenli ödeme',
		'support' => 'Destek',
		'gift' => 'Hediye',
		'star' => 'Kalite',
	];

	public static function ensureSchema(): void
	{
		DB::execute(
			'CREATE TABLE IF NOT EXISTS `' . self::SETTINGS_TABLE . '` (
				`id` tinyint unsigned NOT NULL DEFAULT 1,
				`enabled` tinyint(1) NOT NULL DEFAULT 1,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		);

		DB::execute(
			'CREATE TABLE IF NOT EXISTS `' . self::ITEMS_TABLE . '` (
				`id_item` int unsigned NOT NULL AUTO_INCREMENT,
				`title` varchar(128) NOT NULL DEFAULT \'\',
				`subtitle` varchar(255) NOT NULL DEFAULT \'\',
				`icon` varchar(32) NOT NULL DEFAULT \'shipping\',
				`position` int unsigned NOT NULL DEFAULT 0,
				`active` tinyint(1) NOT NULL DEFAULT 1,
				PRIMARY KEY (`id_item`),
				KEY `position` (`position`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		);

		if (!DB::getRowSafe(self::SETTINGS_TABLE, 'id = ?', [self::ROW_ID])) {
			DB::insert(self::SETTINGS_TABLE, ['id' => self::ROW_ID, 'enabled' => 1]);
		}

		if (!DB::getValue('SELECT COUNT(*) FROM `' . self::ITEMS_TABLE . '`')) {
			self::seedDefaults();
		}
	}

	public static function seedDefaults(): void
	{
		$defaults = [
			['title' => 'Ücretsiz Kargo', 'subtitle' => 'Belirli tutar üzeri', 'icon' => 'shipping', 'position' => 1],
			['title' => 'Kolay İade', 'subtitle' => '30 gün iade', 'icon' => 'returns', 'position' => 2],
			['title' => 'Güvenli Ödeme', 'subtitle' => '%100 güvenli', 'icon' => 'secure', 'position' => 3],
			['title' => '7/24 Destek', 'subtitle' => 'Bize ulaşın', 'icon' => 'support', 'position' => 4],
		];

		foreach ($defaults as $row) {
			DB::insert(self::ITEMS_TABLE, array_merge($row, ['active' => 1]));
		}
	}

	public static function isEnabled(): bool
	{
		self::ensureSchema();
		$row = DB::getRowSafe(self::SETTINGS_TABLE, 'id = ?', [self::ROW_ID]);

		return !empty($row['enabled']);
	}

	/** @return array<string, mixed> */
	public static function getSettings(): array
	{
		self::ensureSchema();
		$row = DB::getRowSafe(self::SETTINGS_TABLE, 'id = ?', [self::ROW_ID]);

		return [
			'enabled' => !empty($row['enabled']),
		];
	}

	public static function saveSettings(bool $enabled): bool
	{
		self::ensureSchema();
		$row = DB::getRowSafe(self::SETTINGS_TABLE, 'id = ?', [self::ROW_ID]);

		if ($row) {
			return DB::update(self::SETTINGS_TABLE, ['enabled' => $enabled ? 1 : 0], 'id = :id', ['id' => self::ROW_ID]) !== false;
		}

		return DB::insert(self::SETTINGS_TABLE, ['id' => self::ROW_ID, 'enabled' => $enabled ? 1 : 0]) !== false;
	}

	/** @return array<int, array<string, mixed>> */
	public static function getActiveItems(): array
	{
		self::ensureSchema();

		return DB::execute(
			'SELECT * FROM `' . self::ITEMS_TABLE . '` WHERE active = 1 ORDER BY position ASC, id_item ASC'
		) ?: [];
	}

	/** @return array<int, array<string, mixed>> */
	public static function getAllItems(): array
	{
		self::ensureSchema();

		return DB::execute(
			'SELECT * FROM `' . self::ITEMS_TABLE . '` ORDER BY position ASC, id_item ASC'
		) ?: [];
	}

	/** @param array<int, array<string, mixed>> $rows */
	public static function saveItems(array $rows): bool
	{
		self::ensureSchema();

		foreach ($rows as $index => $row) {
			$id = (int) ($row['id_item'] ?? 0);
			$title = trim((string) ($row['title'] ?? ''));
			$subtitle = trim((string) ($row['subtitle'] ?? ''));
			$icon = (string) ($row['icon'] ?? 'shipping');
			$position = max(1, (int) ($row['position'] ?? ($index + 1)));
			$active = !empty($row['active']) ? 1 : 0;

			if (!array_key_exists($icon, self::ICONS)) {
				$icon = 'shipping';
			}

			if ($title === '') {
				continue;
			}

			$payload = [
				'title' => $title,
				'subtitle' => $subtitle,
				'icon' => $icon,
				'position' => $position,
				'active' => $active,
			];

			if ($id > 0) {
				DB::update(self::ITEMS_TABLE, $payload, 'id_item = :id', ['id' => $id]);
			} else {
				DB::insert(self::ITEMS_TABLE, $payload);
			}
		}

		return true;
	}

	public static function deleteItem(int $id): bool
	{
		self::ensureSchema();

		if ($id <= 0) {
			return false;
		}

		return DB::execute('DELETE FROM `' . self::ITEMS_TABLE . '` WHERE id_item = ?', [$id]) !== false;
	}

	public static function addItem(string $title, string $subtitle = '', string $icon = 'shipping'): bool
	{
		self::ensureSchema();
		$title = trim($title);

		if ($title === '') {
			return false;
		}

		if (!array_key_exists($icon, self::ICONS)) {
			$icon = 'shipping';
		}

		$position = (int) DB::getValue('SELECT COALESCE(MAX(position), 0) + 1 FROM `' . self::ITEMS_TABLE . '`');

		return DB::insert(self::ITEMS_TABLE, [
			'title' => $title,
			'subtitle' => trim($subtitle),
			'icon' => $icon,
			'position' => max(1, $position),
			'active' => 1,
		]) !== false;
	}
}
