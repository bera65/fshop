<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN') && !defined('IN_PWA')) {
	exit;
}

class PushSubscriptionService
{
	private const TABLE = 'mobil_app_push_subscriptions';

	public static function ensureSchema(): void
	{
		DB::execute(
			'CREATE TABLE IF NOT EXISTS `' . self::TABLE . '` (
				`id_subscription` int unsigned NOT NULL AUTO_INCREMENT,
				`id_user` int unsigned NOT NULL,
				`endpoint` varchar(512) NOT NULL,
				`p256dh` varchar(255) NOT NULL,
				`auth` varchar(255) NOT NULL,
				`user_agent` varchar(255) DEFAULT NULL,
				`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`date_upd` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id_subscription`),
				UNIQUE KEY `endpoint` (`endpoint`(191)),
				KEY `id_user` (`id_user`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		);
	}

	/** @param array<string, mixed> $subscription */
	public static function saveForUser(int $idUser, array $subscription, string $userAgent = ''): bool
	{
		if ($idUser <= 0) {
			return false;
		}

		self::ensureSchema();

		$endpoint = trim((string) ($subscription['endpoint'] ?? ''));

		if ($endpoint === '') {
			return false;
		}

		$keys = is_array($subscription['keys'] ?? null) ? $subscription['keys'] : [];
		$p256dh = trim((string) ($keys['p256dh'] ?? ''));
		$auth = trim((string) ($keys['auth'] ?? ''));

		if ($p256dh === '' || $auth === '') {
			return false;
		}

		$existing = DB::getRowSafe(self::TABLE, 'endpoint = ?', [$endpoint]);

		if ($existing) {
			return (bool) DB::update(
				self::TABLE,
				[
					'id_user' => $idUser,
					'p256dh' => $p256dh,
					'auth' => $auth,
					'user_agent' => mb_substr($userAgent, 0, 255),
				],
				'id_subscription = :id_subscription',
				['id_subscription' => (int) $existing['id_subscription']]
			);
		}

		return (int) DB::insert(self::TABLE, [
			'id_user' => $idUser,
			'endpoint' => mb_substr($endpoint, 0, 512),
			'p256dh' => $p256dh,
			'auth' => $auth,
			'user_agent' => mb_substr($userAgent, 0, 255),
		]) > 0;
	}

	public static function removeByEndpoint(string $endpoint, ?int $idUser = null): bool
	{
		self::ensureSchema();

		$endpoint = trim($endpoint);

		if ($endpoint === '') {
			return false;
		}

		if ($idUser !== null && $idUser > 0) {
			return (bool) DB::execute(
				'DELETE FROM `' . self::TABLE . '` WHERE endpoint = ? AND id_user = ?',
				[$endpoint, $idUser]
			);
		}

		return (bool) DB::execute(
			'DELETE FROM `' . self::TABLE . '` WHERE endpoint = ?',
			[$endpoint]
		);
	}

	/** @return list<array<string, mixed>> */
	public static function getForUser(int $idUser): array
	{
		if ($idUser <= 0) {
			return [];
		}

		self::ensureSchema();

		return DB::execute(
			'SELECT * FROM `' . self::TABLE . '` WHERE id_user = ? ORDER BY id_subscription ASC',
			[$idUser]
		) ?: [];
	}

	public static function countForUser(int $idUser): int
	{
		if ($idUser <= 0) {
			return 0;
		}

		self::ensureSchema();

		return (int) DB::getValue(
			'SELECT COUNT(*) FROM `' . self::TABLE . '` WHERE id_user = ?',
			[$idUser]
		);
	}

	public static function countAll(): int
	{
		self::ensureSchema();

		return (int) DB::getValue('SELECT COUNT(*) FROM `' . self::TABLE . '`');
	}

	public static function removeById(int $idSubscription): bool
	{
		if ($idSubscription <= 0) {
			return false;
		}

		return (bool) DB::execute(
			'DELETE FROM `' . self::TABLE . '` WHERE id_subscription = ?',
			[$idSubscription]
		);
	}
}
