<?php

class Cart
{
	const SESSION_KEY = 'cart';
	const META_KEY = 'cart_meta';

	public static function init(): void
	{
		if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
			$_SESSION[self::SESSION_KEY] = [];
		}

		if (!isset($_SESSION[self::META_KEY]) || !is_array($_SESSION[self::META_KEY])) {
			$_SESSION[self::META_KEY] = [];
		}
	}

	/** @param array<string, string> $options */
	public static function cartKey(int $idProduct, int $idVariation = 0, array $options = []): string
	{
		$base = $idVariation > 0 ? $idProduct . ':' . $idVariation : (string) $idProduct;
		$options = ProductOption::normalizeSelections($options);

		if ($options === []) {
			return $base;
		}

		return $base . '::' . substr(md5(json_encode($options, JSON_UNESCAPED_UNICODE)), 0, 12);
	}

	/** @return array{id_product: int, id_variation: int, options: array<string, string>} */
	public static function parseCartKey(string $key): array
	{
		$options = [];
		$baseKey = $key;

		if (strpos($key, '::') !== false) {
			[$baseKey] = explode('::', $key, 2);
			$options = self::getLineMeta($key)['options'] ?? [];
		}

		if (strpos($baseKey, ':') !== false) {
			$parts = explode(':', $baseKey, 2);

			return [
				'id_product' => (int) ($parts[0] ?? 0),
				'id_variation' => (int) ($parts[1] ?? 0),
				'options' => $options,
			];
		}

		return [
			'id_product' => (int) $baseKey,
			'id_variation' => 0,
			'options' => $options,
		];
	}

	private static function getLineMeta(string $cartKey): array
	{
		self::init();

		return is_array($_SESSION[self::META_KEY][$cartKey] ?? null)
			? $_SESSION[self::META_KEY][$cartKey]
			: [];
	}

	private static function setLineMeta(string $cartKey, array $meta): void
	{
		self::init();
		$_SESSION[self::META_KEY][$cartKey] = $meta;
	}

	private static function clearLineMeta(string $cartKey): void
	{
		unset($_SESSION[self::META_KEY][$cartKey]);
	}

	private static function getLineQty(string $cartKey): int
	{
		$value = $_SESSION[self::SESSION_KEY][$cartKey] ?? 0;

		return max(0, (int) $value);
	}

	public static function resolveCartKey(int $idProduct, int $idVariation = 0, array $options = [], string $cartKey = ''): string
	{
		$cartKey = trim($cartKey);

		if ($cartKey !== '') {
			return $cartKey;
		}

		return self::cartKey($idProduct, $idVariation, $options);
	}

	/** @param array<string, string> $options */
	public static function add(int $idProduct, int $qty = 1, int $idVariation = 0, array $options = []): array
	{
		self::init();

		$product = Product::getById($idProduct);
		if (!$product) {
			return self::fail(translate('Product not found'));
		}

		if (Product::isPackProduct($product)) {
			return self::addPack($idProduct, $qty, $product);
		}

		$idVariation = max(0, $idVariation);
		$options = ProductOption::normalizeSelections($options);

		$optionError = ProductOption::validateSelections($idProduct, $options);
		if ($optionError !== null) {
			return self::fail($optionError);
		}

		if ($idVariation > 0) {
			$variation = ProductVariation::getById($idVariation);

			if (!$variation || (int) $variation['id_product'] !== $idProduct || (int) $variation['active'] !== 1) {
				return self::fail(translate('Product not found'));
			}
		} elseif (ProductVariation::hasVariations($idProduct)) {
			return self::fail('Lütfen ürün varyasyonu seçin');
		}

		$stock = Product::getStock($product, $idVariation);
		if ($stock <= 0) {
			return self::fail(translate('Out of stock'));
		}

		$qty = max(1, $qty);
		$key = self::cartKey($idProduct, $idVariation, $options);
		$current = self::getLineQty($key);
		$maxAllowed = $stock - $current;

		if ($maxAllowed <= 0) {
			return self::fail(translate('You have reached the maximum number of products'));
		}

		$added = min($qty, $maxAllowed);
		$_SESSION[self::SESSION_KEY][$key] = $current + $added;
		self::setLineMeta($key, ['options' => $options]);
		self::notifyChanged();

		return self::ok(translate('Added to cart'));
	}

	/** Set ürününü bileşenlerine parçalayarak sepete ekler. */
	private static function addPack(int $idPack, int $qty, array $packProduct): array
	{
		if (!Module::isEnabled('product-set')) {
			return self::fail('Ürün seti modülü aktif değil');
		}

		$file = dirname(__DIR__) . '/modules/product-set/lib/ProductSetService.php';
		if (!is_file($file)) {
			return self::fail('Ürün seti tanımı bulunamadı');
		}

		require_once $file;

		$items = ProductSetService::getItems($idPack);
		if ($items === []) {
			return self::fail('Bu sete henüz ürün eklenmemiş');
		}

		$qty = max(1, $qty);
		$available = ProductSetService::getAvailableStock($idPack);
		if ($available < $qty) {
			return self::fail(translate('Out of stock'));
		}

		foreach ($items as $item) {
			$idChild = (int) $item['id_product'];
			$need = max(1, (int) $item['qty']) * $qty;
			$child = Product::getById($idChild);

			if (!$child || Product::isPackProduct($child)) {
				return self::fail('Set bileşeni geçersiz: #' . $idChild);
			}

			if (ProductVariation::hasVariations($idChild)) {
				return self::fail('Varyasyonlu ürün sete eklenemez: ' . ($child['product_name'] ?? ''));
			}

			$key = self::cartKey($idChild, 0, []);
			$current = self::getLineQty($key);
			$stock = Product::getStock($child, 0);

			if ($stock - $current < $need) {
				return self::fail(translate('Out of stock') . ': ' . ($child['product_name'] ?? ''));
			}
		}

		foreach ($items as $item) {
			$idChild = (int) $item['id_product'];
			$need = max(1, (int) $item['qty']) * $qty;
			$result = self::add($idChild, $need, 0, []);

			if (empty($result['success'])) {
				return $result;
			}
		}

		return self::ok(translate('Added to cart'));
	}

	public static function update(int $idProduct, int $qty, int $idVariation = 0, string $cartKey = ''): array
	{
		self::init();

		$key = self::resolveCartKey($idProduct, $idVariation, [], $cartKey);

		if ($qty <= 0) {
			return self::remove($idProduct, $idVariation, $key);
		}

		$parsed = self::parseCartKey($key);
		$idProduct = (int) $parsed['id_product'];
		$idVariation = (int) $parsed['id_variation'];
		$product = Product::getById($idProduct);

		if (!$product) {
			unset($_SESSION[self::SESSION_KEY][$key]);
			self::clearLineMeta($key);

			return self::fail(translate('Product not found'));
		}

		if ($idVariation > 0) {
			$variation = ProductVariation::getById($idVariation);

			if (!$variation || (int) $variation['id_product'] !== $idProduct) {
				unset($_SESSION[self::SESSION_KEY][$key]);
				self::clearLineMeta($key);

				return self::fail(translate('Product not found'));
			}
		}

		$stock = Product::getStock($product, $idVariation);
		if ($stock <= 0) {
			unset($_SESSION[self::SESSION_KEY][$key]);
			self::clearLineMeta($key);

			return self::fail(translate('Out of stock'));
		}

		$newQty = min($stock, max(1, $qty));
		$_SESSION[self::SESSION_KEY][$key] = $newQty;
		self::notifyChanged();

		return self::ok(translate('Cart Updated'));
	}

	public static function remove(int $idProduct, int $idVariation = 0, string $cartKey = ''): array
	{
		self::init();

		$key = self::resolveCartKey($idProduct, $idVariation, [], $cartKey);
		unset($_SESSION[self::SESSION_KEY][$key]);
		self::clearLineMeta($key);
		self::notifyChanged();

		return self::ok(translate('The product has been removed from the cart'));
	}

	public static function clear(): array
	{
		$_SESSION[self::SESSION_KEY] = [];
		$_SESSION[self::META_KEY] = [];
		self::notifyChanged();

		return self::ok(translate('The cart has been emptied'));
	}

	public static function getSummary(): array
	{
		self::init();

		$items = [];
		$total = 0.0;
		$count = 0;

		foreach ($_SESSION[self::SESSION_KEY] as $cartKey => $qty) {
			$parsed = self::parseCartKey((string) $cartKey);
			$idProduct = (int) $parsed['id_product'];
			$idVariation = (int) $parsed['id_variation'];
			$options = is_array($parsed['options'] ?? null) ? $parsed['options'] : [];
			$product = Product::getById($idProduct);

			if (!$product) {
				unset($_SESSION[self::SESSION_KEY][$cartKey]);
				self::clearLineMeta((string) $cartKey);
				continue;
			}

			$variation = null;
			$unitPrice = (float) $product['price'];
			$variationLabel = '';
			$optionsLabel = ProductOption::formatLabel($options);

			if ($idVariation > 0) {
				$variation = ProductVariation::getById($idVariation);

				if (!$variation || (int) $variation['id_product'] !== $idProduct || (int) $variation['active'] !== 1) {
					unset($_SESSION[self::SESSION_KEY][$cartKey]);
					self::clearLineMeta((string) $cartKey);
					continue;
				}

				$unitPrice = ProductVariation::getEffectivePrice($variation, (float) $product['price']);
				$variationLabel = ProductVariation::formatLabel($variation);
			}

			$stock = Product::getStock($product, $idVariation);
			$qty = max(1, (int) $qty);

			if ($stock <= 0) {
				unset($_SESSION[self::SESSION_KEY][$cartKey]);
				self::clearLineMeta((string) $cartKey);
				continue;
			}

			if ($qty > $stock) {
				$qty = $stock;
				$_SESSION[self::SESSION_KEY][$cartKey] = $qty;
			}

			$productName = (string) $product['product_name'];
			$labels = [];

			if ($variationLabel !== '') {
				$labels[] = $variationLabel;
			}

			if ($optionsLabel !== '') {
				$labels[] = $optionsLabel;
			}

			if ($labels !== []) {
				$productName .= ' (' . implode(' | ', $labels) . ')';
			}

			$lineTotal = $unitPrice * $qty;
			$fullLabel = trim($variationLabel . ($variationLabel !== '' && $optionsLabel !== '' ? ' | ' : '') . $optionsLabel);

			$items[] = [
				'cart_key' => (string) $cartKey,
				'id_product' => $idProduct,
				'id_category' => (int) ($product['id_category'] ?? 0),
				'id_variation' => $idVariation,
				'options' => $options,
				'options_label' => $optionsLabel,
				'variation_label' => $fullLabel,
				'product_name' => $productName,
				'price' => $unitPrice,
				'price_formatted' => Tools::displayPrice($unitPrice),
				'qty' => $qty,
				'stock' => $stock,
				'max_qty' => $stock,
				'line_total' => $lineTotal,
				'line_total_formatted' => Tools::displayPrice($lineTotal),
				'url' => $product['url'],
				'image_url' => $product['image_url'],
			];

			$total += $lineTotal;
			$count += $qty;
		}

		$shippingAmount = 0.0;
		$cartData = [
			'items' => $items,
			'total' => $total,
			'subtotal' => $total,
			'empty' => empty($items),
		];
		$promotion = CartPromotion::calculate($cartData);
		$promotionDiscount = (float) ($promotion['discount'] ?? 0);
		$afterPromotion = max(0.0, $total - $promotionDiscount);

		if ($afterPromotion > 0 && self::requiresShipping(['items' => $items])) {
			$shippingAmount = Order::getShippingFee($afterPromotion);
		}

		$grandTotal = $afterPromotion + $shippingAmount;

		return [
			'items' => $items,
			'count' => $count,
			'subtotal' => $total,
			'subtotal_formatted' => Tools::displayPrice($total),
			'total' => $total,
			'total_formatted' => Tools::displayPrice($total),
			'promotion_discount' => $promotionDiscount,
			'promotion_discount_formatted' => Tools::displayPrice($promotionDiscount),
			'promotion_name' => $promotion['name'] ?? '',
			'promotion_label' => $promotion['label'] ?? '',
			'promotion_lines' => $promotion['lines'] ?? [],
			'has_promotion' => $promotionDiscount > 0,
			'shipping' => $shippingAmount,
			'shipping_formatted' => $shippingAmount > 0
				? Tools::displayPrice($shippingAmount)
				: translate('Free'),
			'grand_total' => $grandTotal,
			'grand_total_formatted' => Tools::displayPrice($grandTotal),
			'empty' => empty($items),
		];
	}

	public static function hasVirtualProducts(?array $cart = null): bool
	{
		$cart = $cart ?? self::getSummary();

		foreach ($cart['items'] as $item) {
			$product = Product::getById((int) ($item['id_product'] ?? 0));

			if ($product && VirtualProduct::isVirtualProduct($product)) {
				return true;
			}
		}

		return false;
	}

	public static function requiresShipping(?array $cart = null): bool
	{
		$cart = $cart ?? self::getSummary();

		foreach ($cart['items'] as $item) {
			$product = Product::getById((int) ($item['id_product'] ?? 0));

			if (!$product || !VirtualProduct::isVirtualProduct($product)) {
				return true;
			}
		}

		return false;
	}

	private static function ok(string $message): array
	{
		return array_merge([
			'success' => true,
			'message' => $message,
		], self::getSummary());
	}

	private static function fail(string $message): array
	{
		return array_merge([
			'success' => false,
			'message' => $message,
		], self::getSummary());
	}

	private static function notifyChanged(): void
	{
		if (!class_exists('Module', false)) {
			return;
		}

		Module::runHook('cart.changed', [self::getSummary()]);
	}
}
