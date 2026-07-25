<?php

class Order
{
	const STATUS_PENDING = 1;
	const STATUS_PROCESSING = 2;
	const STATUS_SHIPPED = 3;
	const STATUS_DELIVERED = 4;
	const STATUS_CANCELLED = 5;
	const STATUS_RETURNED = 6;
	const STATUS_RETURN_PENDING = 7;

	private static bool $schemaReady = false;

	public static function ensureSchema(): void
	{
		if (self::$schemaReady) {
			return;
		}

		self::$schemaReady = true;

		$columns = [
			'customer_email' => "varchar(128) NOT NULL DEFAULT '' AFTER `customer_phone`",
			'company_name' => "varchar(128) NOT NULL DEFAULT '' AFTER `customer_email`",
			'tax_office' => "varchar(64) NOT NULL DEFAULT '' AFTER `company_name`",
			'tax_number' => "varchar(20) NOT NULL DEFAULT '' AFTER `tax_office`",
			'cargo_company' => "varchar(64) NOT NULL DEFAULT '' AFTER `status`",
			'tracking_number' => "varchar(64) NOT NULL DEFAULT '' AFTER `cargo_company`",
			'payment_discount' => "decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `promotion_discount`",
			'payment_discount_label' => "varchar(128) NOT NULL DEFAULT '' AFTER `payment_discount`",
		];

		foreach ($columns as $name => $definition) {
			$exists = DB::execute("SHOW COLUMNS FROM `orders` LIKE '{$name}'");

			if (empty($exists)) {
				DB::execute("ALTER TABLE `orders` ADD COLUMN `{$name}` {$definition}");
			}
		}

		$dateDelivered = DB::execute("SHOW COLUMNS FROM `orders` LIKE 'date_delivered'");

		if (empty($dateDelivered)) {
			DB::execute(
				"ALTER TABLE `orders` ADD COLUMN `date_delivered` datetime DEFAULT NULL AFTER `date_add`"
			);
			DB::execute(
				'UPDATE orders SET date_delivered = date_add WHERE status = ? AND date_delivered IS NULL',
				[self::STATUS_DELIVERED]
			);
		}
	}

	public const PAYMENT_SESSION_KEY = 'checkout_payment_method';

	public static function getSelectedPaymentMethod(): string
	{
		return trim((string) ($_SESSION[self::PAYMENT_SESSION_KEY] ?? ''));
	}

	public static function setSelectedPaymentMethod(string $method): bool
	{
		$method = trim($method);

		if ($method === '') {
			unset($_SESSION[self::PAYMENT_SESSION_KEY]);

			return true;
		}

		$methods = Module::getPaymentMethods();

		if ($methods !== []) {
			if (!isset($methods[$method])) {
				return false;
			}
		} else {
			return false;
		}

		$_SESSION[self::PAYMENT_SESSION_KEY] = $method;

		return true;
	}

	public static function getStatusLabel(int $status): string
	{
		$labels = [
			self::STATUS_PENDING => translate('Order status pending'),
			self::STATUS_PROCESSING => translate('Order status processing'),
			self::STATUS_SHIPPED => translate('Order status shipped'),
			self::STATUS_DELIVERED => translate('Order status delivered'),
			self::STATUS_CANCELLED => translate('Order status cancelled'),
			self::STATUS_RETURNED => translate('Order status returned'),
			self::STATUS_RETURN_PENDING => translate('Order status return pending'),
		];

		return $labels[$status] ?? translate('Order status unknown');
	}

	public static function getPaymentLabel(string $method): string
	{
		$methods = Module::getPaymentMethods();

		if (isset($methods[$method])) {
			return $methods[$method]['label'];
		}

		$labels = [
			'bank_transfer' => translate('Bank Transfer'),
			'cash_on_delivery' => translate('Cash on Delivery'),
			'pos_cash' => 'POS — Nakit',
			'pos_card' => 'POS — Kart',
			'pos_transfer' => 'POS — Havale',
		];

		return isset($labels[$method]) ? $labels[$method] : $method;
	}

	public static function getShippingFee(float $subtotal, ?int $idCargo = null): float
	{
		if (!class_exists('Cargo') && is_file(dirname(__DIR__) . '/core/Cargo.php')) {
			require_once dirname(__DIR__) . '/core/Cargo.php';
		}

		if (!class_exists('Cargo')) {
			return 0.0;
		}

		$fee = Cargo::getFeeForAmount($subtotal, $idCargo);

		return $fee !== null ? $fee : 0.0;
	}

	public static function isPaymentAccepted(int $status): bool
	{
		return in_array($status, [
			self::STATUS_PROCESSING,
			self::STATUS_SHIPPED,
			self::STATUS_DELIVERED,
		], true);
	}

	public static function getCheckoutTotals(float $subtotal, float $discount = 0.0, ?array $cart = null, ?int $idCargo = null, ?string $paymentMethod = null): array
	{
		$discount = max(0.0, min($subtotal, $discount));
		$afterDiscount = $subtotal - $discount;
		$requiresShipping = Cart::requiresShipping($cart);
		$shipping = $requiresShipping ? self::getShippingFee($afterDiscount, $idCargo) : 0.0;
		$paymentMethod = $paymentMethod !== null ? trim($paymentMethod) : self::getSelectedPaymentMethod();
		$paymentInfo = Module::getPaymentDiscount($paymentMethod, $afterDiscount);
		$paymentDiscount = min($afterDiscount, (float) ($paymentInfo['amount'] ?? 0));
		$total = max(0.0, $afterDiscount - $paymentDiscount) + $shipping;
		$hints = class_exists('Cargo') ? Cargo::getDisplayHints() : ['free_shipping_min' => 0.0];

		return [
			'subtotal' => $subtotal,
			'subtotal_formatted' => Tools::displayPrice($subtotal),
			'discount' => $discount,
			'discount_formatted' => Tools::displayPrice($discount),
			'payment_discount' => $paymentDiscount,
			'payment_discount_formatted' => Tools::displayPrice($paymentDiscount),
			'payment_discount_label' => (string) ($paymentInfo['label'] ?? ''),
			'has_payment_discount' => $paymentDiscount > 0,
			'shipping' => $shipping,
			'shipping_formatted' => $requiresShipping && $shipping > 0
				? Tools::displayPrice($shipping)
				: ($requiresShipping ? translate('Free') : '—'),
			'total' => $total,
			'total_formatted' => Tools::displayPrice($total),
			'free_shipping_min' => (float) ($hints['free_shipping_min'] ?? 0),
			'requires_shipping' => $requiresShipping,
			'shipping_from_cargo' => class_exists('Cargo') && Cargo::getFeeForAmount($afterDiscount, $idCargo) !== null,
			'id_cargo' => $idCargo !== null && $idCargo > 0
				? $idCargo
				: (class_exists('Cargo') ? Cargo::getSelectedId() : 0),
			'payment_method' => $paymentMethod,
		];
	}

