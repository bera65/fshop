<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

class InstagramGalleryService
{
	private const SETTINGS_TABLE = 'instagram_gallery_settings';
	private const ITEMS_TABLE = 'instagram_gallery_items';
	private const ROW_ID = 1;

	public static function getImageDir(): string
	{
		return dirname(__DIR__) . '/assets/img';
	}

	public static function getImagePublicUrl(string $filename): string
	{
		global $domain;

		$filename = basename($filename);

		return rtrim((string) $domain, '/') . '/modules/instagram-gallery/assets/img/' . rawurlencode($filename);
	}

	public static function ensureSchema(): void
	{
		DB::execute(
			'CREATE TABLE IF NOT EXISTS `' . self::SETTINGS_TABLE . '` (
				`id` tinyint unsigned NOT NULL DEFAULT 1,
				`enabled` tinyint(1) NOT NULL DEFAULT 1,
				`title` varchar(128) NOT NULL DEFAULT \'Instagram\',
				`subtitle` varchar(255) NOT NULL DEFAULT \'\',
				`profile_url` varchar(255) NOT NULL DEFAULT \'\',
				`profile_label` varchar(128) NOT NULL DEFAULT \'@magaza\',
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		);

		DB::execute(
			'CREATE TABLE IF NOT EXISTS `' . self::ITEMS_TABLE . '` (
				`id` int unsigned NOT NULL AUTO_INCREMENT,
				`image_url` varchar(512) NOT NULL DEFAULT \'\',
				`link_url` varchar(512) NOT NULL DEFAULT \'\',
				`caption` varchar(255) NOT NULL DEFAULT \'\',
				`position` int unsigned NOT NULL DEFAULT 0,
				`active` tinyint(1) NOT NULL DEFAULT 1,
				PRIMARY KEY (`id`),
				KEY `position` (`position`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		);

		if (!DB::getRowSafe(self::SETTINGS_TABLE, 'id = ?', [self::ROW_ID])) {
			DB::insert(self::SETTINGS_TABLE, [
				'id' => self::ROW_ID,
				'enabled' => 1,
				'title' => 'Instagram',
				'profile_label' => '@magaza',
			]);
		}

		$dir = self::getImageDir();

		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
	}

	/** @return array<string, mixed> */
	public static function getSettings(): array
	{
		self::ensureSchema();
		$row = DB::getRowSafe(self::SETTINGS_TABLE, 'id = ?', [self::ROW_ID]);

		if (!$row) {
			return [
				'enabled' => 1,
				'title' => 'Instagram',
				'subtitle' => '',
				'profile_url' => '',
				'profile_label' => '@magaza',
			];
		}

		return [
			'enabled' => (int) ($row['enabled'] ?? 1),
			'title' => (string) ($row['title'] ?? 'Instagram'),
			'subtitle' => (string) ($row['subtitle'] ?? ''),
			'profile_url' => (string) ($row['profile_url'] ?? ''),
			'profile_label' => (string) ($row['profile_label'] ?? '@magaza'),
		];
	}

	public static function isEnabled(): bool
	{
		$s = self::getSettings();

		return !empty($s['enabled']);
	}

	/** @param array<string, mixed> $input */
	public static function saveSettings(array $input): bool
	{
		self::ensureSchema();

		$row = [
			'enabled' => !empty($input['enabled']) ? 1 : 0,
			'title' => trim((string) ($input['title'] ?? 'Instagram')),
			'subtitle' => trim((string) ($input['subtitle'] ?? '')),
			'profile_url' => trim((string) ($input['profile_url'] ?? '')),
			'profile_label' => trim((string) ($input['profile_label'] ?? '@magaza')),
		];

		if ($row['title'] === '') {
			$row['title'] = 'Instagram';
		}

		$exists = DB::getRowSafe(self::SETTINGS_TABLE, 'id = ?', [self::ROW_ID]);

		if ($exists) {
			return DB::update(self::SETTINGS_TABLE, $row, 'id = :where_id', ['where_id' => self::ROW_ID]) !== false;
		}

		$row['id'] = self::ROW_ID;

		return DB::insert(self::SETTINGS_TABLE, $row) !== false;
	}

	/** @return array<int, array<string, mixed>> */
	public static function getActiveItems(): array
	{
		self::ensureSchema();
		$rows = DB::execute(
			'SELECT * FROM `' . self::ITEMS_TABLE . '` WHERE active = 1 ORDER BY position ASC, id ASC'
		) ?: [];

		return array_map([self::class, 'normalizeItem'], $rows);
	}

	/** @return array<int, array<string, mixed>> */
	public static function getAllItems(): array
	{
		self::ensureSchema();
		$rows = DB::execute(
			'SELECT * FROM `' . self::ITEMS_TABLE . '` ORDER BY position ASC, id ASC'
		) ?: [];

		return array_map([self::class, 'normalizeItem'], $rows);
	}

	/** @param array<string, mixed> $row */
	public static function normalizeItem(array $row): array
	{
		$row['image_url'] = self::resolveImageUrl((string) ($row['image_url'] ?? ''));

		return $row;
	}

	public static function resolveImageUrl(string $stored): string
	{
		if ($stored === '') {
			return '';
		}

		if (strpos($stored, 'local:') === 0) {
			return self::getImagePublicUrl(substr($stored, 6));
		}

		return $stored;
	}

	/**
	 * @param array<string, mixed> $input
	 * @param array<string, mixed>|null $file
	 * @return array{success: bool, message: string}
	 */
	public static function addItem(array $input, ?array $file = null): array
	{
		self::ensureSchema();

		$linkUrl = trim((string) ($input['link_url'] ?? ''));
		$caption = trim((string) ($input['caption'] ?? ''));
		$postUrl = trim((string) ($input['instagram_post_url'] ?? ''));
		$imageUrl = trim((string) ($input['image_url'] ?? ''));

		if ($postUrl !== '') {
			$import = self::importFromInstagramPost($postUrl);

			if (!$import['success']) {
				return $import;
			}

			$imageUrl = 'local:' . $import['file'];

			if ($linkUrl === '') {
				$linkUrl = $postUrl;
			}

			if ($caption === '' && !empty($import['title'])) {
				$caption = (string) $import['title'];
			}
		} elseif ($file !== null && !empty($file['tmp_name'])) {
			$upload = self::uploadImage($file);

			if (!$upload['success']) {
				return $upload;
			}

			$imageUrl = 'local:' . $upload['file'];
		} elseif ($imageUrl !== '') {
			// Harici URL — geriye dönük uyumluluk
		} else {
			return self::fail('Bir görsel dosyası seçin veya Instagram gönderi linki yapıştırın.');
		}

		$maxPos = (int) DB::getValue('SELECT COALESCE(MAX(position), 0) FROM `' . self::ITEMS_TABLE . '`');

		if (DB::insert(self::ITEMS_TABLE, [
			'image_url' => $imageUrl,
			'link_url' => $linkUrl,
			'caption' => $caption,
			'position' => $maxPos + 1,
			'active' => !empty($input['active']) ? 1 : 0,
		]) === false) {
			return self::fail('Görsel kaydedilemedi');
		}

		return self::ok('Görsel eklendi');
	}

	/** @return array{success: bool, message: string, file?: string, title?: string} */
	public static function importFromInstagramPost(string $postUrl): array
	{
		$postUrl = trim($postUrl);

		if (!preg_match('#instagram\.com/(p|reel|reels|tv)/#i', $postUrl)) {
			return self::fail('Geçerli bir Instagram gönderi linki girin (ör. instagram.com/p/...).');
		}

		$oembedUrl = 'https://api.instagram.com/oembed?url=' . rawurlencode($postUrl) . '&omitscript=true';
		$context = stream_context_create([
			'http' => [
				'timeout' => 12,
				'header' => "User-Agent: FShop Instagram Gallery\r\n",
			],
		]);

		$raw = @file_get_contents($oembedUrl, false, $context);
		$data = is_string($raw) ? json_decode($raw, true) : null;

		if (!is_array($data) || empty($data['thumbnail_url'])) {
			return self::fail('Instagram görseli alınamadı. Gönderi herkese açık olmalı; linki tarayıcıdan kopyalayın.');
		}

		$imageBytes = @file_get_contents((string) $data['thumbnail_url'], false, $context);

		if (!is_string($imageBytes) || $imageBytes === '') {
			return self::fail('Görsel indirilemedi. Dosya yüklemeyi deneyin.');
		}

		$saved = self::saveImageBytes($imageBytes);

		if (!$saved['success']) {
			return $saved;
		}

		return [
			'success' => true,
			'message' => 'Instagram görseli alındı',
			'file' => $saved['file'],
			'title' => (string) ($data['title'] ?? ''),
		];
	}

	/** @param array<string, mixed> $file */
	public static function uploadImage(array $file): array
	{
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
			return self::fail('Geçerli bir görsel dosyası seçin (JPG, PNG veya WEBP).');
		}

		$bytes = @file_get_contents($file['tmp_name']);

		if (!is_string($bytes) || $bytes === '') {
			return self::fail('Dosya okunamadı');
		}

		return self::saveImageBytes($bytes);
	}

