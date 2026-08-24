<?php

class RateLimit
{
	const SCOPE_CUSTOMER_LOGIN = 'customer_login';
	const SCOPE_ADMIN_LOGIN = 'admin_login';
	const SCOPE_ADMIN_PASSWORD_RESET = 'admin_password_reset';
	const SCOPE_POS_LOGIN = 'pos_login';
	const SCOPE_WEBAPI = 'webapi';

	private static bool $schemaReady = false;

	public static function ensureSchema(): void
	{
		if (self::$schemaReady) {
			return;
		}

		self::$schemaReady = true;

		$table = DB::execute("SHOW TABLES LIKE 'rate_limit_hits'");

		if (empty($table)) {
			DB::execute(
				"CREATE TABLE `rate_limit_hits` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`scope` varchar(32) NOT NULL,
					`identifier` varchar(128) NOT NULL,
					`hit_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`id`),
					KEY `scope_identifier_hit` (`scope`, `identifier`, `hit_at`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}
	}

	public static function clientIp(): string
	{
		$ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

		return $ip !== '' ? $ip : '0.0.0.0';
	}

	public static function loginIdentifier(string $account): string
	{
		return self::clientIp() . '|' . strtolower(trim($account));
	}

	public static function isLimited(string $scope, string $identifier, int $maxAttempts, int $windowSeconds): bool
	{
		self::ensureSchema();

		$since = date('Y-m-d H:i:s', time() - $windowSeconds);
		$count = (int) DB::getValue(
			'SELECT COUNT(*) FROM rate_limit_hits
			 WHERE scope = ? AND identifier = ? AND hit_at >= ?',
			[$scope, $identifier, $since]
		);

		return $count >= $maxAttempts;
	}

	public static function record(string $scope, string $identifier): void
	{
		self::ensureSchema();

		DB::insert('rate_limit_hits', [
			'scope' => $scope,
			'identifier' => $identifier,
		]);

		if (random_int(1, 20) === 1) {
			self::prune();
		}
	}

	public static function clear(string $scope, string $identifier): void
	{
		self::ensureSchema();

		DB::execute(
			'DELETE FROM rate_limit_hits WHERE scope = ? AND identifier = ?',
			[$scope, $identifier]
		);
	}

	private static function prune(): void
	{
		$cutoff = date('Y-m-d H:i:s', time() - 86400);
		DB::execute('DELETE FROM rate_limit_hits WHERE hit_at < ?', [$cutoff]);
	}
}