	public static function place(array $data): array
	{
		self::ensureSchema();

		$usingCartSnapshot = !empty($data['_payment_done'])
			&& !empty($data['_cart_snapshot'])
			&& is_array($data['_cart_snapshot']);

		if ($usingCartSnapshot) {
			$cart = $data['_cart_snapshot'];
		} else {
			$cart = Cart::getSummary();
		}

		if (!empty($cart['empty'])) {
			return self::fail(translate('Cart is empty order'));
		}

		if (!empty($data['_stored_coupon_code'])) {
			$_SESSION[Coupon::SESSION_KEY] = (string) $data['_stored_coupon_code'];
		}

		$name = trim((string) ($data['customer_name'] ?? ''));
		$phone = Customer::normalizePhone((string) ($data['customer_phone'] ?? ''));
		$customerEmail = strtolower(trim((string) ($data['customer_email'] ?? '')));
		$city = trim((string) ($data['address_city'] ?? ''));
		$district = trim((string) ($data['address_district'] ?? ''));
		$address = trim((string) ($data['address_text'] ?? ''));
		$note = trim((string) ($data['note'] ?? ''));
		$companyName = mb_substr(trim(strip_tags((string) ($data['company_name'] ?? ''))), 0, 128);
		$taxOffice = mb_substr(trim(strip_tags((string) ($data['tax_office'] ?? ''))), 0, 64);
		$taxNumber = preg_replace('/\D+/', '', (string) ($data['tax_number'] ?? ''));
		$taxNumber = mb_substr($taxNumber, 0, 20);
		$payment = (string) ($data['payment_method'] ?? '');
		$idUser = isset($data['_stored_id_user']) ? (int) $data['_stored_id_user'] : Customer::getId();
		$idAddress = (int) ($data['id_address'] ?? 0);

		if ($idAddress > 0) {
			if ($idUser <= 0) {
				return self::fail(translate('Address not found'));
			}

			$savedAddress = Address::getForUser($idAddress, $idUser);

			if (!$savedAddress) {
				return self::fail(translate('Address not found'));
			}

			$name = $savedAddress['full_name'];
			$phone = $savedAddress['phone'];
			$city = $savedAddress['city'];
			$district = $savedAddress['district'];
			$address = $savedAddress['address_text'];

			if ($companyName === '' && trim((string) ($savedAddress['company_name'] ?? '')) !== '') {
				$companyName = mb_substr(trim((string) $savedAddress['company_name']), 0, 128);
			}
			if ($taxOffice === '' && trim((string) ($savedAddress['tax_office'] ?? '')) !== '') {
				$taxOffice = mb_substr(trim((string) $savedAddress['tax_office']), 0, 64);
			}
			if ($taxNumber === '' && trim((string) ($savedAddress['tax_number'] ?? '')) !== '') {
				$taxNumber = mb_substr(preg_replace('/\D+/', '', (string) $savedAddress['tax_number']), 0, 20);
			}
		}

		if (!Validate::isName($name)) {
			return self::fail(translate('Please enter a valid full name'));
		}

		if ($idUser > 0 && $customerEmail === '') {
			$current = Customer::getCurrent();
			$customerEmail = strtolower(trim((string) ($current['email'] ?? '')));
		}

		if ($idUser <= 0) {
			if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
				return self::fail(translate('Please enter a valid email'));
			}
		} elseif ($customerEmail !== '' && !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
			return self::fail(translate('Please enter a valid email'));
		}

		if (!Customer::isValidPhone($phone)) {
			return self::fail(translate('Please enter a valid phone number'));
		}

		if ($city === '' || $district === '' || $address === '') {
			return self::fail(translate('Complete delivery address'));
		}

		$paymentMethods = Module::getPaymentMethods();

		if ($paymentMethods !== []) {
			if (!isset($paymentMethods[$payment])) {
				return self::fail(translate('Invalid payment method'));
			}
		} else {
			return self::fail(translate('No payment methods available'));
		}

		if (empty($data['accept_terms'])) {
			return self::fail(translate('Must accept terms'));
		}

		if ($payment === 'cash_on_delivery' && Cart::hasVirtualProducts($cart)) {
			return self::fail(translate('COD not for virtual'));
		}

		$requiresShipping = Cart::requiresShipping($cart);
		$idCargo = (int) ($data['id_cargo'] ?? 0);
		$cargoCompanyName = '';

		if (!class_exists('Cargo') && is_file(dirname(__DIR__) . '/core/Cargo.php')) {
			require_once dirname(__DIR__) . '/core/Cargo.php';
		}

		if (class_exists('Cargo') && $requiresShipping) {
			$activeCargos = Cargo::getList(true);

			if ($activeCargos !== []) {
				if ($idCargo <= 0) {
					$idCargo = Cargo::getSelectedId();
				}

				$cargoRow = Cargo::getById($idCargo);

				if (!$cargoRow || empty($cargoRow['active'])) {
					return self::fail('Lütfen bir kargo firması seçin');
				}

				Cargo::setSelectedId($idCargo);
				$cargoCompanyName = (string) ($cargoRow['name'] ?? '');
				$data['id_cargo'] = $idCargo;
			}
		}

		self::setSelectedPaymentMethod($payment);

		// "Önce ödeme" isteyen modül (sanal POS gibi): sipariş henüz OLUŞTURULMAZ.
		// Form verisi session'da bekletilir, müşteri kart sayfasına yönlendirilir.
		// Banka onayından sonra modül Order::placePending() ile siparişi oluşturur.
		if (empty($data['_payment_done'])) {
			$prePayModule = Module::getPaymentModule($payment);

			if ($prePayModule && $prePayModule->paysBeforeOrder) {
				$paymentPage = $prePayModule->getPaymentPageUrl();

				if ($paymentPage === '') {
					return self::fail('Ödeme sayfası yapılandırılmamış');
				}

				$_SESSION['pending_order_data'] = $data;

				return [
					'success' => true,
					'message' => 'Ödeme sayfasına yönlendiriliyorsunuz',
					'id_order' => 0,
					'reference' => '',
					'redirect' => $paymentPage,
				];
			}
		}

