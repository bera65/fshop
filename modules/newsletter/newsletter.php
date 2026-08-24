<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

class NewsletterModule extends ModuleBase
{
	public string $name = 'newsletter';
	public string $title = 'Bülten';
	public string $version = '1.0.0';
	public string $description = 'Footer bülten aboneliği ve abone yönetimi';
	public string $author = 'FShop';

	public array $displayHooks = [
		'footer' => 'Footer bülten abonelik formu',
	];

	public array $defaultDisplayHooks = ['footer'];

	public array $frontScripts = ['newsletter.js'];

	public array $apiActions = [
		'subscribe' => 'api/subscribe.php',
	];

	public function install(): bool
	{
		return $this->runSqlFile('install.sql');
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	public function boot(): void
	{
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';

		if (Tools::isSubmit('toggleSubscriber')) {
			$postToken = (string) Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$id = (int) Tools::getValue('id_subscriber');
				$row = DB::getRowSafe('newsletter_subscribers', 'id_subscriber = ?', [$id]);

				if ($row) {
					$newActive = (int) $row['active'] === 1 ? 0 : 1;
					DB::update('newsletter_subscribers', ['active' => $newActive], 'id_subscriber = :where_id', [
						'where_id' => $id,
					]);
					$flash = 'Abone durumu güncellendi';
				}
			}
		}

		$currentPage = max(1, (int) Tools::getValue('page'));
		$perPage = 50;
		$total = self::countAll();
		$pagination = Pagination::build($total, $currentPage, $perPage, Admin::url($this->getAdminSlug()));
		$subscribers = self::getAdminList($perPage, $pagination['offset']);

		$smarty->assign([
			'subscribers' => $subscribers,
			'pagination' => $pagination,
			'activeCount' => self::countAdmin(),
			'flash' => $flash,
		]);
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if (!in_array($hook, ['footer'], true)) {
			return null;
		}

		global $domain;

		$html = $this->renderFrontTemplate($hook, [
			'newsletterApiUrl' => rtrim($domain, '/') . '/api/module.php?m=newsletter&action=subscribe',
			'token' => Security::getCsrfToken('front'),
		]);

		return $html !== '' ? $html : null;
	}

	public static function subscribe(string $email): array
	{
		$email = trim(strtolower($email));

		if ($email === '' || !Validate::isEmail($email)) {
			return ['success' => false, 'message' => 'Geçerli bir e-posta girin'];
		}

		$exists = DB::getRowSafe('newsletter_subscribers', 'email = ?', [$email]);

		if ($exists) {
			if ((int) $exists['active'] === 0) {
				DB::update('newsletter_subscribers', ['active' => 1], 'id_subscriber = :where_id', [
					'where_id' => (int) $exists['id_subscriber'],
				]);

				return ['success' => true, 'message' => 'Aboneliğiniz yeniden etkinleştirildi'];
			}

			return ['success' => true, 'message' => 'Bu e-posta zaten kayıtlı'];
		}

		$id = DB::insert('newsletter_subscribers', [
			'email' => $email,
			'active' => 1,
		]);

		return $id
			? ['success' => true, 'message' => 'Bültene başarıyla abone oldunuz']
			: ['success' => false, 'message' => 'Kayıt oluşturulamadı'];
	}

	public static function getAdminList(int $limit = 50, int $offset = 0): array
	{
		return DB::execute(
			'SELECT * FROM newsletter_subscribers ORDER BY id_subscriber DESC LIMIT '
			. (int) $limit . ' OFFSET ' . (int) $offset
		) ?: [];
	}

	public static function countAdmin(): int
	{
		return (int) DB::getValue('SELECT COUNT(*) FROM newsletter_subscribers WHERE active = 1');
	}

	public static function countAll(): int
	{
		return (int) DB::getValue('SELECT COUNT(*) FROM newsletter_subscribers');
	}
}
