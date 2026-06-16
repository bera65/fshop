<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

class SliderModule extends ModuleBase
{
	public string $name = 'slider';
	public string $title = 'Slayt Modülü';
	public string $version = '1.0.0';
	public string $description = 'Ana sayfa üst slayt ve kampanya slaytı yönetimi';
	public string $author = 'FShop';

	public array $displayHooks = [
		'home_slider' => 'Ana sayfa üst slayt (hero)',
		'home_promo_slider' => 'Ana sayfa kampanya slaytı (2\'li)',
	];

	public array $defaultDisplayHooks = ['home_slider', 'home_promo_slider'];

	public array $frontStylesheets = ['slider.css'];
	public array $frontScripts = ['slider.js'];

	public function install(): bool
	{
		if (!$this->runSqlFile('install.sql')) {
			return false;
		}

		$imgDir = $this->getImageDir();

		if (!is_dir($imgDir)) {
			mkdir($imgDir, 0755, true);
		}

		if (Settings::get('SLIDER_PROMO_TITLE') === '') {
			Settings::set('SLIDER_PROMO_TITLE', 'En Avantajlı Fırsatlar');
		}

		return true;
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';
		$group = self::normalizeGroup((string) Tools::getValue('group', 'hero'));

		if (Tools::isSubmit('saveSliderSettings')) {
			$postToken = (string) Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				Settings::set('SLIDER_PROMO_TITLE', trim((string) Tools::getValue('promo_title')));
				$flash = 'Ayarlar kaydedildi';
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		if (Tools::isSubmit('saveSlide')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
			} else {
				$result = self::saveSlide([
					'id_slide' => (int) Tools::getValue('id_slide'),
					'slide_group' => $group,
					'title' => (string) Tools::getValue('title'),
					'subtitle' => (string) Tools::getValue('subtitle'),
					'promo_lines' => (string) Tools::getValue('promo_lines'),
					'button_text' => (string) Tools::getValue('button_text'),
					'link_url' => (string) Tools::getValue('link_url'),
					'position' => (int) Tools::getValue('position'),
					'active' => Tools::getValue('active') ? 1 : 0,
				], isset($_FILES['image']) ? $_FILES['image'] : null);

				$flash = $result['message'];
			}
		}

		if (Tools::isSubmit('deleteSlide')) {
			$postToken = (string) Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$result = self::deleteSlide((int) Tools::getValue('id_slide'));
				$flash = $result['message'];
			}
		}