		$subtotal = (float) $cart['total'];
		$checkoutSummary = Coupon::getCheckoutSummary($subtotal, $cart);
		$couponDiscount = (float) ($checkoutSummary['coupon_discount'] ?? 0);
		$promotionDiscount = (float) ($checkoutSummary['promotion_discount'] ?? 0);
		$promotionName = (string) ($checkoutSummary['promotion_name'] ?? '');
		$paymentDiscount = (float) ($checkoutSummary['payment_discount'] ?? 0);
		$paymentDiscountLabel = (string) ($checkoutSummary['payment_discount_label'] ?? '');
		$appliedCoupon = Coupon::getApplied();
		$couponCode = $appliedCoupon ? (string) $appliedCoupon['code'] : '';
		$totals = self::getCheckoutTotals(
			$subtotal,
			(float) $checkoutSummary['discount'],
			$cart,
			null,
			$payment
		);

		if (!empty($data['_reference'])) {
			$reference = (string) $data['_reference'];
			$existingId = (int) DB::getValue('SELECT id_order FROM orders WHERE reference = ? LIMIT 1', [$reference]);

			if ($existingId > 0) {
				return [
					'success' => true,
					'message' => translate('Order placed'),
					'id_order' => $existingId,
					'reference' => $reference,
					'redirect' => '',
				];
			}
		} else {
			$reference = self::generateReference();
		}

		global $db;

