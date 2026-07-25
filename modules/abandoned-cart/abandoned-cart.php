<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/AbandonedCartService.php';

class AbandonedCartModule extends ModuleBase
{
	public string $name = 'abandoned-cart';
	public string $title = 'Terk Edilen Sepet';
	public string $version = '1.0.0';
	public string $description = 'Sepet takibi, hatırlatma e-postası ve otomatik cron';
	public string $author = 'FShop';

	public array $adminStylesheets = ['admin.css'];

	public function install(): bool
	{
		if (!$this->runSqlFile('install.sql')) {
			return false;
		}

		if (Settings::get('ABANDONED_CART_IDLE_HOURS') === '') {
			Settings::set('ABANDONED_CART_IDLE_HOURS', '2');
		}

		if (Settings::get('ABANDONED_CART_AUTO_REMIND') === '') {
			Settings::set('ABANDONED_CART_AUTO_REMIND', '0');
		}

		if (Settings::get('ABANDONED_CART_REMIND_HOURS') === '') {
			Settings::set('ABANDONED_CART_REMIND_HOURS', '24');
		}

		return true;
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	public function boot(): void
	{
		AbandonedCartService::ensureSchema();

		Module::registerHook('cart.changed', static function (array $cart): void {
			AbandonedCartService::syncFromCartSummary($cart);
		});

		Module::registerHook('order.placed', static function (array $order): void {
			AbandonedCartService::markConverted($order);
		});

		$this->registerAdminMenuLink('Abandoned Carts', 'sales', 45);
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';
		$flashType = 'success';

		if (Tools::isSubmit('saveAbandonedCartSettings') && hash_equals($adminToken, (string) Tools::getValue('token'))) {
			Settings::set('ABANDONED_CART_IDLE_HOURS', (string) max(1, (int) Tools::getValue('idle_hours')));
			Settings::set('ABANDONED_CART_AUTO_REMIND', Tools::getValue('auto_remind') ? '1' : '0');
			Settings::set('ABANDONED_CART_REMIND_HOURS', (string) max(1, (int) Tools::getValue('remind_hours')));
			Settings::set('ABANDONED_CART_AUTO_MESSAGE', trim((string) Tools::getValue('auto_message')));
			$flash = 'Ayarlar kaydedildi';
		}

		if (Tools::isSubmit('sendCartReminder') && hash_equals($adminToken, (string) Tools::getValue('token'))) {
			$result = AbandonedCartService::sendReminder(
				(int) Tools::getValue('id_cart'),
				trim((string) Tools::getValue('reminder_message')),
				trim((string) Tools::getValue('coupon_code')),
				(bool) Tools::getValue('create_coupon'),
				[
					'discount_type' => (string) Tools::getValue('coupon_type'),
					'discount_value' => Tools::getValue('coupon_value'),
					'min_cart' => Tools::getValue('coupon_min_cart'),
					'prefix' => 'SEP',
				]
			);
			$flash = $result['message'];
			$flashType = $result['success'] ? 'success' : 'danger';
		}

		$status = trim((string) Tools::getValue('status'));
		$currentPage = max(1, (int) Tools::getValue('page'));
		$perPage = 30;
		$total = AbandonedCartService::countAdmin($status);
		$pagination = Pagination::build(
			$total,
			$currentPage,
			$perPage,
			Admin::url($this->getAdminSlug()),
			array_filter(['status' => $status !== '' ? $status : null])
		);
		$carts = AbandonedCartService::getAdminList($status, $perPage, $pagination['offset']);

		$smarty->assign([
			'flash' => $flash,
			'flashType' => $flashType,
			'carts' => $carts,
			'pagination' => $pagination,
			'statusFilter' => $status,
			'settings' => [
				'idle_hours' => AbandonedCartService::getIdleHours(),
				'auto_remind' => AbandonedCartService::isAutoRemindEnabled(),
				'remind_hours' => AbandonedCartService::getAutoRemindHours(),
				'auto_message' => Settings::get('ABANDONED_CART_AUTO_MESSAGE'),
			],
			'cronUrl' => rtrim((string) ($GLOBALS['domain'] ?? ''), '/') . '/api/cron.php?action=abandoned-cart&token=' . urlencode((string) Settings::get('SHOP_TOKEN')),
		]);
	}
}
