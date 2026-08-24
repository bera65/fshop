<?php

class Coupon
{
	const SESSION_KEY = 'applied_coupon';

	public static function ensureSchema(): void
	{
		$col = DB::execute("SHOW COLUMNS FROM `coupons` LIKE 'id_user'");

		if (empty($col)) {
			DB::execute(
				'ALTER TABLE `coupons`
				 ADD COLUMN `id_user` int(11) NOT NULL DEFAULT 0 AFTER `used_count`,
				 ADD KEY `id_user` (`id_user`)'
			);
		}
	}

	public static function normalizeCode(string $code): string
	{
		return strtoupper(preg_replace('/\s+/', '', trim($code)));
	}

	public static function getApplied(): ?array
	{
		$code = $_SESSION[self::SESSION_KEY] ?? '';

		if ($code === '') {
			return null;
		}

		$coupon = self::getByCode((string) $code);

		return $coupon ?: null;
	}

	public static function apply(string $code, float $subtotal): array
	{
		$code = self::normalizeCode($code);

		if ($code === '') {
			return self::fail('Kupon kodu girin');
		}

		$cart = Cart::getSummary();
		$promoDiscount = (float) (CartPromotion::calculate($cart)['discount'] ?? 0);
		$effectiveSubtotal = max(0.0, $subtotal - $promoDiscount);
		$validation = self::validate($code, $effectiveSubtotal);

		if (!$validation['success']) {
			return $validation;
		}

		$_SESSION[self::SESSION_KEY] = $code;

		$cart = Cart::getSummary();

		return self::ok('Kupon uygulandı', (float) $cart['total'], $cart);
	}

	public static function remove(): array
	{
		unset($_SESSION[self::SESSION_KEY]);

		return [
			'success' => true,
			'message' => 'Kupon kaldırıldı',
			'discount' => 0.0,
			'discount_formatted' => Tools::displayPrice(0),
			'code' => '',
		];
	}

	public static function getDiscount(float $subtotal, ?array $cart = null): float
	{
		$coupon = self::getApplied();

		if (!$coupon) {
			return 0.0;
		}

		$cart = $cart ?? Cart::getSummary();
		$promoDiscount = (float) (CartPromotion::calculate($cart)['discount'] ?? 0);
		$effectiveSubtotal = max(0.0, $subtotal - $promoDiscount);

		$validation = self::validate($coupon['code'], $effectiveSubtotal);

		if (!$validation['success']) {
			unset($_SESSION[self::SESSION_KEY]);

			return 0.0;
		}

		return (float) $validation['discount'];
	}