		try {
			$db->beginTransaction();

			foreach ($cart['items'] as $item) {
				$idVariation = (int) ($item['id_variation'] ?? 0);
				$product = Product::getById((int) $item['id_product']);

				if (!$product || !Product::isInStock($product, (int) $item['qty'], $idVariation)) {
					throw new RuntimeException('Sepette stokta olmayan ürün var: ' . ($item['product_name'] ?? ''));
				}

				if (Product::isPackProduct($product)) {
					throw new RuntimeException('Set ürünü sepete doğrudan eklenemez: ' . ($item['product_name'] ?? ''));
				}

				if (!Product::decreaseStock((int) $item['id_product'], (int) $item['qty'], $idVariation)) {
					throw new RuntimeException('Stok yetersiz: ' . ($item['product_name'] ?? ''));
				}
			}

			$idOrder = DB::insert('orders', [
				'id_user' => $idUser,
				'reference' => $reference,
				'status' => self::STATUS_PENDING,
				'payment_method' => $payment,
				'customer_name' => $name,
				'customer_phone' => $phone,
				'customer_email' => $customerEmail,
				'company_name' => $companyName,
				'tax_office' => $taxOffice,
				'tax_number' => $taxNumber,
				'address_city' => $city,
				'address_district' => $district,
				'address_text' => $address,
				'note' => $note,
				'coupon_code' => $couponCode,
				'coupon_discount' => $couponDiscount,
				'promotion_name' => $promotionName,
				'promotion_discount' => $promotionDiscount,
				'payment_discount' => $paymentDiscount,
				'payment_discount_label' => mb_substr($paymentDiscountLabel, 0, 128),
				'cargo_company' => $cargoCompanyName,
				'subtotal' => $totals['subtotal'],
				'shipping' => $totals['shipping'],
				'total' => $totals['total'],
			]);

			if (!$idOrder) {
				throw new RuntimeException('Sipariş kaydedilemedi');
			}

			foreach ($cart['items'] as $item) {
				$ok = DB::insert('order_detail', [
					'id_order' => (int) $idOrder,
					'id_product' => (int) $item['id_product'],
					'id_variation' => (int) ($item['id_variation'] ?? 0),
					'product_name' => $item['product_name'],
					'variation_label' => (string) ($item['variation_label'] ?? ''),
					'price' => (float) $item['price'],
					'qty' => (int) $item['qty'],
					'total' => (float) $item['line_total'],
				]);

				if (!$ok) {
					throw new RuntimeException('Sipariş satırı kaydedilemedi');
				}
			}

			$db->commit();
			Cart::clear();

			if (class_exists('Marketplace', false)) {
				foreach ($cart['items'] as $item) {
					$idProd = (int) ($item['id_product'] ?? 0);
					if ($idProd > 0) {
						Marketplace::syncProductStockAcrossPlatforms($idProd, null);
					}
				}
			}

			if ($couponCode !== '') {
				Coupon::markUsed($couponCode);
				Coupon::remove();
			}

			Notification::orderPlaced($idUser, $reference, (float) $totals['total'], (int) $idOrder);

			if ($idUser <= 0) {
				self::grantGuestOrderAccess((int) $idOrder);
			}

			$placedOrder = self::getByIdAdmin((int) $idOrder);

			if ($placedOrder && class_exists('Module', false)) {
				Module::runHook('order.placed', [$placedOrder]);
			}

			if ($idAddress === 0 && $idUser > 0 && !empty($data['save_address'])) {
				Address::save($idUser, [
					'label' => isset($data['address_label']) ? $data['address_label'] : '',
					'full_name' => $name,
					'phone' => $phone,
					'company_name' => $companyName,
					'tax_office' => $taxOffice,
					'tax_number' => $taxNumber,
					'city' => $city,
					'district' => $district,
					'address_text' => $address,
					'is_default' => isset($data['set_default_address']) ? $data['set_default_address'] : 0,
				]);
			}

			// Ödeme modülünü devreye al: PayTR gibi modüller redirect dönebilir.
			// Sipariş bu noktada kaydedildi; modül hatası siparişi iptal etmemeli.
			// Ödeme zaten alındıysa (_payment_done) processPayment atlanır.
			$redirect = '';
			$paymentModule = empty($data['_payment_done']) ? Module::getPaymentModule($payment) : null;

			if ($paymentModule) {
				try {
					$orderRow = self::getByIdAdmin((int) $idOrder);
					$process = $paymentModule->processPayment($orderRow ? $orderRow : []);

					if (!empty($process['redirect'])) {
						$redirect = (string) $process['redirect'];
					}
				} catch (Exception $e) {
					// Modül hatasında standart onay sayfasına devam edilir
				}
			}

			return [
				'success' => true,
				'message' => translate('Order placed'),
				'id_order' => (int) $idOrder,
				'reference' => $reference,
				'redirect' => $redirect,
			];
		} catch (Exception $e) {
			if ($db->inTransaction()) {
				$db->rollBack();
			}

			return self::fail(translate('Order create failed'));
		}
	}

	/** Kart sayfası bekleyen bir checkout var mı? */
	public static function hasPendingPayment(): bool
	{
		return !empty($_SESSION['pending_order_data']);
	}

	/**
	 * Banka onayı alındıktan sonra ödeme modülü tarafından çağrılır:
	 * session'da bekletilen checkout verisiyle siparişi gerçekten oluşturur.
	 * Stok ve adres tekrar doğrulanır (kart sayfasında beklerken değişmiş olabilir).
	 */
	public static function placePending(): array
	{
		$data = isset($_SESSION['pending_order_data']) ? $_SESSION['pending_order_data'] : null;

		if (!is_array($data)) {
			return self::fail('Bekleyen sipariş bulunamadı, lütfen tekrar deneyin');
		}

		$data['_payment_done'] = 1;
		$result = self::place($data);

		if ($result['success']) {
			unset($_SESSION['pending_order_data']);
		}

		return $result;
	}

	/** Müşteri ödemeden vazgeçtiyse bekleyen checkout verisini temizler */
	public static function clearPendingPayment(): void
	{
		unset($_SESSION['pending_order_data']);
	}

	public static function grantGuestOrderAccess(int $idOrder): void
	{
		if ($idOrder <= 0 || session_status() !== PHP_SESSION_ACTIVE) {
			return;
		}

		if (!isset($_SESSION['guest_order_ids']) || !is_array($_SESSION['guest_order_ids'])) {
			$_SESSION['guest_order_ids'] = [];
		}

		$_SESSION['guest_order_ids'][$idOrder] = time();

		if (count($_SESSION['guest_order_ids']) > 5) {
			asort($_SESSION['guest_order_ids']);
			$_SESSION['guest_order_ids'] = array_slice($_SESSION['guest_order_ids'], -5, null, true);
		}
	}

	public static function guestCanViewOrder(int $idOrder): bool
	{
		return $idOrder > 0
			&& session_status() === PHP_SESSION_ACTIVE
			&& !empty($_SESSION['guest_order_ids'][$idOrder]);
	}

	public static function getByIdForViewer(int $idOrder): ?array
	{
		$idUser = Customer::getId();

		if ($idUser > 0) {
			$order = self::getByIdForUser($idOrder, $idUser);

			if ($order) {
				return $order;
			}
		}

		// Ödeme dönüşü / guest: session'da erişim verilmişse id_user fark etmeksizin göster
		// (PSP dönünce oturum düşse bile ref + grant ile sipariş görülebilir)
		if (!self::guestCanViewOrder($idOrder)) {
			return null;
		}

		$order = DB::getRowSafe('orders', 'id_order = ?', [$idOrder]);

		if (!$order) {
			return null;
		}

		return self::hydrateCustomerOrder($order, (int) ($order['id_user'] ?? 0));
	}

	public static function getByIdForUser(int $idOrder, int $idUser): ?array
	{
		$order = DB::getRowSafe('orders', 'id_order = ? AND id_user = ?', [$idOrder, $idUser]);

		if (!$order) {
			return null;
		}

		return self::hydrateCustomerOrder($order, $idUser);
	}

	private static function hydrateCustomerOrder(array $order, int $idUser): array
	{
		$idOrder = (int) $order['id_order'];

		if (self::isPaymentAccepted((int) $order['status']) && class_exists('VirtualProduct', false)) {
			VirtualProduct::fulfillOrder($idOrder);
		}

		$order['status_label'] = self::getStatusLabel((int) $order['status']);
		$order['payment_label'] = self::getPaymentLabel($order['payment_method']);
		$order['subtotal'] = (float) ($order['subtotal'] ?? 0);
		$order['shipping'] = (float) ($order['shipping'] ?? 0);
		$order['total'] = (float) ($order['total'] ?? 0);
		$order['subtotal_formatted'] = Tools::displayPrice($order['subtotal']);
		$order['shipping_formatted'] = $order['shipping'] > 0
			? Tools::displayPrice($order['shipping'])
			: translate('Free');
		$order['total_formatted'] = Tools::displayPrice($order['total']);
		$order['coupon_code'] = (string) ($order['coupon_code'] ?? '');
		$order['coupon_discount'] = (float) ($order['coupon_discount'] ?? 0);
		$order['coupon_discount_formatted'] = Tools::displayPrice($order['coupon_discount']);
		$order['promotion_name'] = (string) ($order['promotion_name'] ?? '');
		$order['promotion_discount'] = (float) ($order['promotion_discount'] ?? 0);
		$order['promotion_discount_formatted'] = Tools::displayPrice($order['promotion_discount']);
		$order['payment_discount'] = (float) ($order['payment_discount'] ?? 0);
		$order['payment_discount_formatted'] = Tools::displayPrice($order['payment_discount']);
		$order['payment_discount_label'] = (string) ($order['payment_discount_label'] ?? '');
		$order['date_formatted'] = Tools::formatDate3($order['date_add']);
		$order['items'] = DB::execute(
			'SELECT od.*, p.barcode, p.stock_code, p.vat, p.product_type, p.virtual_kind
			FROM order_detail od
			LEFT JOIN products p ON p.id_product = od.id_product
			WHERE od.id_order = ?
			ORDER BY od.id_order_detail ASC',
			[$idOrder]
		) ?: [];

		foreach ($order['items'] as &$item) {
			$item['price_formatted'] = Tools::displayPrice($item['price']);
			$item['total_formatted'] = Tools::displayPrice($item['total']);
			VirtualProduct::enrichOrderItem($item, $idUser, (int) $order['status']);
		}
		unset($item);

		return $order;
	}

	public static function getUserOrders(int $idUser): array
	{
		$rows = DB::execute(
			'SELECT * FROM orders WHERE id_user = ? ORDER BY id_order DESC',
			[$idUser]
		);

		if (!$rows) {
			return [];
		}

		foreach ($rows as &$row) {
			$row['status_label'] = self::getStatusLabel((int) $row['status']);
			$row['payment_label'] = self::getPaymentLabel($row['payment_method']);
			$row['total_formatted'] = Tools::displayPrice($row['total']);
			$row['date_formatted'] = Tools::formatDate3($row['date_add']);
		}
		unset($row);

		return $rows;
	}

	public static function enrichUserOrderRows(array $rows): array
	{
		foreach ($rows as &$row) {
			$idOrder = (int) $row['id_order'];
			$status = (int) $row['status'];

			$row['status_label'] = self::getStatusLabel($status);
			$row['status_class'] = self::getStatusBadgeClass($status);
			$row['payment_label'] = self::getPaymentLabel($row['payment_method']);
			$row['total_formatted'] = Tools::displayPrice($row['total']);
			$row['date_formatted'] = Tools::formatDate3($row['date_add']);
			$row['is_ongoing'] = in_array($status, [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_SHIPPED], true);
			$row['is_cancelled'] = $status === self::STATUS_CANCELLED;
			$row['is_returned'] = in_array($status, [self::STATUS_RETURNED, self::STATUS_RETURN_PENDING], true);
			$row['is_return_pending'] = $status === self::STATUS_RETURN_PENDING;
			$row['is_return_completed'] = $status === self::STATUS_RETURNED;
			$row['is_delivered'] = $status === self::STATUS_DELIVERED;

			$items = DB::execute(
				'SELECT od.*, i.id_image
				FROM order_detail od
				LEFT JOIN images i ON i.id_product = od.id_product AND i.cover = 1
				WHERE od.id_order = ?
				ORDER BY od.id_order_detail ASC',
				[$idOrder]
			) ?: [];

			$row['item_count'] = count($items);
			$first = $items[0] ?? null;
			$row['thumb_product'] = $first['product_name'] ?? '';
			$row['thumb_url'] = !empty($first['id_image'])
				? Product::getImageUrl((int) $first['id_image'])
				: '../img/default.jpg';
			$row['first_product_id'] = (int) ($first['id_product'] ?? 0);
			$row['can_review'] = $status === self::STATUS_DELIVERED && $row['first_product_id'] > 0;
		}
		unset($row);

		return $rows;
	}

	public static function canCustomerCancel(int $status): bool
	{
		return in_array($status, [self::STATUS_PENDING, self::STATUS_PROCESSING], true);
	}

	/** Ana sayfa: teslim edilmemiş son siparişler (en fazla 3). */
	public static function getActiveOrdersForViewer(int $limit = 3): array
	{
		$limit = max(1, min(3, $limit));
		$exclude = [self::STATUS_DELIVERED, self::STATUS_CANCELLED, self::STATUS_RETURNED, self::STATUS_RETURN_PENDING];
		$idUser = Customer::getId();
		$rows = [];

		if ($idUser > 0) {
			$rows = DB::execute(
				'SELECT * FROM orders
				 WHERE id_user = ? AND status NOT IN (?, ?, ?, ?)
				 ORDER BY id_order DESC
				 LIMIT ' . $limit,
				[$idUser, self::STATUS_DELIVERED, self::STATUS_CANCELLED, self::STATUS_RETURNED, self::STATUS_RETURN_PENDING]
			) ?: [];
		} elseif (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['guest_order_ids']) && is_array($_SESSION['guest_order_ids'])) {
			$ids = array_values(array_filter(array_map('intval', array_keys($_SESSION['guest_order_ids']))));

			if ($ids !== []) {
				$placeholders = implode(',', array_fill(0, count($ids), '?'));
				$params = array_merge($ids, $exclude);
				$rows = DB::execute(
					'SELECT * FROM orders
					 WHERE id_order IN (' . $placeholders . ')
					   AND id_user = 0
					   AND status NOT IN (?, ?, ?, ?)
					 ORDER BY id_order DESC
					 LIMIT ' . $limit,
					$params
				) ?: [];
			}
		}

		return array_map([self::class, 'enrichActiveOrderCard'], $rows);
	}

	public static function getStatusProgress(int $status): int
	{
		switch ((int) $status) {
			case self::STATUS_PENDING:
				return 10;
			case self::STATUS_PROCESSING:
				return 25;
			case self::STATUS_SHIPPED:
				return 70;
			case self::STATUS_DELIVERED:
				return 100;
			case self::STATUS_RETURNED:
			case self::STATUS_RETURN_PENDING:
				return 100;
			default:
				return 5;
		}
	}

	public static function getStatusStepLabel(int $status): string
	{
		switch ((int) $status) {
			case self::STATUS_PENDING:
				return 'Sipariş alındı';
			case self::STATUS_PROCESSING:
				return 'Hazırlanıyor';
			case self::STATUS_SHIPPED:
				return 'Kuryeye verildi';
			case self::STATUS_DELIVERED:
				return translate('Order status delivered');
			case self::STATUS_RETURNED:
				return translate('Order status returned');
			case self::STATUS_RETURN_PENDING:
				return translate('Order status return pending');
			default:
				return self::getStatusLabel((int) $status);
		}
	}

	private static function enrichActiveOrderCard(array $order): array
	{
		$status = (int) ($order['status'] ?? 0);

		$order['status_label'] = self::getStatusLabel($status);
		$order['status_step_label'] = self::getStatusStepLabel($status);
		$order['status_progress'] = self::getStatusProgress($status);
		$order['time_ago'] = Tools::timeAgo((string) ($order['date_add'] ?? ''));
		$order['total_formatted'] = Tools::displayPrice((float) ($order['total'] ?? 0));

		return $order;
	}

	public static function trackByReference(string $reference, ?int $idUser = null): ?array
	{
		$reference = strtoupper(trim($reference));

		if ($reference === '' || !self::isValidPublicReference($reference)) {
			return null;
		}

		if ($idUser) {
			return self::getByReferenceForUser($reference, $idUser);
		}

		$order = DB::getRowSafe('orders', 'reference = ?', [$reference]);

		if (!$order) {
			return null;
		}

		return [
			'id_order' => (int) $order['id_order'],
			'reference' => $order['reference'],
			'status' => (int) $order['status'],
			'status_label' => self::getStatusLabel((int) $order['status']),
			'date_formatted' => Tools::formatDate3($order['date_add']),
			'public' => true,
		];
	}

	public static function getByReferenceForUser(string $reference, int $idUser): ?array
	{
		$reference = strtoupper(trim($reference));
		$order = DB::getRowSafe('orders', 'reference = ? AND id_user = ?', [$reference, $idUser]);

		if (!$order) {
			return null;
		}

		return self::getByIdForUser((int) $order['id_order'], $idUser);
	}

	public static function getStatusOptions(): array
	{
		return [
			self::STATUS_PENDING => self::getStatusLabel(self::STATUS_PENDING),
			self::STATUS_PROCESSING => self::getStatusLabel(self::STATUS_PROCESSING),
			self::STATUS_SHIPPED => self::getStatusLabel(self::STATUS_SHIPPED),
			self::STATUS_DELIVERED => self::getStatusLabel(self::STATUS_DELIVERED),
			self::STATUS_CANCELLED => self::getStatusLabel(self::STATUS_CANCELLED),
			self::STATUS_RETURN_PENDING => self::getStatusLabel(self::STATUS_RETURN_PENDING),
			self::STATUS_RETURNED => self::getStatusLabel(self::STATUS_RETURNED),
		];
	}

	public static function getStatusBadgeClass(int $status): string
	{
		$map = [
			self::STATUS_PENDING => 'pending',
			self::STATUS_PROCESSING => 'processing',
			self::STATUS_SHIPPED => 'shipped',
			self::STATUS_DELIVERED => 'delivered',
			self::STATUS_CANCELLED => 'cancelled',
			self::STATUS_RETURN_PENDING => 'return-pending',
			self::STATUS_RETURNED => 'returned',
		];

		return $map[$status] ?? 'default';
	}

	public static function enrichAdminRows(array $rows): array
	{
		foreach ($rows as &$row) {
			$row['location'] = trim($row['address_city'] . '/' . $row['address_district'], '/');
			$row['status_class'] = self::getStatusBadgeClass((int) $row['status']);
			$row['date_full'] = date('Y-m-d H:i:s', strtotime($row['date_add']));

			$cargoName = trim((string) ($row['cargo_company'] ?? ''));
			$cargo = class_exists('Cargo', false) && $cargoName !== '' ? Cargo::getByName($cargoName) : null;
			$row['cargo_name'] = $cargoName;
			$row['cargo_logo_url'] = class_exists('Cargo', false)
				? (Cargo::resolveLogoUrl($cargoName, $cargo ? (int) $cargo['id_cargo'] : null) ?? '')
				: '';
			$row['tracking_url'] = '';

			$trackingNumber = trim((string) ($row['tracking_number'] ?? ''));

			if ($trackingNumber !== '' && class_exists('Cargo', false)) {
				$row['tracking_url'] = Cargo::buildTrackingUrl($trackingNumber, $cargo ?: $cargoName);
			}

			$firstItem = DB::execute(
				'SELECT od.product_name, od.id_product, i.id_image
				 FROM order_detail od
				 LEFT JOIN products p ON p.id_product = od.id_product
				 LEFT JOIN images i ON p.id_product = i.id_product
				 WHERE od.id_order = ?
				 ORDER BY od.id_order_detail ASC
				 LIMIT 1',
				[(int) $row['id_order']]
			);

			$item = $firstItem[0] ?? null;
			$row['thumb_product'] = $item['product_name'] ?? '';
			if ($item['id_image'])
				$row['thumb_url'] = Product::getImageUrl($item['id_image']);
			else
				$row['thumb_url'] = '../img/default.jpg';
		}
		unset($row);

		return $rows;
	}

	public static function getDashboardRecentOrders(int $limit = 15): array
	{
		return self::enrichAdminRows(self::getAdminList(0, $limit, 0));
	}

	public static function getAdminList(int $status = 0, int $limit = 30, int $offset = 0, string $dateFrom = '', string $dateTo = '', array $filters = []): array
	{
		$sql = 'SELECT * FROM orders WHERE 1=1';
		$params = [];

		self::applyAdminFilters($sql, $params, $status, $dateFrom, $dateTo, $filters);

		$sql .= ' ORDER BY id_order DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

		$rows = DB::execute($sql, $params) ?: [];

		foreach ($rows as &$row) {
			$row['status_label'] = self::getStatusLabel((int) $row['status']);
			$row['payment_label'] = self::getPaymentLabel($row['payment_method']);
			$row['total_formatted'] = Tools::displayPrice($row['total']);
			$row['date_formatted'] = Tools::formatDate3($row['date_add']);
		}
		unset($row);

		return $rows;
	}

	public static function countAdmin(int $status = 0, string $dateFrom = '', string $dateTo = '', array $filters = []): int
	{
		$sql = 'SELECT COUNT(*) FROM orders WHERE 1=1';
		$params = [];

		self::applyAdminFilters($sql, $params, $status, $dateFrom, $dateTo, $filters);

		return (int) DB::getValue($sql, $params);
	}

	/** @return array{reference: string, customer: string, date_from: string, date_to: string} */
	public static function normalizeAdminFilters(array $input): array
	{
		return [
			'reference' => mb_substr(trim((string) ($input['reference'] ?? '')), 0, 32),
			'customer' => mb_substr(trim((string) ($input['customer'] ?? '')), 0, 128),
			'date_from' => trim((string) ($input['date_from'] ?? '')),
			'date_to' => trim((string) ($input['date_to'] ?? '')),
		];
	}

	/** @return array<string, int|string> */
	public static function buildAdminFilterQuery(int $status, array $filters): array
	{
		$query = [];

		foreach (['reference', 'customer', 'date_from', 'date_to'] as $key) {
			$value = trim((string) ($filters[$key] ?? ''));

			if ($value !== '') {
				$query[$key] = $value;
			}
		}

		if ($status > 0) {
			$query['status'] = $status;
		}

		return $query;
	}

	private static function applyAdminFilters(string &$sql, array &$params, int $status, string $dateFrom, string $dateTo, array $filters = []): void
	{
		if ($status > 0) {
			$sql .= ' AND status = ?';
			$params[] = $status;
		}

		self::applyDateFilters($sql, $params, $dateFrom, $dateTo);

		$reference = trim((string) ($filters['reference'] ?? ''));

		if ($reference !== '') {
			$sql .= ' AND reference LIKE ?';
			$params[] = '%' . $reference . '%';
		}

		$customer = trim((string) ($filters['customer'] ?? ''));

		if ($customer !== '') {
			$sql .= ' AND customer_name LIKE ?';
			$params[] = '%' . $customer . '%';
		}
	}

	private static function applyDateFilters(string &$sql, array &$params, string $dateFrom, string $dateTo): void
	{
		$dateFrom = trim($dateFrom);
		$dateTo = trim($dateTo);

		if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
			$dateFrom .= ' 00:00:00';
		}

		if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
			$dateTo .= ' 23:59:59';
		}

		if ($dateFrom !== '') {
			$sql .= ' AND date_add >= ?';
			$params[] = $dateFrom;
		}

		if ($dateTo !== '') {
			$sql .= ' AND date_add <= ?';
			$params[] = $dateTo;
		}
	}

	public static function getByIdAdmin(int $idOrder): ?array
	{
		$order = DB::getRowSafe('orders', 'id_order = ?', [$idOrder]);

		if (!$order) {
			return null;
		}

		$order['status_label'] = self::getStatusLabel((int) $order['status']);
		$order['payment_label'] = self::getPaymentLabel($order['payment_method']);
		$order['subtotal_formatted'] = Tools::displayPrice($order['subtotal']);
		$order['shipping_formatted'] = Tools::displayPrice($order['shipping']);
		$order['total_formatted'] = Tools::displayPrice($order['total']);
		$order['coupon_discount'] = (float) ($order['coupon_discount'] ?? 0);
		$order['coupon_discount_formatted'] = Tools::displayPrice($order['coupon_discount']);
		$order['promotion_discount'] = (float) ($order['promotion_discount'] ?? 0);
		$order['promotion_discount_formatted'] = Tools::displayPrice($order['promotion_discount']);
		$order['payment_discount'] = (float) ($order['payment_discount'] ?? 0);
		$order['payment_discount_formatted'] = Tools::displayPrice($order['payment_discount']);
		$order['payment_discount_label'] = (string) ($order['payment_discount_label'] ?? '');
		$order['date_formatted'] = Tools::formatDate3($order['date_add']);
		$order['items'] = DB::execute(
			'SELECT od.*, p.barcode, p.stock_code, p.vat, p.product_type, p.virtual_kind, p.virtual_file_name
			FROM order_detail od
			LEFT JOIN products p ON p.id_product = od.id_product
			WHERE od.id_order = ?
			ORDER BY od.id_order_detail ASC',
			[$idOrder]
		) ?: [];

		foreach ($order['items'] as &$item) {
			$item['price_formatted'] = Tools::displayPrice($item['price']);
			$item['total_formatted'] = Tools::displayPrice($item['total']);
			VirtualProduct::enrichAdminOrderItem($item);
		}
		unset($item);

		return $order;
	}

	public static function setStatusQuiet(int $idOrder, int $status): bool
	{
		self::ensureSchema();

		if (!isset(self::getStatusOptions()[$status])) {
			return false;
		}

		$updated = DB::update(
			'orders',
			['status' => $status],
			'id_order = :id_order',
			['id_order' => $idOrder]
		);

		return $updated !== false;
	}

	public static function updateStatus(int $idOrder, int $status): array
	{
		return self::updateFromApi($idOrder, ['status' => $status]);
	}

	public static function updateFromApi(int $idOrder, array $data): array
	{
		self::ensureSchema();

		$order = self::getByIdAdmin($idOrder);

		if (!$order) {
			return self::fail('Sipariş bulunamadı');
		}

		$row = [];
		$oldStatus = (int) $order['status'];

		if (array_key_exists('status', $data)) {
			$status = (int) $data['status'];

			if (!isset(self::getStatusOptions()[$status])) {
				return self::fail('Geçersiz sipariş durumu');
			}

			$row['status'] = $status;
		}

		if (array_key_exists('cargo_company', $data)) {
			$row['cargo_company'] = mb_substr(trim(strip_tags((string) $data['cargo_company'])), 0, 64);
		}

		if (array_key_exists('tracking_number', $data)) {
			$row['tracking_number'] = mb_substr(trim(strip_tags((string) $data['tracking_number'])), 0, 64);
		}

		$newStatus = (int) ($row['status'] ?? $oldStatus);

		if (isset($row['status']) && $newStatus === self::STATUS_DELIVERED && $oldStatus !== self::STATUS_DELIVERED) {
			$row['date_delivered'] = date('Y-m-d H:i:s');
		}

		if ($row === []) {
			return self::fail('Güncellenecek alan yok');
		}

		$newStatus = (int) ($row['status'] ?? $oldStatus);

		if (
			isset($row['status'])
			&& $newStatus === $oldStatus
			&& !array_key_exists('cargo_company', $row)
			&& !array_key_exists('tracking_number', $row)
		) {
			return self::ok('Sipariş durumu zaten güncel');
		}

		if (
			!isset($row['status'])
			&& array_key_exists('cargo_company', $row)
			&& $row['cargo_company'] === (string) ($order['cargo_company'] ?? '')
			&& array_key_exists('tracking_number', $row)
			&& $row['tracking_number'] === (string) ($order['tracking_number'] ?? '')
		) {
			return self::ok('Sipariş bilgileri zaten güncel');
		}

		DB::update(
			'orders',
			$row,
			'id_order = :id_order',
			['id_order' => $idOrder]
		);

		if ($newStatus === self::STATUS_CANCELLED && $oldStatus !== self::STATUS_CANCELLED) {
			self::restoreStock($idOrder);
		}

		if (isset($row['status']) && $newStatus !== $oldStatus) {
			$order['status'] = $newStatus;
			Notification::orderStatusChanged($order, $oldStatus, $newStatus);

			if (self::isPaymentAccepted($newStatus) && !self::isPaymentAccepted($oldStatus)) {
				VirtualProduct::fulfillOrder($idOrder);
			}
		}

		$updatedOrder = self::getByIdAdmin($idOrder) ?: $order;

		if (class_exists('Module', false)) {
			Module::runHook('order.updated', [$updatedOrder, $oldStatus, $row]);
		}

		return self::ok('Sipariş güncellendi');
	}

	public static function restoreStock(int $idOrder): void
	{
		$items = DB::execute(
			'SELECT id_product, qty FROM order_detail WHERE id_order = ?',
			[$idOrder]
		) ?: [];

		foreach ($items as $item) {
			Product::increaseStock((int) $item['id_product'], (int) $item['qty']);
		}
	}

	/** Web API: siparişlere satır ve müşteri e-postası ekler */
	public static function attachApiDetails(array $orders): array
	{
		if ($orders === []) {
			return [];
		}

		$orderIds = array_map(static fn(array $row): int => (int) $row['id_order'], $orders);
		$userIds = array_values(array_unique(array_filter(array_map(
			static fn(array $row): int => (int) ($row['id_user'] ?? 0),
			$orders
		))));

		$linesByOrder = self::getLinesGroupedByOrderIds($orderIds);
		$emailsByUser = self::getEmailsByUserIds($userIds);
		$prepared = [];

		foreach ($orders as $order) {
			$idOrder = (int) $order['id_order'];
			$idUser = (int) ($order['id_user'] ?? 0);
			$order['items'] = $linesByOrder[$idOrder] ?? [];
			$order['customer_email'] = $emailsByUser[$idUser] ?? '';
			$prepared[] = $order;
		}

		return $prepared;
	}

	private static function getLinesGroupedByOrderIds(array $orderIds): array
	{
		$orderIds = array_values(array_filter(array_map('intval', $orderIds)));

		if ($orderIds === []) {
			return [];
		}

		$placeholders = implode(',', array_fill(0, count($orderIds), '?'));
		$rows = DB::execute(
			'SELECT od.*, p.barcode, p.stock_code, p.vat
			FROM order_detail od
			LEFT JOIN products p ON p.id_product = od.id_product
			WHERE od.id_order IN (' . $placeholders . ')
			ORDER BY od.id_order ASC, od.id_order_detail ASC',
			$orderIds
		) ?: [];

		$grouped = [];

		foreach ($rows as $row) {
			$grouped[(int) $row['id_order']][] = $row;
		}

		return $grouped;
	}

	private static function getEmailsByUserIds(array $userIds): array
	{
		$userIds = array_values(array_filter(array_map('intval', $userIds)));

		if ($userIds === []) {
			return [];
		}

		$placeholders = implode(',', array_fill(0, count($userIds), '?'));
		$rows = DB::execute(
			'SELECT id_user, email FROM users WHERE id_user IN (' . $placeholders . ')',
			$userIds
		) ?: [];

		$map = [];

		foreach ($rows as $row) {
			$map[(int) $row['id_user']] = (string) ($row['email'] ?? '');
		}

		return $map;
	}

	private static function ok(string $message): array
	{
		return [
			'success' => true,
			'message' => $message,
		];
	}

	public static function reserveReference(): string
	{
		return self::generateReference();
	}

	/** @return array<string, string> */
	public static function getReferenceSuffixModes(): array
	{
		return [
			'sequential' => 'Sequential number (00123)',
			'timestamp' => 'Unix timestamp (time)',
			'datetime' => 'Date and time (YmdHis)',
			'random' => 'Random code',
			'date_random' => 'Date + random (ymd + code)',
		];
	}

	/** @return array{prefix: string, suffix_mode: string, pad: int} */
	public static function getReferenceSettings(): array
	{
		$configured = trim((string) Settings::get('ORDER_REF_SUFFIX_MODE')) !== ''
			|| trim((string) Settings::get('ORDER_REF_PREFIX')) !== '';

		$prefix = self::sanitizeReferencePrefix((string) Settings::get('ORDER_REF_PREFIX'));
		$mode = strtolower(trim((string) Settings::get('ORDER_REF_SUFFIX_MODE')));
		$modes = array_keys(self::getReferenceSuffixModes());

		if (!in_array($mode, $modes, true)) {
			$mode = $configured ? 'sequential' : 'date_random';
		}

		if (!$configured && $prefix === '') {
			$prefix = 'FS';
		}

		$pad = (int) Settings::get('ORDER_REF_PAD');

		if ($pad < 3 || $pad > 10) {
			$pad = 5;
		}

		return [
			'prefix' => $prefix,
			'suffix_mode' => $mode,
			'pad' => $pad,
		];
	}

	public static function previewReference(?array $override = null): string
	{
		$settings = $override ?? self::getReferenceSettings();

		return self::composeReference($settings, true);
	}

	public static function sanitizeReferencePrefix(string $prefix): string
	{
		$prefix = strtoupper(trim($prefix));
		$prefix = preg_replace('/[^A-Z0-9]/', '', $prefix) ?: '';

		return mb_substr($prefix, 0, 12);
	}

	public static function isValidPublicReference(string $reference): bool
	{
		$reference = strtoupper(trim($reference));

		return $reference !== '' && (bool) preg_match('/^[A-Z0-9]{4,32}$/', $reference);
	}

	private static function generateReference(): string
	{
		$settings = self::getReferenceSettings();

		do {
			$reference = self::composeReference($settings, false);
			$exists = DB::getValue('SELECT id_order FROM orders WHERE reference = ? LIMIT 1', [$reference]);
		} while ($exists);

		return $reference;
	}

	/** @param array{prefix: string, suffix_mode: string, pad: int} $settings */
	private static function composeReference(array $settings, bool $preview): string
	{
		$prefix = self::sanitizeReferencePrefix((string) ($settings['prefix'] ?? ''));
		$suffix = self::buildReferenceSuffix(
			(string) ($settings['suffix_mode'] ?? 'sequential'),
			(int) ($settings['pad'] ?? 5),
			$preview
		);
		$reference = strtoupper($prefix . $suffix);
		$reference = preg_replace('/[^A-Z0-9]/', '', $reference) ?: strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

		return mb_substr($reference, 0, 32);
	}

	private static function buildReferenceSuffix(string $mode, int $pad, bool $preview): string
	{
		switch ($mode) {
			case 'timestamp':
				return $preview ? (string) time() : (string) time();

			case 'datetime':
				return date('YmdHis');

			case 'random':
				return strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

			case 'date_random':
				return date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

			case 'sequential':
			default:
				$next = $preview ? self::peekReferenceCounter() : self::nextReferenceCounter();

				return str_pad((string) max(1, $next), max(3, min(10, $pad)), '0', STR_PAD_LEFT);
		}
	}

	private static function peekReferenceCounter(): int
	{
		self::ensureReferenceCounter();

		return max(1, (int) DB::getValue('SELECT value FROM settings WHERE title = ? LIMIT 1', ['ORDER_REF_COUNTER']) + 1);
	}

	private static function nextReferenceCounter(): int
	{
		self::ensureReferenceCounter();

		DB::execute(
			'UPDATE settings SET value = CAST(COALESCE(NULLIF(value, ""), "0") AS UNSIGNED) + 1 WHERE title = ?',
			['ORDER_REF_COUNTER']
		);

		return max(1, (int) DB::getValue('SELECT value FROM settings WHERE title = ? LIMIT 1', ['ORDER_REF_COUNTER']));
	}

	private static function ensureReferenceCounter(): void
	{
		$val = DB::getValue('SELECT value FROM settings WHERE title = ? LIMIT 1', ['ORDER_REF_COUNTER']);

		if ($val !== false && $val !== '') {
			return;
		}

		$start = (int) DB::getValue('SELECT COALESCE(MAX(id_order), 0) FROM orders');
		Settings::set('ORDER_REF_COUNTER', (string) $start);
	}

	private static function fail(string $message): array
	{
		return [
			'success' => false,
			'message' => $message,
		];
	}
}