	/** @return array{success: bool, message: string, file?: string} */
	public static function saveImageBytes(string $bytes): array
	{
		$source = @imagecreatefromstring($bytes);

		if (!$source) {
			return self::fail('Geçerli bir görsel dosyası değil');
		}

		$dir = self::getImageDir();

		if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
			imagedestroy($source);

			return self::fail('Görsel klasörü oluşturulamadı');
		}

		$filename = 'ig_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.jpg';
		$dest = $dir . '/' . $filename;
		$width = imagesx($source);
		$height = imagesy($source);
		$maxSize = 1080;

		if ($width > $maxSize || $height > $maxSize) {
			if ($width >= $height) {
				$newWidth = $maxSize;
				$newHeight = (int) round($height * ($maxSize / $width));
			} else {
				$newHeight = $maxSize;
				$newWidth = (int) round($width * ($maxSize / $height));
			}

			$resized = imagecreatetruecolor($newWidth, $newHeight);
			imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
			imagedestroy($source);
			$source = $resized;
		}

		imagejpeg($source, $dest, 88);
		imagedestroy($source);

		return [
			'success' => true,
			'message' => 'Görsel kaydedildi',
			'file' => $filename,
		];
	}

	public static function deleteItem(int $id): bool
	{
		if ($id <= 0) {
			return false;
		}

		$row = DB::getRowSafe(self::ITEMS_TABLE, 'id = ?', [$id]);

		if ($row) {
			self::removeStoredImage((string) ($row['image_url'] ?? ''));
		}

		return DB::execute('DELETE FROM `' . self::ITEMS_TABLE . '` WHERE id = ?', [$id]) !== false;
	}

	public static function toggleItem(int $id): bool
	{
		$row = DB::getRowSafe(self::ITEMS_TABLE, 'id = ?', [$id]);

		if (!$row) {
			return false;
		}

		return DB::update(self::ITEMS_TABLE, [
			'active' => empty($row['active']) ? 1 : 0,
		], 'id = :where_id', ['where_id' => $id]) !== false;
	}

	private static function removeStoredImage(string $stored): void
	{
		if (strpos($stored, 'local:') !== 0) {
			return;
		}

		$path = self::getImageDir() . '/' . basename(substr($stored, 6));

		if (is_file($path)) {
			@unlink($path);
		}
	}

	/** @return array{success: bool, message: string} */
	private static function ok(string $message): array
	{
		return ['success' => true, 'message' => $message];
	}

	/** @return array{success: bool, message: string} */
	private static function fail(string $message): array
	{
		return ['success' => false, 'message' => $message];
	}
}
