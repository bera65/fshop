<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

class CannedResponsesModule extends ModuleBase
{
	public string $name = 'canned-responses';
	public string $title = 'Hazır Cevaplar (Mesajlar)';
	public string $version = '1.0.0';
	public string $description = 'Müşteri mesajlarına hızlı cevap vermek için hazır metinler oluşturun';
	public string $author = 'FShop';

	public array $displayHooks = [
		'admin_footer' => 'Admin footer (mesaj sayfasında hazır cevapları yükler)',
	];

	public array $defaultDisplayHooks = ['admin_footer'];

	public function install(): bool
	{
		return $this->runSqlFile('install.sql');
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';

		if (Tools::isSubmit('saveResponse')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
			} else {
				$idResponse = (int) Tools::getValue('id_canned_response');
				$title = trim((string) Tools::getValue('title'));
				$message = trim((string) Tools::getValue('message'));
				$position = (int) Tools::getValue('position');

				if ($title === '' || $message === '') {
					$flash = 'Başlık ve mesaj zorunludur.';
				} else {
					$row = [
						'title' => $title,
						'message' => $message,
						'position' => $position,
					];

					if ($idResponse > 0) {
						DB::update('canned_responses', $row, 'id_canned_response = :id', ['id' => $idResponse]);
						$flash = 'Hazır cevap güncellendi.';
					} else {
						DB::insert('canned_responses', $row);
						$flash = 'Hazır cevap eklendi.';
					}
				}
			}
		}

		if (Tools::isSubmit('deleteResponse')) {
			$postToken = (string) Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$idResponse = (int) Tools::getValue('id_canned_response');
				DB::execute('DELETE FROM canned_responses WHERE id_canned_response = ?', [$idResponse]);
				$flash = 'Hazır cevap silindi.';
			}
		}

		$editId = (int) Tools::getValue('edit');
		$editResponse = $editId > 0 ? DB::getRowSafe('canned_responses', 'id_canned_response = ?', [$editId]) : null;

		$responses = DB::execute('SELECT * FROM canned_responses ORDER BY position ASC, id_canned_response ASC') ?: [];

		$smarty->assign([
			'responses' => $responses,
			'editResponse' => $editResponse,
			'flash' => $flash,
		]);
	}

	public function renderAdminDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook === 'admin_footer') {
			$page = $context['page_name'] ?? Tools::getValue('container');
			if ($page !== 'message') {
				return null;
			}

			$responses = DB::execute('SELECT * FROM canned_responses ORDER BY position ASC, id_canned_response ASC') ?: [];
			if (empty($responses)) {
				return null;
			}

			$html = $this->renderAdminTemplate('admin_footer', [
				'cannedResponses' => $responses,
			]);

			return $html !== '' ? $html : null;
		}

		return null;
	}
}
