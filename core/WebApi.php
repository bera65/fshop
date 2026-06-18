<?php

class WebApi
{
	public static function dispatch(): void
	{
		self::authenticate();

		$method = self::resolveMethod();
		$route = self::parseRoute();

		switch ($route['resource']) {
			case 'orders':
				self::handleOrders($method, $route['id']);
				break;
			case 'products':
				self::handleProducts($method, $route['id'], $route['sub']);
				break;
			case 'categories':
				self::handleCategories($method);
				break;
			case 'brands':
				self::handleBrands($method);
				break;
			default:
				self::respond(404, ['success' => false, 'message' => 'Source not found']);
		}
	}

	private static function authenticate(): void
	{
		if (Settings::get('WEBAPI_ENABLED') !== '1') {
			self::respond(403, ['success' => false, 'message' => 'Web API is down']);
		}

		$storedKey = (string) Settings::get('WEBAPI_KEY');

		if ($storedKey === '') {
			self::respond(503, ['success' => false, 'message' => 'The API key is not configured. Create one in Admin → Settings.']);
		}

		$provided = self::extractApiKey();

		if ($provided === '' || !hash_equals($storedKey, $provided)) {
			self::respond(403, ['success' => false, 'message' => 'Invalid API key']);
		}
	}

	private static function extractApiKey(): string
	{
		$key = trim((string) ($_SERVER['HTTP_X_API_KEY'] ?? ''));

		if ($key !== '') {
			return $key;
		}

		$auth = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));

		if ($auth !== '' && preg_match('/^Bearer\s+(.+)$/i', $auth, $matches)) {
			return trim($matches[1]);
		}

		return trim((string) Tools::getValue('api_key'));
	}

	private static function resolveMethod(): string
	{
		$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
		$override = trim((string) Tools::getValue('_method'));

		if ($override === '' && $method === 'POST') {
			$body = self::getInput();
			$override = trim((string) ($body['_method'] ?? ''));
		}

		if ($override !== '') {
			$method = strtoupper($override);
		}

		return $method;
	}

	private static function parseRoute(): array
	{
		$path = trim((string) Tools::getValue('route'), '/');

		if ($path === '') {
			$uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
			$base = rtrim((string) Settings::get('FOLDER'), '/');
			$prefix = $base . '/api/v1/';

			if (strpos($uri, $prefix) !== false) {
				$path = substr($uri, strpos($uri, $prefix) + strlen($prefix));
				$path = strtok($path, '?') ?: '';
			}
		}

		$parts = array_values(array_filter(explode('/', $path), static function ($part) {
			return $part !== '';
		}));

		$resource = strtolower((string) ($parts[0] ?? ''));
		$id = isset($parts[1]) && ctype_digit((string) $parts[1]) ? (int) $parts[1] : 0;
		$sub = strtolower((string) ($parts[2] ?? ''));

		return [
			'resource' => $resource,
			'id' => $id,
			'sub' => $sub,
		];
	}

	private static function handleOrders(string $method, int $id): void
	{
		if ($method === 'GET' && $id <= 0) {
			self::listOrders();
		}

		if ($method === 'GET' && $id > 0) {
			self::getOrder($id);
		}

		if (in_array($method, ['PUT', 'PATCH'], true) && $id > 0) {
			self::updateOrder($id);
		}

		self::respond(405, ['success' => false, 'message' => 'Unsupported order processing']);
	}

	private static function handleProducts(string $method, int $id, string $sub = ''): void
	{
		if ($method === 'GET' && $id <= 0) {
			self::listProducts();
		}

		if ($method === 'GET' && $id > 0 && $sub === '') {
			self::getProduct($id);
		}

		if ($method === 'POST' && $id <= 0) {
			self::createProduct();
		}

		if ($method === 'POST' && $id > 0 && $sub === 'image') {
			self::uploadProductImage($id);
		}

		if (in_array($method, ['PUT', 'PATCH'], true) && $id > 0 && $sub === 'quick') {
			self::patchProductQuick($id);
		}

		if (in_array($method, ['PUT', 'PATCH'], true) && $id > 0 && $sub === '') {
			self::updateProduct($id);
		}

		if ($method === 'DELETE' && $id > 0 && $sub === '') {
			self::deleteProduct($id);
		}

		self::respond(405, ['success' => false, 'message' => 'Unsupported product operation']);
	}

	private static function listOrders(): void
	{
		$status = max(0, (int) Tools::getValue('status'));
		$page = max(0, (int) Tools::getValue('page', 0));
		$size = min(100, max(1, (int) Tools::getValue('size', (int) Tools::getValue('limit', 30))));
		$offset = $page * $size;
		$dates = self::parseOrderDateFilters();

		$rows = Order::attachApiDetails(Order::getAdminList($status, $size, $offset, $dates['from'], $dates['to']));
		$total = Order::countAdmin($status, $dates['from'], $dates['to']);
		$totalPages = $size > 0 ? (int) ceil($total / $size) : 0;

		self::respond(200, [
			'totalElements' => $total,
			'totalPages' => $totalPages,
			'page' => $page,
			'size' => $size,
			'content' => array_map([self::class, 'formatTrendyolOrder'], $rows),
		]);
	}

	private static function parseOrderDateFilters(): array
	{
		$from = trim((string) Tools::getValue('date_from'));
		$to = trim((string) Tools::getValue('date_to'));
		$startMs = Tools::getValue('startDate');
		$endMs = Tools::getValue('endDate');

		if ($from === '' && $startMs !== '' && is_numeric($startMs)) {
			$from = date('Y-m-d H:i:s', (int) (((int) $startMs) / 1000));
		}

		if ($to === '' && $endMs !== '' && is_numeric($endMs)) {
			$to = date('Y-m-d H:i:s', (int) (((int) $endMs) / 1000));
		}

		if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
			$from .= ' 00:00:00';
		}

		if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
			$to .= ' 23:59:59';
		}

		return ['from' => $from, 'to' => $to];
	}

	private static function handleCategories(string $method): void
	{
		if ($method !== 'GET') {
			self::respond(405, ['success' => false, 'message' => 'Unsupported category operation']);
		}

		self::listCategories();
	}

	private static function handleBrands(string $method): void
	{
		if ($method !== 'GET') {
			self::respond(405, ['success' => false, 'message' => 'Unsupported trademark operation']);
		}

		self::listBrands();
	}

	private static function listCategories(): void
	{
		$page = max(0, (int) Tools::getValue('page', 0));
		$size = min(500, max(1, (int) Tools::getValue('size', (int) Tools::getValue('limit', 100))));
		$offset = $page * $size;
		$activeOnly = Tools::getIsset('active') ? (int) Tools::getValue('active') : 1;

		$rows = Category::getAdminList($activeOnly, $size, $offset);
		$total = Category::countAdmin($activeOnly);

		self::respond(200, [
			'totalElements' => $total,
			'totalPages' => $size > 0 ? (int) ceil($total / $size) : 0,
			'page' => $page,
			'size' => $size,
			'content' => array_map(static function (array $row): array {
				return [
					'id' => (int) $row['id_category'],
					'name' => (string) $row['category_name'],
					'slug' => (string) $row['category_link'],
					'parentId' => (int) ($row['id_parent'] ?? 0),
					'parentName' => (string) ($row['parent_name'] ?? ''),
					'active' => (int) ($row['active'] ?? 0) === 1,
				];
			}, $rows),
		]);
	}

	private static function listBrands(): void
	{
		$page = max(0, (int) Tools::getValue('page', 0));
		$size = min(500, max(1, (int) Tools::getValue('size', (int) Tools::getValue('limit', 100))));
		$offset = $page * $size;
		$activeOnly = Tools::getIsset('active') ? (int) Tools::getValue('active') : 1;

		$rows = Brand::getAdminList($activeOnly, $size, $offset);
		$total = Brand::countAdmin($activeOnly);

		self::respond(200, [
			'totalElements' => $total,
			'totalPages' => $size > 0 ? (int) ceil($total / $size) : 0,
			'page' => $page,
			'size' => $size,
			'content' => array_map(static function (array $row): array {
				return [
					'id' => (int) $row['id_brand'],
					'name' => (string) $row['brand_name'],
					'slug' => (string) $row['brand_link'],
					'active' => (int) ($row['active'] ?? 0) === 1,
				];
			}, $rows),
		]);
	}

	private static function getOrder(int $id): void
	{
		$order = Order::getByIdAdmin($id);

		if (!$order) {
			self::respond(404, ['success' => false, 'message' => 'Order not found']);
		}

		$prepared = Order::attachApiDetails([$order]);

		self::respond(200, self::formatTrendyolOrder($prepared[0]));
	}

	private static function updateOrder(int $id): void
	{
		$input = self::getInput();
		$payload = [];

		if (array_key_exists('status', $input) || Tools::getIsset('status')) {
			$status = (int) ($input['status'] ?? Tools::getValue('status'));

			if ($status > 0) {
				$payload['status'] = $status;
			}
		}

		if (array_key_exists('cargoCompany', $input) || array_key_exists('cargo_company', $input)) {
			$payload['cargo_company'] = (string) ($input['cargoCompany'] ?? $input['cargo_company'] ?? '');
		}

		if (array_key_exists('trackingNumber', $input) || array_key_exists('tracking_number', $input)) {
			$payload['tracking_number'] = (string) ($input['trackingNumber'] ?? $input['tracking_number'] ?? '');
		}

		if ($payload === []) {
			self::respond(422, [
				'success' => false,
				'message' => 'At least one of the following fields is required: status, cargoCompany, or trackingNumber.',
			]);
		}

		$result = Order::updateFromApi($id, $payload);

		if (empty($result['success'])) {
			self::respond(400, $result);
		}

		$order = Order::getByIdAdmin($id);
		$prepared = $order ? Order::attachApiDetails([$order]) : [];

		self::respond(200, [
			'success' => true,
			'message' => $result['message'],
			'content' => $prepared ? self::formatTrendyolOrder($prepared[0]) : null,
		]);
	}

	private static function listProducts(): void
	{
		$query = trim((string) Tools::getValue('q'));
		$idCategory = max(0, (int) Tools::getValue('category'));
		$idBrand = max(0, (int) Tools::getValue('brand'));
		$activeFilter = Tools::getIsset('active') ? (int) Tools::getValue('active') : -1;
		$page = max(1, (int) Tools::getValue('page', 1));
		$limit = min(100, max(1, (int) Tools::getValue('limit', 30)));
		$offset = ($page - 1) * $limit;

		$rows = Product::getAdminList($query, $idCategory, $idBrand, $activeFilter, $limit, $offset);
		$total = Product::countAdmin($query, $idCategory, $idBrand, $activeFilter);

		self::respond(200, [
			'success' => true,
			'data' => array_map([self::class, 'formatProduct'], $rows),
			'meta' => [
				'total' => $total,
				'page' => $page,
				'limit' => $limit,
				'pages' => (int) ceil($total / $limit),
			],
		]);
	}

	private static function getProduct(int $id): void
	{
		$product = Product::getByIdAdmin($id);

		if (!$product) {
			self::respond(404, ['success' => false, 'message' => 'Product not found']);
		}

		self::respond(200, [
			'success' => true,
			'data' => self::formatProduct($product, true),
		]);
	}

	private static function createProduct(): void
	{
		$input = self::mapProductInput(self::getInput());
		$result = Product::save($input);

		if (empty($result['success'])) {
			self::respond(422, $result);
		}

		$product = Product::getByIdAdmin((int) $result['id']);

		self::respond(201, [
			'success' => true,
			'message' => $result['message'],
			'data' => $product ? self::formatProduct($product, true) : null,
		]);
	}

	private static function updateProduct(int $id): void
	{
		if (!Product::getByIdAdmin($id)) {
			self::respond(404, ['success' => false, 'message' => 'Product not found']);
		}

		$input = self::mapProductInput(self::getInput());
		$result = Product::save($input, $id);

		if (empty($result['success'])) {
			self::respond(422, $result);
		}

		$product = Product::getByIdAdmin($id);

		self::respond(200, [
			'success' => true,
			'message' => $result['message'],
			'data' => $product ? self::formatProduct($product, true) : null,
		]);
	}

	private static function deleteProduct(int $id): void
	{
		$result = Product::deleteById($id);

		if (empty($result['success'])) {
			self::respond(404, $result);
		}

		self::respond(200, $result);
	}

	private static function uploadProductImage(int $id): void
	{
		if (!Product::getByIdAdmin($id)) {
			self::respond(404, ['success' => false, 'message' => 'Product not found']);
		}

		if (!empty($_FILES['image']['tmp_name'])) {
			$result = Product::uploadImage($id, $_FILES['image']);
		} else {
			$input = self::getInput();
			$imageUrl = trim((string) ($input['image_url'] ?? $input['url'] ?? ''));

			if ($imageUrl !== '') {
				$result = Product::importImageFromUrl($id, $imageUrl);
			} else {
				$base64 = trim((string) ($input['image_base64'] ?? ''));

				if ($base64 === '') {
					self::respond(422, [
						'success' => false,
						'message' => 'The image file, image_url, or image base64 field is required.',
					]);
				}

				if (strpos($base64, ',') !== false) {
					$base64 = substr($base64, strrpos($base64, ',') + 1);
				}

				$binary = base64_decode($base64, true);

				if ($binary === false || $binary === '') {
					self::respond(422, ['success' => false, 'message' => 'Invalid base64 visual data.']);
				}

				$result = Product::importImageBinary($id, $binary);
			}
		}

		if (empty($result['success'])) {
			self::respond(422, $result);
		}

		$idImage = (int) ($result['id'] ?? 0);

		self::respond(201, [
			'success' => true,
			'message' => $result['message'],
			'data' => [
				'id' => $idImage,
				'product_id' => $id,
				'url' => Product::getImageUrl($idImage),
			],
		]);
	}

	private static function patchProductQuick(int $id): void
	{
		$input = self::getInput();
		$payload = [];

		if (array_key_exists('price', $input)) {
			$payload['price'] = $input['price'];
		}

		if (array_key_exists('doviz_price', $input)) {
			$payload['doviz_price'] = $input['doviz_price'];
		}

		if (array_key_exists('stock', $input)) {
			$payload['stock'] = $input['stock'];
		}

		if (array_key_exists('active', $input)) {
			$payload['active'] = filter_var($input['active'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
		}

		$result = Product::patchQuick($id, $payload);

		if (empty($result['success'])) {
			self::respond(422, $result);
		}

		$product = Product::getByIdAdmin($id);

		self::respond(200, [
			'success' => true,
			'message' => $result['message'],
			'data' => $product ? self::formatProduct($product, true) : null,
		]);
	}

	private static function mapProductInput(array $input): array
	{
		$map = [
			'name' => 'product_name',
			'slug' => 'product_link',
			'category_id' => 'id_category',
			'brand_id' => 'id_brand',
			'category' => 'category_name',
			'brand' => 'brand_name',
			'description_html' => 'description',
		];

		foreach ($map as $from => $to) {
			if (array_key_exists($from, $input) && !array_key_exists($to, $input)) {
				$input[$to] = $input[$from];
			}
		}

		if (empty($input['id_category']) && !empty($input['category_name'])) {
			$resolved = self::resolveCategoryId((string) $input['category_name']);

			if ($resolved > 0) {
				$input['id_category'] = $resolved;
			}
		}

		if (empty($input['id_brand']) && !empty($input['brand_name'])) {
			$resolved = self::resolveBrandId((string) $input['brand_name']);

			if ($resolved > 0) {
				$input['id_brand'] = $resolved;
			}
		}

		if (!array_key_exists('doviz_price', $input) && array_key_exists('price', $input)) {
			$input['doviz_price'] = $input['price'];
		}

		if (!array_key_exists('doviz_old_price', $input) && array_key_exists('old_price', $input)) {
			$input['doviz_old_price'] = $input['old_price'];
		}

		if (array_key_exists('active', $input)) {
			$input['active'] = filter_var($input['active'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
		}

		return $input;
	}

	private static function resolveCategoryId(string $name): int
	{
		$name = trim($name);

		if ($name === '') {
			return 0;
		}

		$id = (int) DB::getValue(
			'SELECT id_category FROM categories WHERE LOWER(category_name) = LOWER(?) LIMIT 1',
			[$name]
		);

		if ($id > 0) {
			return $id;
		}

		$result = Category::save([
			'category_name' => $name,
			'id_parent' => self::defaultCategoryParentId(),
			'active' => 1,
		]);

		return !empty($result['success']) ? (int) $result['id'] : 0;
	}

	private static function resolveBrandId(string $name): int
	{
		$name = trim($name);

		if ($name === '') {
			return 0;
		}

		$id = (int) DB::getValue(
			'SELECT id_brand FROM brands WHERE LOWER(brand_name) = LOWER(?) LIMIT 1',
			[$name]
		);

		if ($id > 0) {
			return $id;
		}

		$result = Brand::save([
			'brand_name' => $name,
			'active' => 1,
		]);

		return !empty($result['success']) ? (int) $result['id'] : 0;
	}

	private static function defaultCategoryParentId(): int
	{
		$id = (int) DB::getValue(
			'SELECT id_category FROM categories WHERE id_parent = 0 AND active = 1 ORDER BY id_category ASC LIMIT 1'
		);

		return $id > 0 ? $id : 0;
	}

	private static function formatTrendyolOrder(array $order): array
	{
		$statusCode = (int) ($order['status'] ?? 0);
		$statusName = self::mapOrderStatusName($statusCode);
		$nameParts = self::splitCustomerName((string) ($order['customer_name'] ?? ''));
		$subtotal = (float) ($order['subtotal'] ?? 0);
		$discount = (float) ($order['coupon_discount'] ?? 0);
		$shipping = (float) ($order['shipping'] ?? 0);
		$total = (float) ($order['total'] ?? 0);
		$taxNumber = (string) ($order['tax_number'] ?? '');
		$companyName = (string) ($order['company_name'] ?? '');
		$commercial = $companyName !== '' || $taxNumber !== '';
		$currency = strtoupper((string) (Settings::get('GSF_CURRENCY') ?: 'TRY'));
		$orderDate = strtotime((string) ($order['date_add'] ?? '')) ?: 0;

		$shipmentAddress = self::buildAddressBlock($order, false);
		$invoiceAddress = self::buildAddressBlock($order, true);

		if ($commercial) {
			$invoiceAddress['taxOffice'] = (string) ($order['tax_office'] ?? '');
			$invoiceAddress['taxNumber'] = $taxNumber;
		}

		$lines = array_map(static function (array $item) use ($statusName, $currency): array {
			$unitPrice = (float) ($item['price'] ?? 0);
			$qty = (int) ($item['qty'] ?? 0);

			return [
				'quantity' => $qty,
				'stockCode' => (string) ($item['stock_code'] ?? ''),
				'productName' => (string) ($item['product_name'] ?? ''),
				'contentId' => (int) ($item['id_product'] ?? 0),
				'lineGrossAmount' => $unitPrice,
				'lineTotalDiscount' => 0.0,
				'lineSellerDiscount' => 0.0,
				'lineUnitPrice' => $unitPrice,
				'lineId' => (int) ($item['id_order_detail'] ?? 0),
				'vatRate' => (float) ($item['vat'] ?? 0),
				'barcode' => (string) ($item['barcode'] ?? ''),
				'orderLineItemStatusName' => $statusName,
				'currencyCode' => $currency,
				'lineAmount' => round($unitPrice * $qty, 2),
			];
		}, $order['items'] ?? []);

		return [
			'id' => (int) ($order['id_order'] ?? 0),
			'orderNumber' => (string) ($order['reference'] ?? ''),
			'shipmentAddress' => $shipmentAddress,
			'invoiceAddress' => $invoiceAddress,
			'customerFirstName' => $nameParts['firstName'],
			'customerLastName' => $nameParts['lastName'],
			'customerEmail' => (string) ($order['customer_email'] ?? ''),
			'customerId' => (int) ($order['id_user'] ?? 0),
			'customerPhone' => (string) ($order['customer_phone'] ?? ''),
			'packageGrossAmount' => round($subtotal, 2),
			'packageSellerDiscount' => 0.0,
			'packageTotalDiscount' => round($discount, 2),
			'packageShipping' => round($shipping, 2),
			'packageTotalPrice' => round($total, 2),
			'couponCode' => (string) ($order['coupon_code'] ?? ''),
			'paymentMethod' => (string) ($order['payment_method'] ?? ''),
			'paymentLabel' => (string) ($order['payment_label'] ?? Order::getPaymentLabel((string) ($order['payment_method'] ?? ''))),
			'lines' => $lines,
			'orderDate' => $orderDate * 1000,
			'identityNumber' => $taxNumber,
			'taxNumber' => $taxNumber,
			'currencyCode' => $currency,
			'shipmentPackageStatus' => $statusName,
			'status' => $statusName,
			'statusCode' => $statusCode,
			'statusLabel' => (string) ($order['status_label'] ?? Order::getStatusLabel($statusCode)),
			'cargoCompany' => (string) ($order['cargo_company'] ?? ''),
			'trackingNumber' => (string) ($order['tracking_number'] ?? ''),
			'commercial' => $commercial,
			'note' => (string) ($order['note'] ?? ''),
		];
	}

	private static function splitCustomerName(string $fullName): array
	{
		$fullName = trim($fullName);
		$parts = preg_split('/\s+/u', $fullName, 2) ?: [];

		return [
			'firstName' => (string) ($parts[0] ?? ''),
			'lastName' => (string) ($parts[1] ?? ''),
		];
	}

	private static function buildAddressBlock(array $order, bool $invoice): array
	{
		$nameParts = self::splitCustomerName((string) ($order['customer_name'] ?? ''));
		$city = (string) ($order['address_city'] ?? '');
		$district = (string) ($order['address_district'] ?? '');
		$addressText = (string) ($order['address_text'] ?? '');
		$fullAddress = trim($city . ' / ' . $district . ($addressText !== '' ? ' — ' . $addressText : ''));

		$block = [
			'id' => (int) ($order['id_order'] ?? 0),
			'firstName' => $nameParts['firstName'],
			'lastName' => $nameParts['lastName'],
			'company' => (string) ($order['company_name'] ?? ''),
			'address1' => $addressText,
			'address2' => '',
			'city' => $city,
			'district' => $district,
			'phone' => (string) ($order['customer_phone'] ?? ''),
			'fullAddress' => $fullAddress,
			'fullName' => (string) ($order['customer_name'] ?? ''),
			'countryCode' => 'TR',
		];

		if ($invoice) {
			$block['taxOffice'] = (string) ($order['tax_office'] ?? '');
			$block['taxNumber'] = (string) ($order['tax_number'] ?? '');
		}

		return $block;
	}

	private static function mapOrderStatusName(int $status): string
	{
		$map = [
			Order::STATUS_PENDING => 'AwaitingPayment',
			Order::STATUS_PROCESSING => 'Picking',
			Order::STATUS_SHIPPED => 'Shipped',
			Order::STATUS_DELIVERED => 'Delivered',
			Order::STATUS_CANCELLED => 'Cancelled',
		];

		return $map[$status] ?? 'Unknown';
	}

	private static function formatProduct(array $product, bool $detailed = false): array
	{
		$enriched = Product::enrich($product);
		$data = [
			'id' => (int) $enriched['id_product'],
			'name' => (string) $enriched['product_name'],
			'slug' => (string) $enriched['product_link'],
			'url' => (string) $enriched['url'],
			'price' => (float) $enriched['price'],
			'old_price' => (float) ($enriched['old_price'] ?? 0),
			'stock' => (int) ($enriched['stock'] ?? 0),
			'in_stock' => !empty($enriched['in_stock']),
			'active' => (int) ($enriched['active'] ?? 0) === 1,
			'barcode' => (string) ($enriched['barcode'] ?? ''),
			'stock_code' => (string) ($enriched['stock_code'] ?? ''),
			'id_category' => (int) ($enriched['id_category'] ?? 0),
			'category_name' => (string) ($enriched['category_name'] ?? ''),
			'id_brand' => (int) ($enriched['id_brand'] ?? 0),
			'brand_name' => (string) ($enriched['brand_name'] ?? ''),
			'image_url' => (string) ($enriched['image_url'] ?? ''),
		];

		if (!$detailed) {
			return $data;
		}

		$data['short_description'] = (string) ($enriched['short_description'] ?? '');
		$data['description'] = (string) ($enriched['description'] ?? '');
		$data['description_html'] = (string) ($enriched['description'] ?? '');
		$data['meta_title'] = (string) ($enriched['meta_title'] ?? '');
		$data['meta_description'] = (string) ($enriched['meta_description'] ?? '');
		$data['vat'] = (float) ($enriched['vat'] ?? 0);
		$data['doviz'] = (string) ($enriched['doviz'] ?? 'try');
		$data['doviz_price'] = (float) ($enriched['doviz_price'] ?? 0);
		$data['doviz_old_price'] = (float) ($enriched['doviz_old_price'] ?? 0);
		$data['cargo_day'] = (int) ($enriched['cargo_day'] ?? 0);
		$data['label'] = (string) ($enriched['label'] ?? '');
		$data['product_video'] = (string) ($enriched['product_video'] ?? '');
		$data['desi'] = (int) ($enriched['desi'] ?? 0);
		$data['images'] = array_map(static function (array $image): array {
			return [
				'id' => (int) $image['id_image'],
				'url' => Product::getImageUrl((int) $image['id_image']),
				'cover' => (int) ($image['cover'] ?? 0) === 1,
			];
		}, $product['images'] ?? Product::getImages((int) $enriched['id_product']));

		return $data;
	}

	private static function getInput(): array
	{
		$json = [];

		if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
			$raw = file_get_contents('php://input');

			if (is_string($raw) && $raw !== '') {
				$decoded = json_decode($raw, true);

				if (is_array($decoded)) {
					$json = $decoded;
				}
			}
		}

		if ($json !== []) {
			return $json;
		}

		return $_POST ?? [];
	}

	private static function respond(int $status, array $payload): void
	{
		http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($payload, JSON_UNESCAPED_UNICODE);
		exit;
	}
}