		if (Tools::isSubmit('toggleSlide')) {
			$postToken = (string) Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$result = self::toggleActive((int) Tools::getValue('id_slide'));
				$flash = $result['message'];
			}
		}

		$editId = (int) Tools::getValue('edit');
		$editSlide = $editId > 0 ? self::getById($editId) : null;

		if ($editSlide) {
			$group = $editSlide['slide_group'];
		}

		$smarty->assign([
			'sliderGroup' => $group,
			'sliderGroups' => self::getGroups(),
			'slides' => self::getList($group, false),
			'editSlide' => $editSlide,
			'promoTitle' => Settings::get('SLIDER_PROMO_TITLE') ?: 'En Avantajlı Fırsatlar',
			'flash' => $flash,
		]);
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook === 'home_slider') {
			$slides = self::getList('hero');

			if ($slides === []) {
				return null;
			}

			$html = $this->renderFrontTemplate('home_hero_slider', [
				'slides' => $slides,
			]);

			return $html !== '' ? $html : null;
		}

		if ($hook === 'home_promo_slider') {
			$slides = self::getList('promo');

			if ($slides === []) {
				return null;
			}

			$html = $this->renderFrontTemplate('home_promo_slider', [
				'slides' => $slides,
				'promoTitle' => Settings::get('SLIDER_PROMO_TITLE') ?: 'En Avantajlı Fırsatlar',
			]);

			return $html !== '' ? $html : null;
		}

		return null;
	}

	/** @return array<string, string> */
	public static function getGroups(): array
	{
		return [
			'hero' => 'Üst Slayt (Hero)',
			'promo' => 'Kampanya Slaytı (2\'li)',
		];
	}

	public static function normalizeGroup(string $group): string
	{
		$groups = self::getGroups();

		return isset($groups[$group]) ? $group : 'hero';
	}

	public static function getImageDir(): string
	{
		return dirname(__DIR__) . '/slider/assets/img/slides';
	}

	public static function getImageUrl(string $file): string
	{
		global $domain;

		if ($file === '') {
			return '';
		}

		return rtrim($domain, '/') . '/modules/slider/assets/img/slides/' . rawurlencode($file);
	}

	/** @return array<int, array<string, mixed>> */
	public static function getList(string $group, bool $activeOnly = true): array
	{
		$group = self::normalizeGroup($group);
		$sql = 'SELECT * FROM slider_slides WHERE slide_group = ?';

		if ($activeOnly) {
			$sql .= ' AND active = 1';
		}

		$sql .= ' ORDER BY position ASC, id_slide ASC';

		$rows = DB::execute($sql, [$group]) ?: [];

		foreach ($rows as &$row) {
			$row = self::enrich($row);
		}
		unset($row);

		return $rows;
	}

	public static function getById(int $idSlide): ?array
	{
		$row = DB::getRowSafe('slider_slides', 'id_slide = ?', [$idSlide]);

		return $row ? self::enrich($row) : null;
	}

	/** @param array<string, mixed> $row */
	private static function enrich(array $row): array
	{
		$row['image_url'] = self::getImageUrl((string) $row['image_file']);
		$row['promo_items'] = self::parsePromoLines((string) ($row['promo_lines'] ?? ''));

		return $row;
	}

	/** @return string[] */
	public static function parsePromoLines(string $text): array
	{
		$lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
		$items = [];

		foreach ($lines as $line) {
			$line = trim($line);

			if ($line !== '') {
				$items[] = $line;
			}
		}

		return $items;
	}

	/** @param array<string, mixed> $data */
	public static function saveSlide(array $data, ?array $file = null): array
	{
		$idSlide = (int) ($data['id_slide'] ?? 0);
		$group = self::normalizeGroup((string) ($data['slide_group'] ?? 'hero'));
		$title = trim((string) ($data['title'] ?? ''));
		$subtitle = trim((string) ($data['subtitle'] ?? ''));
		$promoLines = trim((string) ($data['promo_lines'] ?? ''));
		$buttonText = trim((string) ($data['button_text'] ?? ''));
		$linkUrl = trim((string) ($data['link_url'] ?? ''));
		$position = (int) ($data['position'] ?? 0);
		$active = !empty($data['active']) ? 1 : 0;

		if ($buttonText === '') {
			$buttonText = 'Keşfet';
		}

		$existing = $idSlide > 0 ? self::getById($idSlide) : null;
		$imageFile = $existing ? (string) $existing['image_file'] : '';

		if ($file && !empty($file['tmp_name'])) {
			$upload = self::uploadImage($file, $idSlide);

			if (!$upload['success']) {
				return $upload;
			}

			if ($imageFile !== '' && $imageFile !== $upload['file']) {
				self::removeImageFile($imageFile);
			}

			$imageFile = $upload['file'];
		}

		if ($imageFile === '') {
			return self::fail('Slayt görseli yükleyin');
		}

		$row = [
			'slide_group' => $group,
			'title' => $title,
			'subtitle' => $subtitle,
			'promo_lines' => $promoLines,
			'button_text' => $buttonText,
			'link_url' => $linkUrl,
			'image_file' => $imageFile,
			'position' => $position,
			'active' => $active,
		];

		if ($idSlide > 0 && $existing) {
			$ok = DB::update('slider_slides', $row, 'id_slide = :where_id', ['where_id' => $idSlide]);

			return $ok !== false
				? self::ok('Slayt güncellendi')
				: self::fail('Slayt güncellenemedi');
		}

		$newId = DB::insert('slider_slides', $row);

		return $newId
			? self::ok('Slayt eklendi')
			: self::fail('Slayt eklenemedi');
	}

	public static function deleteSlide(int $idSlide): array
	{
		$row = self::getById($idSlide);

		if (!$row) {
			return self::fail('Slayt bulunamadı');
		}

		DB::execute('DELETE FROM slider_slides WHERE id_slide = ?', [$idSlide]);
		self::removeImageFile((string) $row['image_file']);

		return self::ok('Slayt silindi');
	}

	public static function toggleActive(int $idSlide): array
	{
		$row = self::getById($idSlide);

		if (!$row) {
			return self::fail('Slayt bulunamadı');
		}

		$newActive = (int) $row['active'] === 1 ? 0 : 1;
		DB::update('slider_slides', ['active' => $newActive], 'id_slide = :where_id', ['where_id' => $idSlide]);

		return self::ok('Durum güncellendi');
	}

	/** @param array<string, mixed> $file */
	public static function uploadImage(array $file, int $idSlide = 0): array
	{
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
			return self::fail('Geçerli bir görsel seçin');
		}

		$info = @getimagesize($file['tmp_name']);

		if (!$info) {
			return self::fail('Dosya bir görsel değil');
		}

		$allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

		if (!in_array($info[2], $allowed, true)) {
			return self::fail('Sadece JPG, PNG veya WEBP yükleyebilirsiniz');
		}

		$dir = self::getImageDir();

		if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
			return self::fail('Görsel klasörü oluşturulamadı');
		}

		$filename = 'slide_' . ($idSlide > 0 ? $idSlide : time()) . '_' . bin2hex(random_bytes(4)) . '.jpg';
		$dest = $dir . '/' . $filename;
		$source = imagecreatefromstring(file_get_contents($file['tmp_name']));

		if (!$source) {
			return self::fail('Görsel işlenemedi');
		}

		$width = imagesx($source);
		$height = imagesy($source);
		$maxWidth = 1920;

		if ($width > $maxWidth) {
			$newHeight = (int) round($height * ($maxWidth / $width));
			$resized = imagecreatetruecolor($maxWidth, $newHeight);
			imagecopyresampled($resized, $source, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
			imagedestroy($source);
			$source = $resized;
		}

		imagejpeg($source, $dest, 88);
		imagedestroy($source);

		return [
			'success' => true,
			'message' => 'Görsel yüklendi',
			'file' => $filename,
		];
	}

	private static function removeImageFile(string $file): void
	{
		if ($file === '') {
			return;
		}

		$path = self::getImageDir() . '/' . basename($file);

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
