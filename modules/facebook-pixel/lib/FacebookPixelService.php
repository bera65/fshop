<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

class FacebookPixelService
{
	private const SESSION_PURCHASE = 'fb_pixel_purchase';

	public static function getPixelId(): string
	{
		return trim((string) Settings::get('FB_PIXEL_ID'));
	}

	public static function isEnabled(): bool
	{
		return Settings::get('FB_PIXEL_ENABLED') !== '0' && self::getPixelId() !== '';
	}

	public static function isValidPixelId(string $pixelId): bool
	{
		return (bool) preg_match('/^\d{8,20}$/', $pixelId);
	}

	public static function getCurrency(): string
	{
		$code = strtoupper(trim((string) Settings::get('CURRENCY_CODE')));

		return $code !== '' ? $code : 'TRY';
	}

	public static function saveSettings(array $input): void
	{
		Settings::set('FB_PIXEL_ENABLED', !empty($input['enabled']) ? '1' : '0');
		Settings::set('FB_PIXEL_ID', trim((string) ($input['pixel_id'] ?? '')));
		Settings::set('FB_PIXEL_TRACK_VIEW', !empty($input['track_view']) ? '1' : '0');
		Settings::set('FB_PIXEL_TRACK_CART', !empty($input['track_cart']) ? '1' : '0');
		Settings::set('FB_PIXEL_TRACK_CHECKOUT', !empty($input['track_checkout']) ? '1' : '0');
		Settings::set('FB_PIXEL_TRACK_PURCHASE', !empty($input['track_purchase']) ? '1' : '0');
	}

	/** @return array<string, mixed> */
	public static function getSettings(): array
	{
		return [
			'enabled' => self::isEnabled(),
			'pixel_id' => self::getPixelId(),
			'track_view' => Settings::get('FB_PIXEL_TRACK_VIEW') !== '0',
			'track_cart' => Settings::get('FB_PIXEL_TRACK_CART') !== '0',
			'track_checkout' => Settings::get('FB_PIXEL_TRACK_CHECKOUT') !== '0',
			'track_purchase' => Settings::get('FB_PIXEL_TRACK_PURCHASE') !== '0',
		];
	}

	/** @param array<string, mixed> $order */
	public static function rememberPurchase(array $order): void
	{
		if (!self::isEnabled() || Settings::get('FB_PIXEL_TRACK_PURCHASE') === '0') {
			return;
		}

		$contentIds = [];
		$lines = DB::execute(
			'SELECT id_product FROM order_lines WHERE id_order = ?',
			[(int) ($order['id_order'] ?? 0)]
		) ?: [];

		foreach ($lines as $line) {
			$id = (int) ($line['id_product'] ?? 0);

			if ($id > 0) {
				$contentIds[] = (string) $id;
			}
		}

		$_SESSION[self::SESSION_PURCHASE] = [
			'event_id' => (string) ($order['reference'] ?? $order['id_order'] ?? ''),
			'value' => (float) ($order['total'] ?? 0),
			'currency' => self::getCurrency(),
			'content_ids' => array_values(array_unique($contentIds)),
		];
	}

	/** @return array<string, mixed>|null */
	public static function consumePurchaseEvent(): ?array
	{
		if (empty($_SESSION[self::SESSION_PURCHASE]) || !is_array($_SESSION[self::SESSION_PURCHASE])) {
			return null;
		}

		$event = $_SESSION[self::SESSION_PURCHASE];
		unset($_SESSION[self::SESSION_PURCHASE]);

		return $event;
	}

	/** @return array<string, mixed>|null */
	public static function buildViewContentEvent(int $idProduct): ?array
	{
		if ($idProduct <= 0 || Settings::get('FB_PIXEL_TRACK_VIEW') === '0') {
			return null;
		}

		$product = Product::getById($idProduct);

		if (!$product) {
			return null;
		}

		return [
			'name' => 'ViewContent',
			'payload' => [
				'content_ids' => [(string) $idProduct],
				'content_type' => 'product',
				'content_name' => (string) ($product['product_name'] ?? ''),
				'value' => (float) ($product['price'] ?? 0),
				'currency' => self::getCurrency(),
			],
		];
	}

	/** @return array<string, mixed> */
	public static function getFooterClientConfig(): array
	{
		$events = [];
		$purchase = self::consumePurchaseEvent();

		if ($purchase) {
			$events[] = [
				'name' => 'Purchase',
				'payload' => [
					'content_ids' => $purchase['content_ids'] ?? [],
					'content_type' => 'product',
					'value' => (float) ($purchase['value'] ?? 0),
					'currency' => (string) ($purchase['currency'] ?? self::getCurrency()),
					'order_id' => (string) ($purchase['event_id'] ?? ''),
				],
			];
		}

		return [
			'active' => self::isEnabled(),
			'currency' => self::getCurrency(),
			'trackCart' => Settings::get('FB_PIXEL_TRACK_CART') !== '0',
			'trackCheckout' => Settings::get('FB_PIXEL_TRACK_CHECKOUT') !== '0',
			'events' => $events,
		];
	}
}