	public static function getCheckoutSummary(float $subtotal, ?array $cart = null): array
	{
		$cart = $cart ?? Cart::getSummary();
		$promotion = CartPromotion::calculate($cart);
		$promotionDiscount = (float) ($promotion['discount'] ?? 0);
		$couponDiscount = self::getDiscount($subtotal, $cart);
		$totalDiscount = $promotionDiscount + $couponDiscount;
		$coupon = self::getApplied();
		$afterDiscount = max(0.0, $subtotal - $totalDiscount);
		$requiresShipping = Cart::requiresShipping($cart);
		$idCargo = class_exists('Cargo') ? Cargo::getSelectedId() : 0;
		$shipping = $requiresShipping ? Order::getShippingFee($afterDiscount, $idCargo > 0 ? $idCargo : null) : 0.0;
		$paymentMethod = Order::getSelectedPaymentMethod();
		$paymentInfo = Module::getPaymentDiscount($paymentMethod, $afterDiscount);
		$paymentDiscount = min($afterDiscount, (float) ($paymentInfo['amount'] ?? 0));
		$gift = Order::resolveGiftWrap();
		$total = max(0.0, $afterDiscount - $paymentDiscount) + $shipping + (float) $gift['gift_wrap_fee'];
		$hints = class_exists('Cargo') ? Cargo::getDisplayHints() : ['free_shipping_min' => 0.0];

		return [
			'subtotal' => $subtotal,
			'subtotal_formatted' => Tools::displayPrice($subtotal),
			'promotion_discount' => $promotionDiscount,
			'promotion_discount_formatted' => Tools::displayPrice($promotionDiscount),
			'promotion_name' => $promotion['name'] ?? '',
			'promotion_label' => $promotion['label'] ?? '',
			'promotion_lines' => $promotion['lines'] ?? [],
			'has_promotion' => $promotionDiscount > 0,
			'coupon_discount' => $couponDiscount,
			'coupon_discount_formatted' => Tools::displayPrice($couponDiscount),
			'discount' => $totalDiscount,
			'discount_formatted' => Tools::displayPrice($totalDiscount),
			'coupon_code' => $coupon['code'] ?? '',
			'has_coupon' => $couponDiscount > 0,
			'payment_discount' => $paymentDiscount,
			'payment_discount_formatted' => Tools::displayPrice($paymentDiscount),
			'payment_discount_label' => (string) ($paymentInfo['label'] ?? ''),
			'has_payment_discount' => $paymentDiscount > 0,
			'payment_method' => $paymentMethod,
			'shipping' => $shipping,
			'shipping_formatted' => $requiresShipping && $shipping > 0
				? Tools::displayPrice($shipping)
				: ($requiresShipping ? 'Ücretsiz' : '—'),
			'gift_wrap' => (int) $gift['gift_wrap'],
			'gift_wrap_fee' => (float) $gift['gift_wrap_fee'],
			'gift_wrap_fee_formatted' => (string) $gift['gift_wrap_fee_formatted'],
			'gift_wrap_enabled' => !empty($gift['gift_wrap_enabled']),
			'has_gift_wrap' => (int) $gift['gift_wrap'] === 1,
			'total' => $total,
			'total_formatted' => Tools::displayPrice($total),
			'free_shipping_min' => (float) ($hints['free_shipping_min'] ?? 0),
			'requires_shipping' => $requiresShipping,
			'id_cargo' => $idCargo,
			'cargo_options' => ($requiresShipping && class_exists('Cargo'))
				? Cargo::getCheckoutOptions($afterDiscount)
				: [],
		];
	}

	public static function validate(string $code, float $subtotal): array
	{
		$code = self::normalizeCode($code);
		$coupon = self::getByCode($code);

		if (!$coupon) {
			return self::fail('Geçersiz kupon kodu');
		}

		if (!(int) $coupon['active']) {
			return self::fail('Bu kupon artık geçerli değil');
		}

		$now = date('Y-m-d H:i:s');

		if (!empty($coupon['date_from']) && $coupon['date_from'] > $now) {
			return self::fail('Bu kupon henüz aktif değil');
		}

		if (!empty($coupon['date_to']) && $coupon['date_to'] < $now) {
			return self::fail('Bu kuponun süresi dolmuş');
		}

		$minCart = (float) $coupon['min_cart'];
		if ($minCart > 0 && $subtotal < $minCart) {
			return self::fail('Bu kupon için minimum sepet tutarı ' . Tools::displayPrice($minCart));
		}

		$maxUses = (int) $coupon['max_uses'];
		if ($maxUses > 0 && (int) $coupon['used_count'] >= $maxUses) {
			return self::fail('Bu kupon kullanım limitine ulaştı');
		}

		$idUserBound = (int) ($coupon['id_user'] ?? 0);
		if ($idUserBound > 0) {
			$currentUserId = class_exists('Customer', false) ? Customer::getId() : 0;

			if ($currentUserId <= 0) {
				return self::fail('Bu kupon yalnızca hesabınıza özeldir. Lütfen giriş yapın.');
			}

			if ($currentUserId !== $idUserBound) {
				return self::fail('Bu kupon sizin hesabınıza tanımlı değil');
			}
		}

		$discount = self::calculateDiscount($coupon, $subtotal);

		if ($discount <= 0) {
			return self::fail('Kupon bu sepet için geçerli değil');
		}

		return [
			'success' => true,
			'message' => 'Kupon geçerli',
			'discount' => $discount,
			'discount_formatted' => Tools::displayPrice($discount),
			'coupon' => $coupon,
		];
	}

	public static function calculateDiscount(array $coupon, float $subtotal): float
	{
		$value = (float) $coupon['discount_value'];

		if ($coupon['discount_type'] === 'percent') {
			$discount = round($subtotal * $value / 100, 2);
		} else {
			$discount = $value;
		}

		return min($subtotal, max(0.0, $discount));
	}

