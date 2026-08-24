<?php

/**
 * Download + extract + copy package (WordPress-style core update).
 */
class UpdateInstaller
{
	public const KEY_MAINTENANCE = 'FS_MAINTENANCE';

	/** @return list<string> Relative path prefixes that must never be overwritten */
	public static function protectedPrefixes(): array
	{
		return [
			'config/env.php',
			'config/installed.lock',
			'img/',
			'cache/',
			'.git/',
		];
	}

	public static function rootDir(): string
	{
		return dirname(__DIR__);
	}

	public static function stagingDir(): string
	{
		return self::rootDir() . '/cache/updates';
	}

	public static function setMaintenance(bool $on): void
	{
		Settings::set(self::KEY_MAINTENANCE, $on ? '1' : '0');
	}

	public static function isMaintenance(): bool
	{
		return Settings::get(self::KEY_MAINTENANCE) === '1';
	}

	/**
	 * Full update: download → extract → copy → migrate → clear caches.
	 *
	 * @param array{version:string,download_url:string,min_php?:string}|null $offer
	 * @return array{success:bool,message:string,logs:list<string>}
	 */
	public static function runFullUpdate(?array $offer = null): array
	{
		@set_time_limit(0);
		@ini_set('memory_limit', '512M');

		$logs = [];

		if (!class_exists('ZipArchive', false)) {
			return self::fail('PHP ZipArchive eklentisi gerekli', $logs);
		}

		if ($offer === null) {
			$check = UpdateChecker::check(true);

			if (empty($check['success']) || empty($check['offer'])) {
				return self::fail((string) ($check['message'] ?? 'Sürüm teklifi yok'), $logs);
			}

			$offer = $check['offer'];

			if (empty($check['update_available'])) {
				return [
					'success' => true,
					'message' => 'Zaten güncel sürümdesiniz.',
					'logs' => ['Kod sürümü: ' . Upgrade::getCodeVersion()],
				];
			}
		}

		$version = (string) ($offer['version'] ?? '');
		$downloadUrl = (string) ($offer['download_url'] ?? '');
		$minPhp = (string) ($offer['min_php'] ?? '7.4');

		if (!Upgrade::isValidVersion($version) || $downloadUrl === '') {
			return self::fail('Geçersiz güncelleme teklifi', $logs);
		}

		if (version_compare(PHP_VERSION, $minPhp, '<')) {
			return self::fail('Bu sürüm için PHP ' . $minPhp . '+ gerekli (şu an ' . PHP_VERSION . ')', $logs);
		}

		if (!UpdateChecker::isHttpsUrl($downloadUrl)) {
			return self::fail('İndirme URL yalnızca HTTPS olmalı', $logs);
		}

		$current = Upgrade::getCodeVersion();

		if (!version_compare($version, $current, '>')) {
			return [
				'success' => true,
				'message' => 'Teklif edilen sürüm kod sürümünden yeni değil.',
				'logs' => ['current=' . $current, 'offer=' . $version],
			];
		}

		$staging = self::stagingDir();

		if (!is_dir($staging) && !@mkdir($staging, 0755, true)) {
			return self::fail('Staging dizini oluşturulamadı: cache/updates', $logs);
		}

		$workId = 'upd_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
		$workDir = $staging . '/' . $workId;
		$zipPath = $workDir . '/package.zip';

		if (!@mkdir($workDir, 0755, true)) {
			return self::fail('Çalışma dizini oluşturulamadı', $logs);
		}

		self::setMaintenance(true);
		$logs[] = 'Bakım modu açıldı';
		$result = null;

		try {
			$logs[] = 'İndiriliyor: ' . $version;
			if (!self::downloadToFile($downloadUrl, $zipPath, $logs)) {
				throw new RuntimeException('Paket indirilemedi');
			}

			$logs[] = 'ZIP açılıyor…';
			$extractDir = $workDir . '/extracted';

			if (!@mkdir($extractDir, 0755, true)) {
				throw new RuntimeException('Extract dizini oluşturulamadı');
			}

			self::extractZip($zipPath, $extractDir);
			$packageRoot = self::resolvePackageRoot($extractDir);
			$logs[] = 'Paket kökü: ' . basename($packageRoot);

			$logs[] = 'Dosyalar kopyalanıyor (korumalı yollar atlanıyor)…';
			$copied = self::copyPackage($packageRoot, self::rootDir(), $logs);
			$logs[] = 'Kopyalanan dosya: ' . $copied;

			clearstatcache(true, self::rootDir() . '/core/FShop.php');

			$logs[] = 'Veritabanı migration çalıştırılıyor…';
			$migrate = Upgrade::runPending();
			foreach ($migrate['logs'] as $line) {
				$logs[] = $line;
			}

			if (empty($migrate['success'])) {
				throw new RuntimeException((string) ($migrate['message'] ?? 'Migration başarısız'));
			}

			if (class_exists('Performance', false)) {
				$clear = Performance::clearCaches();
				$logs[] = 'Önbellek temizlendi: ' . (string) ($clear['message'] ?? 'OK');
			}

			$logs[] = 'Tamam: ' . $version;
			$result = [
				'success' => true,
				'message' => 'Güncelleme tamamlandı (' . $version . ').',
				'logs' => $logs,
			];
		} catch (Throwable $e) {
			$logs[] = 'Hata: ' . $e->getMessage();
			$result = [
				'success' => false,
				'message' => 'Güncelleme başarısız: ' . $e->getMessage(),
				'logs' => $logs,
			];
		} finally {
			self::setMaintenance(false);
			self::rrmdir($workDir);
		}

		if (!is_array($result)) {
			return self::fail('Beklenmeyen güncelleme durumu', $logs);
		}

		$result['logs'][] = 'Bakım modu kapatıldı';

		return $result;
	}

