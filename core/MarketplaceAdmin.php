<?php

class MarketplaceAdmin
{
	public static function renderProductsPage(): void
	{
		global $smarty, $adminToken;

		$flash = Marketplace::handleAdminPosts($adminToken);
		$currentPage = max(1, (int) Tools::getValue('page'));
		$query = trim((string) Tools::getValue('q'));
		$filter = trim((string) Tools::getValue('filter', 'all'));

		if (!in_array($filter, ['all', 'linked', 'unlinked'], true)) {
			$filter = 'all';
		}

		$perPage = 20;
		$total = Trendyol\ProductSyncService::countMarketplaceCatalog($query, $filter);
		$pagination = Pagination::build(
			$total,
			$currentPage,
			$perPage,
			Admin::url('marketplace-products'),
			array_filter([
				'q' => $query !== '' ? $query : null,
				'filter' => $filter !== 'all' ? $filter : null,
			], static fn($v) => $v !== null && $v !== '')
		);

		$rows = Trendyol\ProductSyncService::getMarketplaceCatalog($query, $filter, $perPage, $pagination['offset']);
		$catalogProducts = [];

		foreach ($rows as $row) {
			$idProduct = (int) ($row['id_product'] ?? 0);
			$row = Marketplace::enrichCatalogRow($row);
			$catalogProducts[] = [
				'row' => $row,
				'panel_html' => Marketplace::renderProductPanelHtml($idProduct, 'trendyol'),
				'panel_html_hb' => Marketplace::renderProductPanelHtml($idProduct, 'hepsiburada'),
				'panel_html_n11' => Marketplace::renderProductPanelHtml($idProduct, 'n11'),
			];
		}

		$urls = Marketplace::urls();

		$smarty->assign(array_merge($urls, [
			'flash' => $flash,
			'catalogProducts' => $catalogProducts,
			'searchQuery' => $query,
			'linkFilter' => $filter,
			'pagination' => $pagination,
			'marketplaceAdminAssets' => Marketplace::adminAssets(),
			'marketplacePage' => 'products',
			'marketplacePlatforms' => Marketplace::platformList(),
			'tyConfigured' => Marketplace::isTrendyolConfigured(),
			'hbConfigured' => Marketplace::isPlatformConfigured('hepsiburada'),
			'n11Configured' => Marketplace::isPlatformConfigured('n11'),
			'categoryOptions' => Category::getProductSelectOptions(),
			'brandOptions' => Brand::getOptions(),
			'fiyattrendToken' => (string) Settings::get('TRENDYOL_FIYATTREND_TOKEN'),
		]));

		AdminPage::add('marketplace-products', 'Pazaryeri — Ürünler');
	}

	public static function renderOrdersPage(): void
	{
		global $smarty;

		$flash = Marketplace::handleAdminPosts($GLOBALS['adminToken'] ?? '');
		$platform = trim((string) Tools::getValue('marketplace_platform', Tools::getValue('platform', 'all')));
		$startDate = trim((string) Tools::getValue('start_date', ''));
		$endDate = trim((string) Tools::getValue('end_date', ''));
		$orderNumber = trim((string) Tools::getValue('order_number', ''));
		$customerName = trim((string) Tools::getValue('customer_name', ''));
		$productQuery = trim((string) Tools::getValue('product_query', ''));
		$orderStatus = trim((string) Tools::getValue('order_status', 'all'));

		if (!in_array($platform, ['all', 'trendyol', 'hepsiburada', 'n11'], true)) {
			$platform = 'all';
		}
		if (!in_array($orderStatus, ['all', 'pending', 'navy', 'success', 'done', 'danger', 'muted'], true)) {
			$orderStatus = 'all';
		}

		$orders = self::getMarketplaceOrdersForFilters([
			'marketplace_platform' => $platform,
			'start_date' => $startDate,
			'end_date' => $endDate,
			'order_number' => $orderNumber,
			'customer_name' => $customerName,
			'product_query' => $productQuery,
			'order_status' => $orderStatus,
		]);

		$syncPlatform = $platform;

		$currentPage = max(1, (int) Tools::getValue('page', 1));
		$perPage = 25;
		$totalOrders = count($orders);
		$pagination = Pagination::build(
			$totalOrders,
			$currentPage,
			$perPage,
			Admin::url('marketplace-orders'),
			array_filter([
				'marketplace_platform' => $platform !== 'all' ? $platform : null,
				'start_date' => $startDate !== '' ? $startDate : null,
				'end_date' => $endDate !== '' ? $endDate : null,
				'order_number' => $orderNumber !== '' ? $orderNumber : null,
				'customer_name' => $customerName !== '' ? $customerName : null,
				'product_query' => $productQuery !== '' ? $productQuery : null,
				'order_status' => $orderStatus !== 'all' ? $orderStatus : null,
			], static function ($v) {
				return $v !== null && $v !== '';
			})
		);
		$orders = array_slice($orders, (int) $pagination['offset'], $perPage);

		$smarty->assign(array_merge(Marketplace::urls(), [
			'flash' => $flash,
			'tyConfigured' => Marketplace::isTrendyolConfigured(),
			'hbConfigured' => Marketplace::isPlatformConfigured('hepsiburada'),
			'n11Configured' => Marketplace::isPlatformConfigured('n11'),
			'marketplaceOrders' => $orders,
			'pagination' => $pagination,
			'marketplaceOrderPlatform' => $platform,
			'marketplaceOrderSyncPlatform' => $syncPlatform,
			'marketplaceOrderStartDate' => $startDate,
			'marketplaceOrderEndDate' => $endDate,
			'marketplaceOrderFilterOrderNumber' => $orderNumber,
			'marketplaceOrderFilterCustomerName' => $customerName,
			'marketplaceOrderFilterProductQuery' => $productQuery,
			'marketplaceOrderFilterStatus' => $orderStatus,
			'marketplaceAdminAssets' => ['css' => [], 'js' => []],
			'marketplacePage' => 'orders',
			'marketplacePlatforms' => Marketplace::platformList(),
		]));

		AdminPage::add('marketplace-orders', 'Pazaryeri — Siparişler');
	}