	public static function reserveUse(string $code): bool
	{
		$code = self::normalizeCode($code);

		if ($code === '') {
			return true;
		}

		global $db;

		$stmt = $db->prepare(
			'UPDATE coupons
			 SET used_count = used_count + 1
			 WHERE code = ?
			   AND (max_uses = 0 OR used_count < max_uses)'
		);

		if (!$stmt || !$stmt->execute([$code])) {
			return false;
		}

		return $stmt->rowCount() > 0;
	}

	public static function markUsed(string $code): void
	{
		self::reserveUse($code);
	}

	public static function releaseUsed(string $code): void
	{
		$code = self::normalizeCode($code);

		if ($code === '') {
			return;
		}

		DB::execute(
			'UPDATE coupons
			 SET used_count = GREATEST(used_count - 1, 0)
			 WHERE code = ?',
			[$code]
		);
	}

	public static function getByCode(string $code): ?array
	{
		self::ensureSchema();

		$code = self::normalizeCode($code);
		$row = DB::getRowSafe('coupons', 'code = ?', [$code]);

		return $row ?: null;
	}

	public static function getById(int $idCoupon): ?array
	{
		$row = DB::getRowSafe('coupons', 'id_coupon = ?', [$idCoupon]);

		return $row ?: null;
	}

	public static function getAdminList(): array
	{
		self::ensureSchema();

		$rows = DB::execute(
			'SELECT c.*, u.user_full_name, u.email AS user_email
			 FROM coupons c
			 LEFT JOIN users u ON u.id_user = c.id_user
			 ORDER BY c.date_add DESC'
		) ?: [];

		foreach ($rows as &$row) {
			$row = self::enrichAdmin($row);
		}
		unset($row);

		return $rows;
	}

	public static function save(array $data, int $idCoupon = 0): array
	{
		self::ensureSchema();

		$code = self::normalizeCode((string) ($data['code'] ?? ''));
		$type = (string) ($data['discount_type'] ?? 'percent');
		$value = (float) ($data['discount_value'] ?? 0);
		$minCart = max(0.0, (float) ($data['min_cart'] ?? 0));
		$maxUses = max(0, (int) ($data['max_uses'] ?? 0));
		$active = !empty($data['active']) ? 1 : 0;
		$idUser = max(0, (int) ($data['id_user'] ?? 0));
		$dateFrom = self::normalizeDateTime((string) ($data['date_from'] ?? ''));
		$dateTo = self::normalizeDateTime((string) ($data['date_to'] ?? ''));

		if ($code === '' || strlen($code) < 3) {
			return self::fail('Geçerli bir kupon kodu girin');
		}

		if (!in_array($type, ['percent', 'fixed'], true)) {
			return self::fail('Geçersiz indirim tipi');
		}

		if ($value <= 0 || ($type === 'percent' && $value > 100)) {
			return self::fail('Geçerli bir indirim değeri girin');
		}

		if ($idUser > 0) {
			$user = DB::getRowSafe('users', 'id_user = ?', [$idUser]);
			if (!$user) {
				return self::fail('Seçilen müşteri bulunamadı');
			}
		}

		$exists = DB::getValue(
			'SELECT id_coupon FROM coupons WHERE code = ? AND id_coupon != ? LIMIT 1',
			[$code, $idCoupon]
		);

		if ($exists) {
			return self::fail('Bu kupon kodu zaten kullanılıyor');
		}

		$payload = [
			'code' => $code,
			'discount_type' => $type,
			'discount_value' => $value,
			'min_cart' => $minCart,
			'max_uses' => $maxUses,
			'id_user' => $idUser,
			'active' => $active,
			'date_from' => $dateFrom !== '' ? $dateFrom : null,
			'date_to' => $dateTo !== '' ? $dateTo : null,
		];

		if ($idCoupon > 0) {
			$updated = DB::update('coupons', $payload, 'id_coupon = :id_coupon', ['id_coupon' => $idCoupon]);

			if ($updated === false) {
				return self::fail('Kupon güncellenemedi');
			}

			return self::ok('Kupon güncellendi');
		}

		$id = DB::insert('coupons', array_merge($payload, [
			'used_count' => 0,
			'date_add' => date('Y-m-d H:i:s'),
		]));

		if (!$id) {
			return self::fail('Kupon oluşturulamadı');
		}

		return self::ok('Kupon oluşturuldu', 0.0, null, (int) $id);
	}