	/**
	 * @param list<string> $logs
	 * @return array{success:bool,message:string,logs:list<string>}
	 */
	private static function fail(string $message, array $logs): array
	{
		$logs[] = $message;

		return [
			'success' => false,
			'message' => $message,
			'logs' => $logs,
		];
	}

	/**
	 * @param list<string> $logs
	 */
	private static function downloadToFile(string $url, string $dest, array &$logs): bool
	{
		if (function_exists('curl_init')) {
			$fp = fopen($dest, 'wb');

			if ($fp === false) {
				$logs[] = 'Hedef dosya yazılamadı';

				return false;
			}

			$ch = curl_init($url);
			$opts = [
				CURLOPT_FILE => $fp,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_MAXREDIRS => 5,
				CURLOPT_CONNECTTIMEOUT => 20,
				CURLOPT_TIMEOUT => 600,
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_USERAGENT => 'FriSay-UpdateInstaller',
				CURLOPT_HTTPHEADER => ['Accept: application/octet-stream'],
			];
			$ca = UpdateChecker::caBundlePath();
			if ($ca !== '') {
				$opts[CURLOPT_CAINFO] = $ca;
			}
			curl_setopt_array($ch, $opts);
			$ok = curl_exec($ch);
			$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$err = curl_error($ch);

			if (($ok === false || $code === 0) && $err !== '' && stripos($err, 'SSL') !== false) {
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
				$ok = curl_exec($ch);
				$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
				$err = curl_error($ch);
			}

			curl_close($ch);
			fclose($fp);

			if ($ok === false || $code < 200 || $code >= 300 || !is_file($dest) || filesize($dest) < 100) {
				$logs[] = 'HTTP indirme hatası' . ($err !== '' ? ': ' . $err : ' (HTTP ' . $code . ')');
				@unlink($dest);

				return false;
			}

			$logs[] = 'İndirildi (' . self::formatBytes((int) filesize($dest)) . ')';

			return true;
		}

		$body = UpdateChecker::httpGet($url, ['User-Agent: FriSay-UpdateInstaller']);

		if ($body === null || strlen($body) < 100) {
			$logs[] = 'Paket gövdesi boş veya çok küçük';

			return false;
		}

		if (file_put_contents($dest, $body) === false) {
			$logs[] = 'ZIP diske yazılamadı';

			return false;
		}

		$logs[] = 'İndirildi (' . self::formatBytes(strlen($body)) . ')';

		return true;
	}