	public static function renderQuestionsPage(): void
	{
		global $smarty;

		$flash = Marketplace::handleAdminPosts($GLOBALS['adminToken'] ?? '');
		$platform = trim((string) Tools::getValue('marketplace_platform', Tools::getValue('platform', 'all')));
		$statusFilter = trim((string) Tools::getValue('question_status', 'all'));

		if (!in_array($platform, ['all', 'trendyol', 'hepsiburada', 'n11'], true)) {
			$platform = 'all';
		}

		if (!in_array($statusFilter, ['all', 'waiting', 'answered'], true)) {
			$statusFilter = 'all';
		}

		$questions = self::getMarketplaceQuestionsForFilters([
			'marketplace_platform' => $platform,
			'question_status' => $statusFilter,
		]);

		$currentPage = max(1, (int) Tools::getValue('page', 1));
		$perPage = 25;
		$pagination = Pagination::build(
			count($questions),
			$currentPage,
			$perPage,
			Admin::url('marketplace-questions'),
			array_filter([
				'marketplace_platform' => $platform !== 'all' ? $platform : null,
				'question_status' => $statusFilter !== 'all' ? $statusFilter : null,
			], static function ($v) {
				return $v !== null && $v !== '';
			})
		);
		$questions = array_slice($questions, (int) $pagination['offset'], $perPage);

		$smarty->assign(array_merge(Marketplace::urls(), [
			'flash' => $flash,
			'tyConfigured' => Marketplace::isTrendyolConfigured(),
			'hbConfigured' => Marketplace::isPlatformConfigured('hepsiburada'),
			'n11Configured' => Marketplace::isPlatformConfigured('n11'),
			'marketplaceQuestions' => $questions,
			'marketplaceQuestionPlatform' => $platform,
			'marketplaceQuestionStatus' => $statusFilter,
			'pagination' => $pagination,
			'marketplaceAdminAssets' => ['css' => [], 'js' => []],
			'marketplacePage' => 'questions',
		]));

		AdminPage::add('marketplace-questions', 'Pazaryeri — Soru-Cevap');
	}

	public static function renderSettingsPage(string $defaultPlatform = 'trendyol'): void
	{
		global $smarty;

		$flash = Marketplace::handleAdminPosts($GLOBALS['adminToken'] ?? '');
		$tab = (string) Tools::getValue('tab', 'settings');
		$platform = trim((string) Tools::getValue('platform', $defaultPlatform));

		if (!isset(Marketplace::PLATFORMS[$platform])) {
			$platform = 'trendyol';
		}

		if (!in_array($tab, ['settings', 'fiyattrend'], true)) {
			$tab = 'settings';
		}

		$assign = array_merge(Marketplace::urls(), [
			'flash' => $flash,
			'tab' => $tab,
			'marketplacePlatforms' => Marketplace::platformList(),
			'marketplacePlatform' => $platform,
			'marketplacePage' => 'settings',
			'settingsUrl' => Marketplace::settingsUrl($platform),
			'marketplaceAdminAssets' => ['css' => [], 'js' => []],
		]);

		if ($platform === 'trendyol' && Marketplace::isPlatformActive('trendyol')) {
			$assign = array_merge($assign, Trendyol\TrendyolAdminPages::settingsVars(), [
				'tyConfigured' => Marketplace::isTrendyolConfigured(),
				'tab' => $tab,
				'settingsUrl' => Marketplace::settingsUrl('trendyol'),
			]);
		} elseif ($platform === 'hepsiburada' && Marketplace::isPlatformActive('hepsiburada')) {
			$assign = array_merge($assign, Hepsiburada\HepsiburadaAdminPages::settingsVars(), [
				'settingsUrl' => Marketplace::settingsUrl('hepsiburada'),
			]);
		} elseif ($platform === 'n11' && Marketplace::isPlatformActive('n11')) {
			$assign = array_merge($assign, N11\N11AdminPages::settingsVars(), [
				'settingsUrl' => Marketplace::settingsUrl('n11'),
			]);
		}

		$smarty->assign($assign);

		AdminPage::add('marketplace-settings', 'Pazaryeri — Ayarlar');
	}

	public static function renderHelpPage(): void
	{
		global $smarty;

		$smarty->assign(array_merge(Marketplace::urls(), [
			'flash' => '',
			'marketplaceAdminAssets' => ['css' => [], 'js' => []],
			'marketplacePage' => 'help',
		]));

		AdminPage::add('marketplace-help', 'Pazaryeri — Help');
	}

	public static function renderLogsPage(): void
	{
		global $smarty;

		MarketplaceLog::ensureSchema();

		$platform = trim((string) Tools::getValue('platform', 'all'));
		$eventType = trim((string) Tools::getValue('event_type', 'all'));
		$query = trim((string) Tools::getValue('q', ''));
		$startDate = trim((string) Tools::getValue('start_date', ''));
		$endDate = trim((string) Tools::getValue('end_date', ''));

		if (!in_array($platform, ['all', 'trendyol', 'hepsiburada', 'n11'], true)) {
			$platform = 'all';
		}

		$allowedTypes = [
			'all',
			MarketplaceLog::TYPE_NEW_ORDER,
			MarketplaceLog::TYPE_STOCK_CHANGE,
			MarketplaceLog::TYPE_BELOW_MIN_PRICE,
			MarketplaceLog::TYPE_PRICE_UPDATE,
		];

		if (!in_array($eventType, $allowedTypes, true)) {
			$eventType = 'all';
		}

		$currentPage = max(1, (int) Tools::getValue('page', 1));
		$perPage = 50;
		$result = MarketplaceLog::search([
			'platform' => $platform,
			'event_type' => $eventType,
			'q' => $query,
			'start_date' => $startDate,
			'end_date' => $endDate,
		], $perPage, ($currentPage - 1) * $perPage);

		$pagination = Pagination::build(
			(int) $result['total'],
			$currentPage,
			$perPage,
			Admin::url('marketplace-logs'),
			array_filter([
				'platform' => $platform !== 'all' ? $platform : null,
				'event_type' => $eventType !== 'all' ? $eventType : null,
				'q' => $query !== '' ? $query : null,
				'start_date' => $startDate !== '' ? $startDate : null,
				'end_date' => $endDate !== '' ? $endDate : null,
			], static function ($v) {
				return $v !== null && $v !== '';
			})
		);

		$urls = Marketplace::urls();

		$smarty->assign(array_merge($urls, [
			'flash' => '',
			'marketplacePage' => 'logs',
			'marketplaceAdminAssets' => Marketplace::adminAssets(),
			'marketplaceLogs' => $result['rows'],
			'marketplaceLogPlatform' => $platform,
			'marketplaceLogEventType' => $eventType,
			'marketplaceLogQuery' => $query,
			'marketplaceLogStartDate' => $startDate,
			'marketplaceLogEndDate' => $endDate,
			'pagination' => $pagination,
			'marketplaceLogTypes' => [
				MarketplaceLog::TYPE_NEW_ORDER => MarketplaceLog::typeLabel(MarketplaceLog::TYPE_NEW_ORDER),
				MarketplaceLog::TYPE_STOCK_CHANGE => MarketplaceLog::typeLabel(MarketplaceLog::TYPE_STOCK_CHANGE),
				MarketplaceLog::TYPE_BELOW_MIN_PRICE => MarketplaceLog::typeLabel(MarketplaceLog::TYPE_BELOW_MIN_PRICE),
				MarketplaceLog::TYPE_PRICE_UPDATE => MarketplaceLog::typeLabel(MarketplaceLog::TYPE_PRICE_UPDATE),
			],
		]));

		AdminPage::add('marketplace-logs', 'Pazaryeri — Loglar');
	}