	/**
	 * Müşteriye özel tek kullanımlık kupon üretir.
	 *
	 * @return array{success: bool, message: string, id_coupon?: int, code?: string}
	 */
	public static function createPersonal(array $data): array
	{
		self::ensureSchema();

		$idUser = (int) ($data['id_user'] ?? 0);
		if ($idUser <= 0) {
			return self::fail('Müşteri gerekli');
		}

		$prefix = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($data['prefix'] ?? 'RVW'))) ?: 'RVW';
		$code = '';

		for ($i = 0; $i < 8; $i++) {
			$candidate = $prefix . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
			if (!self::getByCode($candidate)) {
				$code = $candidate;
				break;
			}
		}

		if ($code === '') {
			return self::fail('Kupon kodu üretilemedi');
		}

		$result = self::save([
			'code' => $code,
			'discount_type' => (string) ($data['discount_type'] ?? 'percent'),
			'discount_value' => $data['discount_value'] ?? 5,
			'min_cart' => $data['min_cart'] ?? 0,
			'max_uses' => max(1, (int) ($data['max_uses'] ?? 1)),
			'id_user' => $idUser,
			'active' => 1,
			'date_from' => $data['date_from'] ?? date('Y-m-d H:i:s'),
			'date_to' => $data['date_to'] ?? '',
		], 0);

		if (empty($result['success'])) {
			return $result;
		}

		$coupon = self::getByCode($code);

		return [
			'success' => true,
			'message' => $result['message'],
			'id_coupon' => (int) ($coupon['id_coupon'] ?? 0),
			'code' => $code,
		];
	}

	public static function delete(int $idCoupon): array
	{
		if ($idCoupon <= 0) {
			return self::fail('Geçersiz kupon');
		}

		DB::execute('DELETE FROM coupons WHERE id_coupon = ?', [$idCoupon]);

		return self::ok('Kupon silindi');
	}

	private static function normalizeDateTime(string $value): string
	{
		$value = trim(str_replace('T', ' ', $value));

		if ($value === '') {
			return '';
		}

		$ts = strtotime($value);

		return $ts ? date('Y-m-d H:i:s', $ts) : '';
	}

	public static function formatDateTimeInput(?string $value): string
	{
		if ($value === null || $value === '') {
			return '';
		}

		return substr(str_replace(' ', 'T', $value), 0, 16);
	}

	private static function enrichAdmin(array $row): array
	{
		$row['discount_label'] = $row['discount_type'] === 'percent'
			? '%' . (float) $row['discount_value']
			: Tools::displayPrice($row['discount_value']);
		$row['min_cart_formatted'] = Tools::displayPrice($row['min_cart']);
		$row['active'] = (int) $row['active'];
		$row['id_user'] = (int) ($row['id_user'] ?? 0);
		$row['date_formatted'] = Tools::formatDate3($row['date_add']);
		$row['customer_label'] = '';

		if ($row['id_user'] > 0) {
			$name = trim((string) ($row['user_full_name'] ?? ''));
			$email = trim((string) ($row['user_email'] ?? ''));

			if ($name === '' && $email === '') {
				$user = DB::getRowSafe('users', 'id_user = ?', [$row['id_user']]);
				if ($user) {
					$name = trim((string) ($user['user_full_name'] ?? ''));
					$email = trim((string) ($user['email'] ?? ''));
				}
			}

			$row['customer_label'] = $name !== ''
				? ($name . ($email !== '' ? ' (' . $email . ')' : ''))
				: ($email !== '' ? $email : '#' . $row['id_user']);
		}

		return $row;
	}

	private static function ok(string $message, float $subtotal = 0.0, ?array $cart = null, int $idCoupon = 0): array
	{
		$summary = $subtotal > 0 ? self::getCheckoutSummary($subtotal, $cart) : [];

		$out = array_merge([
			'success' => true,
			'message' => $message,
		], $summary);

		if ($idCoupon > 0) {
			$out['id_coupon'] = $idCoupon;
		}

		return $out;
	}

	private static function fail(string $message): array
	{
		return [
			'success' => false,
			'message' => $message,
		];
	}
}