	private static function extractZip(string $zipPath, string $destDir): void
	{
		$zip = new ZipArchive();
		$open = $zip->open($zipPath);

		if ($open !== true) {
			throw new RuntimeException('ZIP açılamadı (kod ' . $open . ')');
		}

		$destReal = realpath($destDir);

		if ($destReal === false) {
			$zip->close();
			throw new RuntimeException('Extract hedefi geçersiz');
		}

		for ($i = 0; $i < $zip->numFiles; $i++) {
			$name = $zip->getNameIndex($i);

			if ($name === false) {
				continue;
			}

			$name = str_replace('\\', '/', $name);

			if ($name === '' || substr($name, -1) === '/') {
				continue;
			}

			if (strpos($name, '../') !== false || strpos($name, '..\\') !== false || $name[0] === '/' || preg_match('#^[A-Za-z]:#', $name)) {
				$zip->close();
				throw new RuntimeException('ZIP path traversal engellendi: ' . $name);
			}

			$target = $destReal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $name);
			$targetRealParent = realpath(dirname($target));

			// Parent may not exist yet — validate after mkdir via prefix check
			$dir = dirname($target);

			if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
				$zip->close();
				throw new RuntimeException('Dizin oluşturulamadı: ' . $name);
			}

			$resolvedDir = realpath(dirname($target));

			if ($resolvedDir === false || strpos($resolvedDir, $destReal) !== 0) {
				$zip->close();
				throw new RuntimeException('ZIP çıkış yolu güvenli değil: ' . $name);
			}

			$stream = $zip->getStream($name);

			if ($stream === false) {
				continue;
			}

			$out = fopen($target, 'wb');

			if ($out === false) {
				fclose($stream);
				$zip->close();
				throw new RuntimeException('Dosya yazılamadı: ' . $name);
			}

			stream_copy_to_stream($stream, $out);
			fclose($out);
			fclose($stream);
		}

		$zip->close();
	}

	private static function resolvePackageRoot(string $extractDir): string
	{
		$entries = array_values(array_filter(scandir($extractDir) ?: [], static function ($e) {
			return $e !== '.' && $e !== '..';
		}));

		if (count($entries) === 1) {
			$only = $extractDir . '/' . $entries[0];

			if (is_dir($only)) {
				// GitHub zipball: owner-repo-sha/ — or fshop-2.5.6/
				return $only;
			}
		}

		return $extractDir;
	}

	/**
	 * @param list<string> $logs
	 */
	private static function copyPackage(string $from, string $to, array &$logs): int
	{
		$from = rtrim(str_replace('\\', '/', $from), '/');
		$to = rtrim(str_replace('\\', '/', $to), '/');
		$count = 0;
		$skipped = 0;

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		/** @var SplFileInfo $file */
		foreach ($iterator as $file) {
			$full = str_replace('\\', '/', $file->getPathname());
			$rel = ltrim(substr($full, strlen($from)), '/');

			if ($rel === '') {
				continue;
			}

			if (self::isProtectedPath($rel)) {
				$skipped++;
				continue;
			}

			$dest = $to . '/' . $rel;

			if ($file->isDir()) {
				if (!is_dir($dest) && !@mkdir($dest, 0755, true)) {
					throw new RuntimeException('Dizin oluşturulamadı: ' . $rel);
				}
				continue;
			}

			$dir = dirname($dest);

			if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
				throw new RuntimeException('Hedef dizin oluşturulamadı: ' . $rel);
			}

			if (!@copy($file->getPathname(), $dest)) {
				throw new RuntimeException('Kopyalanamadı: ' . $rel);
			}

			$count++;
		}

		$logs[] = 'Atlanan korumalı öğe: ' . $skipped;

		return $count;
	}

	public static function isProtectedPath(string $relative): bool
	{
		$relative = ltrim(str_replace('\\', '/', $relative), '/');
		$lower = strtolower($relative);

		foreach (self::protectedPrefixes() as $prefix) {
			$prefix = strtolower(str_replace('\\', '/', $prefix));

			if (substr($prefix, -1) === '/') {
				if ($lower === rtrim($prefix, '/') || strpos($lower, $prefix) === 0) {
					return true;
				}
			} elseif ($lower === $prefix) {
				return true;
			}
		}

		return false;
	}

	private static function formatBytes(int $bytes): string
	{
		if ($bytes < 1024) {
			return $bytes . ' B';
		}

		if ($bytes < 1048576) {
			return round($bytes / 1024, 1) . ' KB';
		}

		return round($bytes / 1048576, 1) . ' MB';
	}

	private static function rrmdir(string $dir): void
	{
		if (!is_dir($dir)) {
			return;
		}

		$items = scandir($dir) ?: [];

		foreach ($items as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}

			$path = $dir . DIRECTORY_SEPARATOR . $item;

			if (is_dir($path)) {
				self::rrmdir($path);
			} else {
				@unlink($path);
			}
		}

		@rmdir($dir);
	}
}
