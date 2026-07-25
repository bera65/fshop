<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

class HomeTextModule extends ModuleBase
{
	public string $name = 'home-text';
	public string $title = 'Ana Sayfa Metni';
	public string $version = '1.0.0';
	public string $description = 'Ana sayfaya HTML metin bloğu ekler';
	public string $author = 'FShop';

	private const TABLE = '`home-text`';
	private const ROW_ID = 1;

	public function install(): bool
	{
		if (!$this->runSqlFile('install.sql')) {
			return false;
		}

		$this->ensureDefaultRow();

		return true;
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	public function boot(): void
	{
		if (!Module::isInstalled($this->name)) {
			return;
		}

		$this->ensureDefaultRow();

		Module::registerHook('smarty.assign', function ($smarty): void {
			if (!$smarty || defined('IN_ADMIN')) {
				return;
			}

			$smarty->assign([
				'homeText' => $this->getContent(),
			]);
		});
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';
		$flashType = 'info';

		$this->ensureDefaultRow();

		if (Tools::isSubmit('saveHomeText')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} elseif ($this->saveContent((string) Tools::getValue('home_text', ''))) {
				$flash = 'Ana sayfa metni kaydedildi';
				$flashType = 'success';
			} else {
				$flash = 'Metin kaydedilemedi';
				$flashType = 'danger';
			}
		}

		$smarty->assign([
			'flash' => $flash,
			'flashType' => $flashType,
			'homeTextContent' => $this->getContent(),
			'adminUseEditor' => true,
		]);
	}

	public function getContent(): string
	{
		$row = DB::getRowSafe(self::TABLE, 'id = ?', [self::ROW_ID]);

		if (!$row || !isset($row['detail'])) {
			return '';
		}

		return (string) $row['detail'];
	}

	public function saveContent(string $html): bool
	{
		$html = Security::sanitizeHtml(trim($html));
		$row = DB::getRowSafe(self::TABLE, 'id = ?', [self::ROW_ID]);

		if ($row) {
			return DB::update(self::TABLE, ['detail' => $html], 'id = :where_id', ['where_id' => self::ROW_ID]) !== false;
		}

		return DB::insert(self::TABLE, [
			'id' => self::ROW_ID,
			'detail' => $html,
		]) !== false;
	}

	private function ensureDefaultRow(): void
	{
		$row = DB::getRowSafe(self::TABLE, 'id = ?', [self::ROW_ID]);

		if (!$row) {
			DB::insert(self::TABLE, [
				'id' => self::ROW_ID,
				'detail' => '',
			]);
		}
	}
}
