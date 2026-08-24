<?php

class Upgrade
{
	public const SETTING_KEY = 'FS_VERSION';
	public const LEGACY_DEFAULT = '2.4.0';

	/** @return string */
	public static function migrationsDir(): string
	{
		return dirname(__DIR__) . '/upgrade/migrations';
	}

	public static function getCodeVersion(): string
	{
		$fromDisk = self::readCodeVersionFromDisk();

		if ($fromDisk !== '') {
			return $fromDisk;
		}

		return (string) FShop::VERSION;
	}

	/** Prefer on-disk VERSION so one-click updates see the new package without a PHP restart. */
	public static function readCodeVersionFromDisk(): string
	{
		$file = dirname(__DIR__) . '/core/FShop.php';

		if (!is_file($file)) {
			return '';
		}

		$src = (string) @file_get_contents($file);

		if ($src === '') {
			return '';
		}

		if (preg_match("/const\s+VERSION\s*=\s*'(\d+\.\d+\.\d+)'/", $src, $m)) {
			return $m[1];
		}

		return '';
	}

	public static function getInstalledVersion(): string
	{
		$raw = trim((string) Settings::get(self::SETTING_KEY));

		if ($raw === '' || !self::isValidVersion($raw)) {
			return self::LEGACY_DEFAULT;
		}

		return $raw;
	}

	public static function setInstalledVersion(string $version): void
	{
		$version = trim($version);

		if (!self::isValidVersion($version)) {
			return;
		}

		Settings::set(self::SETTING_KEY, $version);
	}

	public static function isUpToDate(): bool
	{
		return version_compare(self::getInstalledVersion(), self::getCodeVersion(), '>=');
	}

	public static function isValidVersion(string $version): bool
	{
		return (bool) preg_match('/^\d+\.\d+\.\d+$/', $version);
	}

	/**
	 * @return list<array{version:string,title:string,file:string,object:object}>
	 */
	public static function listMigrations(): array
	{
		$dir = self::migrationsDir();

		if (!is_dir($dir)) {
			return [];
		}

		$files = glob($dir . '/*.php') ?: [];
		$list = [];

		foreach ($files as $file) {
			$base = basename($file, '.php');

			if (!self::isValidVersion($base)) {
				continue;
			}

			$migration = include $file;

			if (!is_object($migration) || !method_exists($migration, 'up')) {
				continue;
			}

			$version = property_exists($migration, 'version')
				? (string) $migration->version
				: $base;

			if (!self::isValidVersion($version)) {
				$version = $base;
			}

			$title = property_exists($migration, 'title')
				? (string) $migration->title
				: $version;

			$list[] = [
				'version' => $version,
				'title' => $title,
				'file' => $file,
				'object' => $migration,
			];
		}

		usort($list, static function (array $a, array $b): int {
			return version_compare($a['version'], $b['version']);
		});

		return $list;
	}

	/**
	 * Pending: installed < migrationVersion <= codeVersion
	 *
	 * @return list<array{version:string,title:string,file:string,object:object}>
	 */
	public static function getPending(): array
	{
		$installed = self::getInstalledVersion();
		$code = self::getCodeVersion();
		$pending = [];

		foreach (self::listMigrations() as $row) {
			$v = $row['version'];

			if (version_compare($v, $installed, '>') && version_compare($v, $code, '<=')) {
				$pending[] = $row;
			}
		}

		return $pending;
	}

	/**
	 * @return array{success:bool,message:string,logs:list<string>,installed:string,code:string}
	 */
	public static function runPending(): array
	{
		$logs = [];
		$code = self::getCodeVersion();
		$pending = self::getPending();

		if ($pending === []) {
			if (!self::isUpToDate()) {
				self::setInstalledVersion($code);
				$logs[] = 'Bekleyen migration yok; FS_VERSION ' . $code . ' olarak işaretlendi.';
			}

			return [
				'success' => true,
				'message' => 'Sistem zaten güncel.',
				'logs' => $logs,
				'installed' => self::getInstalledVersion(),
				'code' => $code,
			];
		}

		foreach ($pending as $row) {
			$version = $row['version'];
			$title = $row['title'];
			$logs[] = 'Başladı: ' . $version . ' — ' . $title;

			try {
				Schema::forceEnsure();
				$row['object']->up();
				self::setInstalledVersion($version);
				$logs[] = 'Tamam: ' . $version;
			} catch (Throwable $e) {
				$logs[] = 'Hata (' . $version . '): ' . $e->getMessage();

				return [
					'success' => false,
					'message' => 'Güncelleme ' . $version . ' adımında durdu.',
					'logs' => $logs,
					'installed' => self::getInstalledVersion(),
					'code' => $code,
				];
			}
		}

		if (version_compare(self::getInstalledVersion(), $code, '<')) {
			self::setInstalledVersion($code);
			$logs[] = 'FS_VERSION kod sürümüne eşitlendi: ' . $code;
		}

		return [
			'success' => true,
			'message' => 'Güncelleme tamamlandı (' . $code . ').',
			'logs' => $logs,
			'installed' => self::getInstalledVersion(),
			'code' => $code,
		];
	}

	/**
	 * CHANGELOG.md içinden ($from, $to] sürüm bloklarını döner.
	 *
	 * @return list<array{version:string,date:string,body:string}>
	 */
	public static function parseChangelogBetween(string $from, string $to): array
	{
		$path = dirname(__DIR__) . '/CHANGELOG.md';

		if (!is_file($path)) {
			return [];
		}

		$content = (string) file_get_contents($path);

		if ($content === '') {
			return [];
		}

		if (!preg_match_all(
			'/^## \[(\d+\.\d+\.\d+)\](?:\s+[—\-]\s*(.+))?$/m',
			$content,
			$matches,
			PREG_OFFSET_CAPTURE
		)) {
			return [];
		}

		$blocks = [];
		$count = count($matches[0]);

		for ($i = 0; $i < $count; $i++) {
			$version = $matches[1][$i][0];
			$date = trim((string) ($matches[2][$i][0] ?? ''));
			$start = $matches[0][$i][1] + strlen($matches[0][$i][0]);
			$end = ($i + 1 < $count) ? $matches[0][$i + 1][1] : strlen($content);
			$body = trim(substr($content, $start, $end - $start));
			$body = preg_replace('/^---\s*/m', '', $body);
			$body = trim((string) $body);

			if (version_compare($version, $from, '>') && version_compare($version, $to, '<=')) {
				$blocks[] = [
					'version' => $version,
					'date' => $date,
					'body' => $body,
				];
			}
		}

		usort($blocks, static function (array $a, array $b): int {
			return version_compare($a['version'], $b['version']);
		});

		return $blocks;
	}
}