	/**
	 * @param array<int, array<string, mixed>> $orders
	 * @return array<int, array<string, mixed>>
	 */
	private static function normalizeMarketplaceOrders(array $orders, string $platformKey, string $platformLabel): array
	{
		$out = [];

		foreach ($orders as $ord) {
			$lines = is_array($ord['lines'] ?? null) ? $ord['lines'] : [];
			$items = [];
			$totalQty = 0;

			foreach ($lines as $line) {
				if (!is_array($line)) {
					continue;
				}

				$qty = max(1, (int) ($line['quantity'] ?? 1));
				$totalQty += $qty;
				$sku = (string) (
					$line['merchantSku']
					?? $line['stockCode']
					?? $line['barcode']
					?? $line['hepsiburadaSku']
					?? $line['sku']
					?? ''
				);
				$image = (string) (
					$line['productImage']
					?? $line['imageUrl']
					?? $line['productImageUrl']
					?? $line['image']
					?? ''
				);
				if ($image === '') {
					$image = self::resolveLocalProductImage($platformKey, $sku, $line);
				}
				$items[] = [
					'name' => (string) (
						$line['productName']
						?? $line['merchantSku']
						?? $line['hepsiburadaSku']
						?? $line['stockCode']
						?? '—'
					),
					'sku' => $sku,
					'image' => $image,
					'quantity' => $qty,
				];
			}

			$orderDate = trim((string) ($ord['order_date'] ?? ''));
			$syncDate = trim((string) ($ord['last_sync_at'] ?? ''));

			if ($orderDate === '') {
				$orderDate = self::extractOrderDateFromRaw($ord);
			}

			// Liste tarihi = sipariş tarihi (içe aktarım/sync tarihi değil)
			$dateParts = self::splitOrderDateTime($orderDate);
			$sortDate = $orderDate !== '' ? $orderDate : $syncDate;
			$status = trim((string) ($ord['status'] ?? ''));
			$cargoProvider = trim((string) ($ord['cargo_provider'] ?? ''));
			$statusTone = self::orderStatusTone($status);
			$statusLabel = self::orderStatusLabel($status);
			$iconFile = $platformKey . '.png';

			$customerSub = self::stockMovementLabel((int) ($ord['stock_deducted'] ?? 0));

			if ($totalQty > 0) {
				$customerSub .= ' · ' . $totalQty . ' adet';
			}

			$totalPriceValue = self::resolveOrderTotalPrice($ord, $lines, $platformKey);
			$initials = self::customerInitials((string) ($ord['customer_name'] ?? ''));
			$statusStep = self::orderStatusStep($status);
			$costValue = self::estimateOrderCost($platformKey, $items);
			$profitValue = round($totalPriceValue - $costValue, 2);
			$profitRate = $totalPriceValue > 0 ? round(($profitValue / $totalPriceValue) * 100, 2) : 0.0;
			$dateList = trim(($dateParts['day'] ?? '') . ' ' . ($dateParts['time'] ?? ''));
			if ($dateList === '') {
				$dateList = '—';
			}

			$orderNumber = (string) ($ord['order_number'] ?? '');
			$packageId = (string) ($ord['shipment_package_id'] ?? '');
			$rowKey = preg_replace(
				'/[^a-zA-Z0-9_-]+/',
				'_',
				$platformKey . '-' . $orderNumber . '-' . $packageId
			);
			if (!is_string($rowKey) || $rowKey === '') {
				$rowKey = $platformKey . '-' . substr(md5($orderNumber . '|' . $packageId), 0, 12);
			}

			$rowData = [
				'platform' => $platformKey,
				'platform_label' => $platformLabel,
				'platform_icon' => strtoupper($platformKey === 'hepsiburada' ? 'hb' : $platformKey),
				'platform_icon_file' => $iconFile,
				'row_key' => $rowKey,
				'order_number' => $orderNumber,
				'shipment_package_id' => $packageId,
				'customer_name' => (string) ($ord['customer_name'] ?? ''),
				'customer_initials' => $initials,
				'customer_sub' => $customerSub,
				'status' => $statusLabel,
				'status_raw' => $status !== '' ? $status : '—',
				'status_class' => self::orderStatusClass($status),
				'status_tone' => $statusTone,
				'status_pill' => self::statusToneToPill($statusTone),
				'status_step' => $statusStep,
				'total_price' => Tools::displayPrice($totalPriceValue),
				'total_price_value' => $totalPriceValue,
				'cost_value' => $costValue,
				'cost_formatted' => Tools::displayPrice($costValue),
				'profit_value' => $profitValue,
				'profit_formatted' => Tools::displayPrice($profitValue),
				'profit_rate' => $profitRate,
				'profit_rate_formatted' => '%' . number_format(abs($profitRate), 2, ',', '.'),
				'is_profit' => $profitValue >= 0,
				'is_packed' => in_array($statusTone, ['navy', 'success', 'done'], true),
				'ship_tone' => self::statusToneToShip($statusTone),
				'stock_deducted' => (int) ($ord['stock_deducted'] ?? 0),
				'stock_label' => self::stockMovementLabel((int) ($ord['stock_deducted'] ?? 0)),
				'cargo_provider' => $cargoProvider !== '' ? $cargoProvider : '—',
				'cargo_tracking_number' => (string) ($ord['cargo_tracking_number'] ?? ''),
				'cargo_tracking_link' => self::resolveCargoTrackingLink($ord),
				'order_date' => $orderDate,
				'sort_date' => $sortDate,
				'display_date' => $orderDate !== '' ? Tools::formatDate3($orderDate) : '—',
				'date_day' => $orderDate !== '' ? $dateParts['day'] : '—',
				'date_time' => $orderDate !== '' ? $dateParts['time'] : '',
				'date_list' => $dateList,
				'items' => $items,
				'item_count' => count($items),
				'total_qty' => $totalQty,
				'payment_label' => 'Pazaryeri ödeme',
			];

			$rowData['detail_json'] = json_encode($rowData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
			$out[] = $rowData;
		}

		return $out;
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return array<int, array<string, mixed>>
	 */
	public static function getMarketplaceQuestionsForFilters(array $filters): array
	{
		$platform = trim((string) ($filters['marketplace_platform'] ?? $filters['platform'] ?? 'all'));
		$statusFilter = trim((string) ($filters['question_status'] ?? 'all'));

		if (!in_array($platform, ['all', 'trendyol', 'hepsiburada', 'n11'], true)) {
			$platform = 'all';
		}

		if (!in_array($statusFilter, ['all', 'waiting', 'answered'], true)) {
			$statusFilter = 'all';
		}

		$questions = [];

		if ($platform === 'all' || $platform === 'trendyol') {
			$questions = array_merge($questions, self::normalizeMarketplaceQuestions(
				Trendyol\QuestionService::getRecent(200),
				'trendyol',
				'Trendyol',
				'answerTrendyolQuestion'
			));
		}

		if ($platform === 'all' || $platform === 'hepsiburada') {
			$questions = array_merge($questions, self::normalizeMarketplaceQuestions(
				Hepsiburada\QuestionService::getRecent(200),
				'hepsiburada',
				'Hepsiburada',
				'answerHepsiburadaQuestion'
			));
		}

		if ($platform === 'all' || $platform === 'n11') {
			$questions = array_merge($questions, self::normalizeMarketplaceQuestions(
				N11\QuestionService::getRecent(200),
				'n11',
				'N11',
				'answerN11Question'
			));
		}

		usort($questions, static function (array $a, array $b): int {
			return strcmp((string) ($b['sort_date'] ?? ''), (string) ($a['sort_date'] ?? ''));
		});

		if ($statusFilter === 'waiting') {
			$questions = array_values(array_filter($questions, static function (array $q): bool {
				return empty($q['answered']);
			}));
		} elseif ($statusFilter === 'answered') {
			$questions = array_values(array_filter($questions, static function (array $q): bool {
				return !empty($q['answered']);
			}));
		}

		return $questions;
	}

	/**
	 * @param array<int, array<string, mixed>> $questions
	 * @return array<int, array<string, mixed>>
	 */
	private static function normalizeMarketplaceQuestions(
		array $questions,
		string $platformKey,
		string $platformLabel,
		string $answerAction
	): array {
		$out = [];

		foreach ($questions as $q) {
			$answered = !empty($q['answered']);
			$questionDate = trim((string) ($q['question_date'] ?? ''));
			$syncDate = trim((string) ($q['last_sync_at'] ?? ''));
			$sortDate = $questionDate !== '' ? $questionDate : $syncDate;
			$dateParts = self::splitOrderDateTime($sortDate);

			$out[] = [
				'platform' => $platformKey,
				'platform_label' => $platformLabel,
				'platform_icon' => strtoupper($platformKey === 'hepsiburada' ? 'hb' : $platformKey),
				'platform_icon_file' => $platformKey . '.png',
				'question_id' => (string) ($q['question_id'] ?? ''),
				'product_name' => (string) ($q['product_name'] ?? '—'),
				'barcode' => (string) ($q['barcode'] ?? ''),
				'question_text' => (string) ($q['question_text'] ?? ''),
				'answer_text' => (string) ($q['answer_text'] ?? ''),
				'answered' => $answered ? 1 : 0,
				'status' => $answered ? 'Cevaplandı' : 'Cevap bekleniyor',
				'status_tone' => $answered ? 'done' : 'pending',
				'status_raw' => (string) ($q['status'] ?? ''),
				'answer_action' => $answerAction,
				'question_date' => $questionDate,
				'sort_date' => $sortDate,
				'date_day' => $dateParts['day'],
				'date_time' => $dateParts['time'],
				'display_date' => $sortDate !== '' ? Tools::formatDate3($sortDate) : '—',
			];
		}

		return $out;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function getRecentMarketplaceOrders(int $limit = 20): array
	{
		$limit = max(1, min(50, $limit));
		$fetch = max($limit, 25);
		$orders = [];

		try {
			$orders = array_merge($orders, self::normalizeMarketplaceOrders(
				Trendyol\OrderService::getRecent($fetch),
				'trendyol',
				'Trendyol'
			));
		} catch (\Throwable $e) {
		}

		try {
			$orders = array_merge($orders, self::normalizeMarketplaceOrders(
				Hepsiburada\OrderService::getRecent($fetch),
				'hepsiburada',
				'Hepsiburada'
			));
		} catch (\Throwable $e) {
		}

		try {
			$orders = array_merge($orders, self::normalizeMarketplaceOrders(
				N11\OrderService::getRecent($fetch),
				'n11',
				'N11'
			));
		} catch (\Throwable $e) {
		}

		usort($orders, static function (array $a, array $b): int {
			return strcmp((string) ($b['sort_date'] ?? ''), (string) ($a['sort_date'] ?? ''));
		});

		return array_slice($orders, 0, $limit);
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return array<int, array<string, mixed>>
	 */
	public static function getMarketplaceOrdersForFilters(array $filters): array
	{
		$platform = trim((string) ($filters['marketplace_platform'] ?? $filters['platform'] ?? 'all'));
		$startDate = trim((string) ($filters['start_date'] ?? ''));
		$endDate = trim((string) ($filters['end_date'] ?? ''));
		$orderNumber = trim((string) ($filters['order_number'] ?? ''));
		$customerName = trim((string) ($filters['customer_name'] ?? ''));
		$productQuery = trim((string) ($filters['product_query'] ?? ''));
		$orderStatus = trim((string) ($filters['order_status'] ?? 'all'));

		if (!in_array($platform, ['all', 'trendyol', 'hepsiburada', 'n11'], true)) {
			$platform = 'all';
		}

		if (!in_array($orderStatus, ['all', 'pending', 'navy', 'success', 'done', 'danger', 'muted'], true)) {
			$orderStatus = 'all';
		}

		$orders = [];

		if ($platform === 'all' || $platform === 'trendyol') {
			$orders = array_merge($orders, self::normalizeMarketplaceOrders(
				Trendyol\OrderService::getRecent(500),
				'trendyol',
				'Trendyol'
			));
		}

		if ($platform === 'all' || $platform === 'hepsiburada') {
			$orders = array_merge($orders, self::normalizeMarketplaceOrders(
				Hepsiburada\OrderService::getRecent(500),
				'hepsiburada',
				'Hepsiburada'
			));
		}

		if ($platform === 'all' || $platform === 'n11') {
			$orders = array_merge($orders, self::normalizeMarketplaceOrders(
				N11\OrderService::getRecent(500),
				'n11',
				'N11'
			));
		}

		usort($orders, static function (array $a, array $b): int {
			return strcmp((string) ($b['sort_date'] ?? ''), (string) ($a['sort_date'] ?? ''));
		});

		$startTs = $startDate !== '' ? strtotime($startDate . ' 00:00:00') : null;
		$endTs = $endDate !== '' ? strtotime($endDate . ' 23:59:59') : null;

		$qOrderNumber = $orderNumber !== '' ? mb_strtolower($orderNumber) : '';
		$qCustomerName = $customerName !== '' ? mb_strtolower($customerName) : '';
		$qProduct = $productQuery !== '' ? mb_strtolower($productQuery) : '';

		$out = [];
		foreach ($orders as $ord) {
			$sortDate = (string) ($ord['sort_date'] ?? '');
			$ts = $sortDate !== '' ? strtotime($sortDate) : false;

			if ($startTs !== null && ($ts === false || $ts < $startTs)) {
				continue;
			}

			if ($endTs !== null && ($ts === false || $ts > $endTs)) {
				continue;
			}

			if ($qOrderNumber !== '' && mb_strpos(mb_strtolower((string) ($ord['order_number'] ?? '')), $qOrderNumber) === false) {
				continue;
			}

			if ($qCustomerName !== '' && mb_strpos(mb_strtolower((string) ($ord['customer_name'] ?? '')), $qCustomerName) === false) {
				continue;
			}

			if ($qProduct !== '') {
				$hay = [];
				if (!empty($ord['items']) && is_array($ord['items'])) {
					foreach ($ord['items'] as $it) {
						$hay[] = mb_strtolower((string) ($it['name'] ?? ''));
						$hay[] = mb_strtolower((string) ($it['sku'] ?? ''));
					}
				}

				$found = false;
				foreach ($hay as $h) {
					if ($h !== '' && mb_strpos($h, $qProduct) !== false) {
						$found = true;
						break;
					}
				}

				if (!$found) {
					continue;
				}
			}

			if ($orderStatus !== 'all') {
				$tone = (string) ($ord['status_tone'] ?? 'muted');
				if ($tone !== $orderStatus) {
					continue;
				}
			}

			$out[] = $ord;
		}

		return $out;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function getMarketplaceOrderDetail(string $platformKey, string $orderNumber, string $packageId): ?array
	{
		$platformKey = trim($platformKey);
		$orderNumber = trim($orderNumber);
		$packageId = trim($packageId);

		if (!in_array($platformKey, ['trendyol', 'hepsiburada', 'n11'], true)) {
			return null;
		}

		if ($orderNumber === '') {
			return null;
		}

		\MarketplaceTables::ensureSchema();

		$row = \MarketplaceTables::findOrder($platformKey, $orderNumber, $packageId);

		if (!$row) {
			return null;
		}

		$lines = json_decode((string) ($row['lines_json'] ?? ''), true);
		$row['lines'] = is_array($lines) ? $lines : [];

		$label = Marketplace::PLATFORMS[$platformKey]['label'] ?? ucfirst($platformKey);

		$normalized = self::normalizeMarketplaceOrders([$row], $platformKey, $label);
		$order = $normalized[0] ?? null;

		if (!$order) {
			return null;
		}

		return self::enrichOrderForLabel($order, $row);
	}

	/**
	 * @param array<int, array{platform?:string,order_number?:string,package_id?:string}> $keys
	 * @return array<int, array<string, mixed>>
	 */
	public static function getMarketplaceOrdersForPrint(array $keys): array
	{
		$orders = [];

		foreach ($keys as $key) {
			if (!is_array($key)) {
				continue;
			}

			$order = self::getMarketplaceOrderDetail(
				(string) ($key['platform'] ?? ''),
				(string) ($key['order_number'] ?? ''),
				(string) ($key['package_id'] ?? '')
			);

			if ($order) {
				$orders[] = $order;
			}
		}

		return $orders;
	}

	/**
	 * @param array<string, mixed> $order
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	private static function enrichOrderForLabel(array $order, array $row): array
	{
		$raw = json_decode((string) ($row['raw_json'] ?? ''), true);
		$raw = is_array($raw) ? $raw : [];
		$platform = (string) ($order['platform'] ?? '');

		if ($platform === 'hepsiburada' && !self::rawHasUsableAddress($raw)) {
			$detail = self::fetchHepsiburadaOrderDetail((string) ($order['order_number'] ?? ''));
			if ($detail !== null) {
				$raw = array_merge($raw, $detail);
				$row['raw_json'] = json_encode($raw, JSON_UNESCAPED_UNICODE);
				try {
					\MarketplaceTables::updateOrderById((int) ($row['id'] ?? 0), [
						'raw_json' => $row['raw_json'],
					]);
				} catch (\Throwable $e) {
					// ignore persist errors; label still uses merged raw
				}
			}
		}

		$extracted = self::extractLabelAddress($raw);
		$customerName = (string) ($order['customer_name'] ?? '');
		if (($customerName === '' || $customerName === '—') && $extracted['name'] !== '') {
			$customerName = $extracted['name'];
		}

		$addressLine = $extracted['address'];
		$cityLine = trim(
			$extracted['district']
			. ($extracted['district'] !== '' && $extracted['city'] !== '' ? ' / ' : '')
			. $extracted['city']
		);
		if ($extracted['postal'] !== '') {
			$cityLine = trim($cityLine . ($cityLine !== '' ? ' ' : '') . 'PK:' . $extracted['postal']);
		}
		if ($cityLine !== '' && $addressLine !== '') {
			$addressLine = trim($addressLine . ' ' . $cityLine);
		} elseif ($addressLine === '') {
			$addressLine = $cityLine;
		}

		$barcodeValue = trim((string) ($order['cargo_tracking_number'] ?? ''));
		if ($barcodeValue === '') {
			$barcodeValue = trim((string) ($order['order_number'] ?? ''));
		}

		$order['customer_name'] = $customerName !== '' ? $customerName : (string) ($order['customer_name'] ?? '—');
		$order['customer_phone'] = $extracted['phone'];
		$order['customer_address'] = $addressLine !== '' ? $addressLine : '—';
		$order['barcode_value'] = $barcodeValue;
		$order['barcode_svg'] = \Marketplace\Code128Barcode::toSvg($barcodeValue, 58, 1.45);

		if (!empty($order['items']) && is_array($order['items'])) {
			foreach ($order['items'] as $idx => $it) {
				if (!is_array($it)) {
					continue;
				}
				if (trim((string) ($it['image'] ?? '')) !== '') {
					continue;
				}
				$sku = (string) ($it['sku'] ?? '');
				$order['items'][$idx]['image'] = self::resolveLocalProductImage($platform, $sku, $it);
			}
		}

		return $order;
	}

	/** @param array<string, mixed> $raw */
	private static function rawHasUsableAddress(array $raw): bool
	{
		$extracted = self::extractLabelAddress($raw);

		return $extracted['address'] !== '' || ($extracted['city'] !== '' && $extracted['district'] !== '');
	}

	/**
	 * @return array{name:string,address:string,phone:string,city:string,district:string,postal:string}
	 * @param array<string, mixed> $raw
	 */
	private static function extractLabelAddress(array $raw): array
	{
		$out = [
			'name' => '',
			'address' => '',
			'phone' => '',
			'city' => '',
			'district' => '',
			'postal' => '',
		];

		$blocks = [];

		foreach ([
			'shipmentAddress',
			'shippingAddress',
			'deliveryAddress',
			'customerDeliveryAddress',
			'customerAddress',
			'invoiceAddress',
			'billingAddress',
			'address',
			'recipient',
			'shipping',
			'delivery',
		] as $key) {
			if (isset($raw[$key])) {
				$blocks[] = $raw[$key];
			}
		}

		if (isset($raw['customer']) && is_array($raw['customer'])) {
			$blocks[] = $raw['customer'];
			foreach (['shippingAddress', 'deliveryAddress', 'address', 'shipmentAddress'] as $key) {
				if (isset($raw['customer'][$key])) {
					$blocks[] = $raw['customer'][$key];
				}
			}
		}

		$items = $raw['items'] ?? ($raw['lines'] ?? null);
		if (is_array($items)) {
			foreach ($items as $item) {
				if (!is_array($item)) {
					continue;
				}
				foreach (['shippingAddress', 'deliveryAddress', 'shipmentAddress', 'address'] as $key) {
					if (isset($item[$key])) {
						$blocks[] = $item[$key];
					}
				}
			}
		}

		// Flat HB-style package fields
		$flatAddress = trim((string) (
			$raw['shippingAddressDetail']
			?? $raw['deliveryAddressDetail']
			?? $raw['addressDetail']
			?? $raw['fullAddress']
			?? ''
		));
		if ($flatAddress !== '' || isset($raw['city']) || isset($raw['cityName']) || isset($raw['town']) || isset($raw['district'])) {
			$blocks[] = [
				'fullAddress' => $flatAddress,
				'address' => trim((string) ($raw['address'] ?? '')),
				'city' => $raw['city'] ?? ($raw['cityName'] ?? ''),
				'district' => $raw['district'] ?? ($raw['districtName'] ?? ($raw['town'] ?? ($raw['townName'] ?? ''))),
				'neighborhood' => $raw['neighborhood'] ?? ($raw['neighborhoodName'] ?? ''),
				'postalCode' => $raw['postalCode'] ?? ($raw['zipCode'] ?? ''),
				'phone' => $raw['phoneNumber'] ?? ($raw['customerPhoneNumber'] ?? ($raw['gsm'] ?? '')),
				'fullName' => $raw['customerName'] ?? ($raw['recipientName'] ?? ''),
			];
		}

		foreach ($blocks as $block) {
			if (is_string($block)) {
				$block = trim($block);
				if ($block !== '' && $out['address'] === '') {
					$out['address'] = $block;
				}
				continue;
			}

			if (!is_array($block)) {
				continue;
			}

			if ($out['name'] === '') {
				$full = trim((string) ($block['fullName'] ?? ''));
				if ($full === '') {
					$full = trim(self::scalarText($block['firstName'] ?? '') . ' ' . self::scalarText($block['lastName'] ?? ''));
				}
				if ($full === '') {
					$full = trim(self::scalarText($block['name'] ?? ($block['recipientName'] ?? ($block['customerName'] ?? ''))));
				}
				if ($full !== '') {
					$out['name'] = $full;
				}
			}

			if ($out['address'] === '') {
				$address = trim((string) (
					$block['fullAddress']
					?? $block['shippingAddressDetail']
					?? $block['address1']
					?? $block['address']
					?? $block['addressLine']
					?? $block['addressLine1']
					?? ''
				));
				$extra = trim((string) ($block['address2'] ?? ($block['addressLine2'] ?? '')));
				$neighborhood = self::scalarText($block['neighborhood'] ?? ($block['neighborhoodName'] ?? ''));
				if ($neighborhood !== '' && stripos($address, $neighborhood) === false) {
					$address = trim($address . ' ' . $neighborhood);
				}
				if ($extra !== '') {
					$address = trim($address . ' ' . $extra);
				}
				if ($address !== '') {
					$out['address'] = $address;
				}
			}

			if ($out['phone'] === '') {
				$phone = trim((string) (
					$block['phone']
					?? $block['mobilePhone']
					?? $block['gsm']
					?? $block['phoneNumber']
					?? $block['customerPhoneNumber']
					?? ''
				));
				if ($phone !== '') {
					$out['phone'] = $phone;
				}
			}

			if ($out['city'] === '') {
				$city = self::scalarText($block['city'] ?? ($block['cityName'] ?? ''));
				if ($city !== '') {
					$out['city'] = $city;
				}
			}

			if ($out['district'] === '') {
				$district = self::scalarText(
					$block['district']
					?? $block['districtName']
					?? $block['county']
					?? $block['countyName']
					?? $block['town']
					?? $block['townName']
					?? ''
				);
				if ($district !== '') {
					$out['district'] = $district;
				}
			}

			if ($out['postal'] === '') {
				$postal = trim((string) ($block['postalCode'] ?? ($block['zipCode'] ?? ($block['postCode'] ?? ''))));
				if ($postal !== '') {
					$out['postal'] = $postal;
				}
			}
		}

		if ($out['phone'] === '') {
			$out['phone'] = trim((string) (
				$raw['customerPhone']
				?? $raw['customerPhoneNumber']
				?? $raw['phoneNumber']
				?? $raw['gsm']
				?? ''
			));
		}

		if ($out['name'] === '') {
			$out['name'] = trim((string) ($raw['customerName'] ?? ($raw['recipientName'] ?? '')));
		}

		return $out;
	}

	/** @param mixed $value */
	private static function scalarText($value): string
	{
		if (is_array($value)) {
			return trim((string) ($value['name'] ?? ($value['value'] ?? ($value['cityName'] ?? ($value['districtName'] ?? '')))));
		}

		return trim((string) $value);
	}

	/** @return array<string, mixed>|null */
	private static function fetchHepsiburadaOrderDetail(string $orderNumber): ?array
	{
		$orderNumber = trim($orderNumber);
		if ($orderNumber === '' || !class_exists('Hepsiburada\\ProductSyncService')) {
			return null;
		}

		try {
			if (!\Hepsiburada\ProductSyncService::isConfigured()) {
				return null;
			}

			$result = \Hepsiburada\ProductSyncService::api()->getOrderDetail($orderNumber);
			if (!is_array($result) || \Hepsiburada\ProductSyncService::isApiError($result)) {
				return null;
			}

			return $result;
		} catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * @param array<string, mixed> $line
	 */
	private static function resolveLocalProductImage(string $platform, string $sku, array $line = []): string
	{
		$candidates = array_filter([
			$sku,
			(string) ($line['barcode'] ?? ''),
			(string) ($line['merchantSku'] ?? ''),
			(string) ($line['stockCode'] ?? ''),
			(string) ($line['hepsiburadaSku'] ?? ''),
			(string) ($line['sku'] ?? ''),
			(string) ($line['productCode'] ?? ''),
		], static function ($v) {
			return trim((string) $v) !== '';
		});

		$idProduct = 0;
		foreach ($candidates as $code) {
			$idProduct = self::findLocalProductId($platform, (string) $code);
			if ($idProduct > 0) {
				break;
			}
		}

		if ($idProduct <= 0) {
			return '';
		}

		$idImage = (int) (\DB::getValue(
			'SELECT id_image FROM images WHERE id_product = ? ORDER BY cover DESC, id_image ASC LIMIT 1',
			[$idProduct]
		) ?: 0);

		if ($idImage <= 0) {
			return '';
		}

		$url = \Product::getImageUrl($idImage);
		if (strpos($url, 'img/products/') === false) {
			return '';
		}

		return $url;
	}

	private static function findLocalProductId(string $platform, string $code): int
	{
		$code = trim($code);
		if ($code === '') {
			return 0;
		}

		if ($platform === 'trendyol') {
			$map = \DB::getRowSafe('trendyol_products', 'barcode = ?', [$code]);
			if ($map && (int) ($map['id_product'] ?? 0) > 0) {
				return (int) $map['id_product'];
			}
		} elseif ($platform === 'hepsiburada') {
			$map = \DB::getRowSafe(
				'hepsiburada_products',
				'merchant_sku = ? OR hepsiburada_sku = ?',
				[$code, $code]
			);
			if ($map && (int) ($map['id_product'] ?? 0) > 0) {
				return (int) $map['id_product'];
			}
		} elseif ($platform === 'n11') {
			$map = \DB::getRowSafe('n11_products', 'stock_code = ? OR barcode = ?', [$code, $code]);
			if ($map && (int) ($map['id_product'] ?? 0) > 0) {
				return (int) $map['id_product'];
			}
		}

		$id = (int) (\DB::getValue(
			'SELECT id_product FROM products WHERE barcode = ? LIMIT 1',
			[$code]
		) ?: 0);

		if ($id > 0) {
			return $id;
		}

		return (int) (\DB::getValue(
			'SELECT id_product FROM products WHERE stock_code = ? LIMIT 1',
			[$code]
		) ?: 0);
	}

	/** @return array{day:string,time:string} */
	private static function splitOrderDateTime(string $datetime): array
	{
		$datetime = trim($datetime);

		if ($datetime === '' || $datetime === '0000-00-00 00:00:00') {
			return ['day' => '—', 'time' => ''];
		}

		$ts = strtotime($datetime);

		if ($ts === false) {
			return ['day' => $datetime, 'time' => ''];
		}

		return [
			'day' => date('d.m.Y', $ts),
			'time' => date('H:i', $ts),
		];
	}

	/**
	 * Kolon veya raw_json içinden kargo takip URL'si.
	 *
	 * @param array<string, mixed> $ord
	 */
	private static function resolveCargoTrackingLink(array $ord): string
	{
		$link = trim((string) ($ord['cargo_tracking_link'] ?? ''));

		if ($link !== '' && preg_match('#^https?://#i', $link)) {
			return $link;
		}

		$raw = $ord['raw_json'] ?? null;

		if (is_string($raw) && $raw !== '') {
			$decoded = json_decode($raw, true);
			$raw = is_array($decoded) ? $decoded : null;
		}

		if (is_array($raw)) {
			return \MarketplaceTables::extractCargoTrackingLink($raw);
		}

		return '';
	}

	/**
	 * raw_json içinden sipariş tarihini çıkarır (order_date boşsa).
	 *
	 * @param array<string, mixed> $ord
	 */
	private static function extractOrderDateFromRaw(array $ord): string
	{
		$raw = $ord['raw_json'] ?? null;

		if (is_string($raw) && $raw !== '') {
			$decoded = json_decode($raw, true);
			$raw = is_array($decoded) ? $decoded : null;
		}

		if (!is_array($raw)) {
			return '';
		}

		$candidates = [
			$raw['orderDate'] ?? null,
			$raw['packageDate'] ?? null,
			$raw['createdDate'] ?? null,
			$raw['lastModifiedDate'] ?? null,
			$raw['packageLastModifiedDate'] ?? null,
		];

		foreach ($candidates as $rawDate) {
			if ($rawDate === null || $rawDate === '') {
				continue;
			}

			if (is_numeric($rawDate)) {
				$ts = strlen((string) $rawDate) > 10
					? (int) round(((int) $rawDate) / 1000)
					: (int) $rawDate;

				if ($ts > 0) {
					return date('Y-m-d H:i:s', $ts);
				}
			}

			if (is_string($rawDate)) {
				$ts = strtotime($rawDate);

				if ($ts) {
					return date('Y-m-d H:i:s', $ts);
				}
			}
		}

		return '';
	}

	private static function orderStatusTone(string $status): string
	{
		return self::resolveOrderStatusMeta($status)['tone'];
	}

	private static function statusToneToPill(string $tone): string
	{
		$map = [
			'pending' => 'pending',
			'navy' => 'processing',
			'success' => 'shipped',
			'done' => 'delivered',
			'danger' => 'cancelled',
			'muted' => 'default',
		];

		return $map[$tone] ?? 'default';
	}

	private static function statusToneToShip(string $tone): string
	{
		$map = [
			'pending' => 'later',
			'navy' => 'today',
			'success' => 'shipped',
			'done' => 'shipped',
			'danger' => 'overdue',
			'muted' => 'none',
		];

		return $map[$tone] ?? 'none';
	}

	/**
	 * @param array<int, array<string, mixed>> $items
	 */
	private static function estimateOrderCost(string $platform, array $items): float
	{
		$total = 0.0;

		foreach ($items as $item) {
			$sku = trim((string) ($item['sku'] ?? ''));
			$qty = max(1, (int) ($item['quantity'] ?? 1));
			$idProduct = $sku !== '' ? self::findLocalProductId($platform, $sku) : 0;

			if ($idProduct <= 0 && $sku !== '') {
				$idProduct = (int) (DB::getValue(
					'SELECT id_product FROM products WHERE stock_code = ? OR barcode = ? LIMIT 1',
					[$sku, $sku]
				) ?: 0);
			}

			if ($idProduct <= 0) {
				continue;
			}

			$cost = (float) (DB::getValue('SELECT cost FROM products WHERE id_product = ? LIMIT 1', [$idProduct]) ?: 0);
			$total += $cost * $qty;
		}

		return round($total, 2);
	}

	private static function orderStatusLabel(string $status): string
	{
		return self::resolveOrderStatusMeta($status)['label'];
	}

	/**
	 * @return array{label: string, tone: string, step: int}
	 */
	private static function resolveOrderStatusMeta(string $status): array
	{
		$key = strtolower(trim($status));
		$key = preg_replace('/[\s_\-]+/', '', $key) ?: '';

		if ($key === '') {
			return ['label' => 'Hazırlanıyor', 'tone' => 'pending', 'step' => 1];
		}

		$exact = [
			'created' => ['Hazırlanıyor', 'pending', 1],
			'open' => ['Hazırlanıyor', 'pending', 1],
			'opened' => ['Hazırlanıyor', 'pending', 1],
			'new' => ['Hazırlanıyor', 'pending', 1],
			'approved' => ['Hazırlanıyor', 'pending', 1],
			'processing' => ['Hazırlanıyor', 'pending', 1],
			'waiting' => ['Hazırlanıyor', 'pending', 1],
			'waitingbypayment' => ['Hazırlanıyor', 'pending', 1],
			'paymentawaiting' => ['Hazırlanıyor', 'pending', 1],
			'invoiced' => ['Hazırlanıyor', 'pending', 1],
			'unpacked' => ['Hazırlanıyor', 'pending', 1],
			'picking' => ['Paketleniyor', 'navy', 2],
			'packed' => ['Paketleniyor', 'navy', 2],
			'packaged' => ['Paketleniyor', 'navy', 2],
			'readytoship' => ['Paketleniyor', 'navy', 2],
			'shipped' => ['Kargoda', 'success', 3],
			'intransit' => ['Kargoda', 'success', 3],
			'received' => ['Kargoda', 'success', 3],
			'shipping' => ['Kargoda', 'success', 3],
			'atcollectionpoint' => ['Kargoda', 'success', 3],
			'delivered' => ['Teslim Edildi', 'done', 4],
			'completed' => ['Teslim Edildi', 'done', 4],
			'complete' => ['Teslim Edildi', 'done', 4],
			'cancelled' => ['İptal', 'danger', 0],
			'canceled' => ['İptal', 'danger', 0],
			'cancelledbymerchant' => ['İptal', 'danger', 0],
			'cancelbycustomer' => ['İptal', 'danger', 0],
			'unsupplied' => ['İptal', 'danger', 0],
			'returned' => ['İade', 'danger', 0],
			'undelivered' => ['Teslim Edilemedi', 'danger', 0],
		];

		if (isset($exact[$key])) {
			return [
				'label' => $exact[$key][0],
				'tone' => $exact[$key][1],
				'step' => $exact[$key][2],
			];
		}

		// Belirsiz stringler: önce iptal/iade/teslim edilemedi (undelivered, delivered'dan önce)
		if (
			strpos($key, 'undeliver') !== false
			|| strpos($key, 'unsupplied') !== false
			|| strpos($key, 'cancel') !== false
			|| strpos($key, 'iptal') !== false
		) {
			return ['label' => 'İptal', 'tone' => 'danger', 'step' => 0];
		}

		if (strpos($key, 'return') !== false || strpos($key, 'iade') !== false) {
			return ['label' => 'İade', 'tone' => 'danger', 'step' => 0];
		}

		if (
			strpos($key, 'deliver') !== false
			|| strpos($key, 'teslim') !== false
			|| strpos($key, 'complete') !== false
		) {
			return ['label' => 'Teslim Edildi', 'tone' => 'done', 'step' => 4];
		}

		if (
			strpos($key, 'ship') !== false
			|| strpos($key, 'transit') !== false
			|| strpos($key, 'cargo') !== false
			|| strpos($key, 'kargo') !== false
			|| $key === 'received'
		) {
			return ['label' => 'Kargoda', 'tone' => 'success', 'step' => 3];
		}

		if (
			strpos($key, 'picking') !== false
			|| strpos($key, 'packed') !== false
			|| strpos($key, 'packaged') !== false
			|| strpos($key, 'readytoship') !== false
			|| strpos($key, 'paket') !== false
		) {
			return ['label' => 'Paketleniyor', 'tone' => 'navy', 'step' => 2];
		}

		return ['label' => 'Hazırlanıyor', 'tone' => 'pending', 'step' => 1];
	}

	/**
	 * @param array<string, mixed> $ord
	 * @param array<int, mixed> $lines
	 */
	private static function resolveOrderTotalPrice(array $ord, array $lines, string $platformKey): float
	{
		$total = (float) ($ord['total_price'] ?? 0);

		if ($total > 1) {
			return round($total, 2);
		}

		$sum = 0.0;

		foreach ($lines as $line) {
			if (!is_array($line)) {
				continue;
			}

			$qty = max(1, (int) ($line['quantity'] ?? 1));
			$lineTotal = null;

			foreach (['totalPrice', 'merchantTotalPrice', 'lineTotalPrice'] as $key) {
				if (!isset($line[$key])) {
					continue;
				}

				$raw = $line[$key];

				if (is_array($raw) && isset($raw['amount'])) {
					$lineTotal = (float) $raw['amount'];
					break;
				}

				if (is_numeric($raw)) {
					$lineTotal = (float) $raw;
					break;
				}
			}

			if ($lineTotal === null && $platformKey === 'hepsiburada') {
				$unit = Hepsiburada\ProductSyncService::extractOrderLineSalePrice($line);

				if ($unit !== null && $unit > 0) {
					$lineTotal = $unit * $qty;
				}
			}

			if ($lineTotal === null) {
				foreach (['price', 'unitPrice', 'salePrice', 'amount'] as $key) {
					if (!isset($line[$key]) || is_array($line[$key])) {
						continue;
					}

					$value = (float) $line[$key];

					if ($value > 0) {
						$lineTotal = $value * $qty;
						break;
					}
				}
			}

			if ($lineTotal !== null && $lineTotal > 0) {
				$sum += $lineTotal;
			}
		}

		if ($sum > $total) {
			return round($sum, 2);
		}

		return round($total, 2);
	}

	private static function orderStatusStep(string $status): int
	{
		return self::resolveOrderStatusMeta($status)['step'];
	}

	private static function customerInitials(string $name): string
	{
		$name = trim($name);

		if ($name === '') {
			return '?';
		}

		$parts = preg_split('/\s+/u', $name) ?: [];
		$out = '';

		foreach ($parts as $part) {
			$part = trim((string) $part);

			if ($part === '') {
				continue;
			}

			$out .= mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8');

			if (mb_strlen($out, 'UTF-8') >= 2) {
				break;
			}
		}

		return $out !== '' ? $out : '?';
	}

	private static function orderStatusClass(string $status): string
	{
		$tone = self::orderStatusTone($status);

		if ($tone === 'success' || $tone === 'done') {
			return 'success';
		}

		if ($tone === 'pending') {
			return 'warning';
		}

		if ($tone === 'navy') {
			return 'primary';
		}

		if ($tone === 'danger') {
			return 'danger';
		}

		return 'secondary';
	}

	private static function stockMovementLabel(int $stockDeducted): string
	{
		if ($stockDeducted === 1) {
			return 'Stok düşüldü';
		}

		if ($stockDeducted === 2) {
			return 'Stok iade edildi';
		}

		return 'Bekliyor';
	}
}
