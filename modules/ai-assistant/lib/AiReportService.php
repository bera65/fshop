<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once __DIR__ . '/AiClient.php';

class AiReportService
{
	/** @return array<string, string> */
	public static function marketplaceOptions(): array
	{
		$options = [
			'all' => 'Tüm kanallar',
			'store' => 'Mağaza (web sitesi)',
			'pos' => 'POS / Kasa',
		];

		if (self::hasTrendyolTable()) {
			$options['trendyol'] = 'Trendyol';
		}

		return $options;
	}

	public static function hasTrendyolTable(): bool
	{
		static $cached = null;

		if ($cached !== null) {
			return $cached;
		}

		$row = DB::execute("SHOW TABLES LIKE 'trendyol_orders'");

		$cached = !empty($row);

		return $cached;
	}

	/** @return array{success: bool, message?: string, analysis?: string, model?: string, summary?: array<string, mixed>} */
	public static function analyzeSales(string $dateFrom, string $dateTo, string $channel): array
	{
		if (!AiAssistantClient::isConfigured()) {
			return ['success' => false, 'message' => 'API anahtarı tanımlı değil. Modül ayarlarından ekleyin.'];
		}

		$payload = self::collectSalesReportData($dateFrom, $dateTo, $channel);

		if (empty($payload['totals']['order_count'])) {
			return [
				'success' => false,
				'message' => 'Seçilen tarih aralığı ve kanal için satış bulunamadı.',
			];
		}

		$system = 'Sen deneyimli bir e-ticaret satış analistisin. Türkçe, net ve uygulanabilir yaz. '
			. 'Markdown kullan (## başlık, madde listeleri). Verilere sadık kal; uydurma.';

		$user = "Satış raporu analizi isteniyor.\n\n"
			. "Kapsam:\n"
			. "1) Dönem satış özeti (adet, ciro, ortalama sepet)\n"
			. "2) Durum dağılımı yorumu\n"
			. "3) Ödeme / kanal içgörüleri\n"
			. "4) En çok satan ürünler\n"
			. "5) Büyüme için 5 somut aksiyon\n\n"
			. "VERİ (JSON):\n"
			. json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

		$result = AiAssistantClient::chat($system, $user, [
			'max_tokens' => 2400,
			'temperature' => 0.35,
		]);

		if (empty($result['success'])) {
			return $result;
		}

		return [
			'success' => true,
			'message' => 'Satış raporu hazır',
			'analysis' => (string) $result['content'],
			'model' => (string) ($result['model'] ?? ''),
			'summary' => $payload['totals'],
		];
	}

	/** @return array{success: bool, message?: string, analysis?: string, model?: string, summary?: array<string, mixed>} */
	public static function analyzeProductsSeo(int $limit = 60): array
	{
		if (!AiAssistantClient::isConfigured()) {
			return ['success' => false, 'message' => 'API anahtarı tanımlı değil. Modül ayarlarından ekleyin.'];
		}

		$payload = self::collectProductSeoData($limit);

		if (empty($payload['products'])) {
			return ['success' => false, 'message' => 'Analiz edilecek aktif ürün bulunamadı.'];
		}

		$system = 'Sen SEO odaklı bir e-ticaret ürün danışmanısın. Türkçe yaz. Markdown kullan. '
			. 'Her ürün için kısa skor (0-100) ve 1-2 somut iyileştirme öner. '
			. 'Önce genel mağaza SEO özeti, sonra en kritik 10 ürün detayı, son olarak toplu aksiyon listesi ver.';

		$user = "Ürün SEO analizi.\n\n"
			. "meta_title (50-60 karakter), meta_description (120-155), açıklama uzunluğu, "
			. "kısa açıklama, URL dostu isim ve eksik alanları değerlendir.\n\n"
			. "VERİ (JSON):\n"
			. json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

		$result = AiAssistantClient::chat($system, $user, [
			'max_tokens' => 2800,
			'temperature' => 0.4,
		]);

		if (empty($result['success'])) {
			return $result;
		}

		return [
			'success' => true,
			'message' => 'Ürün SEO raporu hazır',
			'analysis' => (string) $result['content'],
			'model' => (string) ($result['model'] ?? ''),
			'summary' => $payload['overview'],
		];
	}

	/** @return array{success: bool, message?: string, analysis?: string, model?: string, summary?: array<string, mixed>} */
	public static function analyzeCancelReturns(string $dateFrom, string $dateTo, string $channel): array
	{
		if (!AiAssistantClient::isConfigured()) {
			return ['success' => false, 'message' => 'API anahtarı tanımlı değil. Modül ayarlarından ekleyin.'];
		}

		$payload = self::collectCancelReturnReportData($dateFrom, $dateTo, $channel);

		if (empty($payload['totals']['total_orders'])) {
			return [
				'success' => false,
				'message' => 'Seçilen tarih aralığı ve kanal için sipariş bulunamadı.',
			];
		}

		if (empty($payload['totals']['cancelled_count']) && empty($payload['totals']['returned_count']) && empty($payload['totals']['return_pending_count'])) {
			return [
				'success' => false,
				'message' => 'Seçilen dönemde iptal veya iade kaydı bulunamadı.',
			];
		}

		$system = 'Sen e-ticaret operasyon ve müşteri deneyimi analistisin. Türkçe yaz. Markdown kullan. '
			. 'İptal ve iade verilerini yorumla: oranlar, kayıp ciro, ürün/kanal kalıpları, müşteri mesajları. '
			. 'Önleyici aksiyonlar ve süreç iyileştirmeleri öner. Verilere sadık kal.';

		$user = "İptal ve iade raporu analizi.\n\n"
			. "Kapsam:\n"
			. "1) Dönem özeti (iptal/iade adet ve oranları, kayıp ciro)\n"
			. "2) Ürün ve kanal bazlı risk alanları\n"
			. "3) İade talebi mesajlarından çıkarımlar (varsa)\n"
			. "4) Operasyonel iyileştirme önerileri\n"
			. "5) Öncelikli 5 aksiyon\n\n"
			. "VERİ (JSON):\n"
			. json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

		$result = AiAssistantClient::chat($system, $user, [
			'max_tokens' => 2600,
			'temperature' => 0.35,
		]);

		if (empty($result['success'])) {
			return $result;
		}

		return [
			'success' => true,
			'message' => 'İptal / iade raporu hazır',
			'analysis' => (string) $result['content'],
			'model' => (string) ($result['model'] ?? ''),
			'summary' => $payload['totals'],
		];
	}

	/** @return array<string, mixed> */
	public static function collectSalesReportData(string $dateFrom, string $dateTo, string $channel): array
	{
		$dateFrom = self::normalizeDate($dateFrom, '-30 days');
		$dateTo = self::normalizeDate($dateTo, 'today');
		$channel = array_key_exists($channel, self::marketplaceOptions()) ? $channel : 'all';

		if ($channel === 'trendyol') {
			return self::collectTrendyolOrderData($dateFrom, $dateTo);
		}

		$cancelled = class_exists('Order') ? Order::STATUS_CANCELLED : 5;
		$params = [$cancelled, $dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];
		$channelSql = self::buildChannelSql($channel);

		$totals = DB::execute(
			"SELECT COUNT(*) AS order_count,
				COALESCE(SUM(o.total), 0) AS revenue,
				COALESCE(AVG(o.total), 0) AS avg_basket,
				COALESCE(SUM(o.shipping), 0) AS shipping_total,
				COALESCE(SUM(o.coupon_discount), 0) AS coupon_total,
				COALESCE(SUM(o.promotion_discount), 0) AS promotion_total
			 FROM orders o
			 WHERE o.status != ?
			   AND o.date_add BETWEEN ? AND ?
			   {$channelSql}",
			$params
		);

		$totalRow = $totals[0] ?? [];

		$byStatus = DB::execute(
			"SELECT o.status, COUNT(*) AS cnt, COALESCE(SUM(o.total), 0) AS revenue
			 FROM orders o
			 WHERE o.status != ?
			   AND o.date_add BETWEEN ? AND ?
			   {$channelSql}
			 GROUP BY o.status
			 ORDER BY cnt DESC",
			$params
		) ?: [];

		$byPayment = DB::execute(
			"SELECT o.payment_method, COUNT(*) AS cnt, COALESCE(SUM(o.total), 0) AS revenue
			 FROM orders o
			 WHERE o.status != ?
			   AND o.date_add BETWEEN ? AND ?
			   {$channelSql}
			 GROUP BY o.payment_method
			 ORDER BY revenue DESC
			 LIMIT 12",
			$params
		) ?: [];

		$topProducts = DB::execute(
			"SELECT od.id_product, od.product_name,
				SUM(od.qty) AS sold_qty,
				COALESCE(SUM(od.total), 0) AS sold_revenue
			 FROM order_detail od
			 INNER JOIN orders o ON o.id_order = od.id_order
			 WHERE o.status != ?
			   AND o.date_add BETWEEN ? AND ?
			   {$channelSql}
			 GROUP BY od.id_product, od.product_name
			 ORDER BY sold_qty DESC
			 LIMIT 15",
			$params
		) ?: [];

		$daily = DB::execute(
			"SELECT DATE(o.date_add) AS day, COUNT(*) AS orders, COALESCE(SUM(o.total), 0) AS revenue
			 FROM orders o
			 WHERE o.status != ?
			   AND o.date_add BETWEEN ? AND ?
			   {$channelSql}
			 GROUP BY DATE(o.date_add)
			 ORDER BY day ASC",
			$params
		) ?: [];

		$statusLabels = class_exists('Order') ? Order::getStatusOptions() : [];

		foreach ($byStatus as &$row) {
			$code = (int) ($row['status'] ?? 0);
			$row['status_label'] = $statusLabels[$code] ?? ('Durum ' . $code);
		}
		unset($row);

		return [
			'channel' => $channel,
			'channel_label' => self::marketplaceOptions()[$channel] ?? $channel,
			'date_from' => $dateFrom,
			'date_to' => $dateTo,
			'totals' => [
				'order_count' => (int) ($totalRow['order_count'] ?? 0),
				'revenue' => round((float) ($totalRow['revenue'] ?? 0), 2),
				'avg_basket' => round((float) ($totalRow['avg_basket'] ?? 0), 2),
				'shipping_total' => round((float) ($totalRow['shipping_total'] ?? 0), 2),
				'coupon_total' => round((float) ($totalRow['coupon_total'] ?? 0), 2),
				'promotion_total' => round((float) ($totalRow['promotion_total'] ?? 0), 2),
			],
			'by_status' => $byStatus,
			'by_payment' => $byPayment,
			'top_products' => $topProducts,
			'daily' => $daily,
		];
	}

	/** @return array<string, mixed> */
	private static function collectTrendyolOrderData(string $dateFrom, string $dateTo): array
	{
		if (!self::hasTrendyolTable()) {
			return [
				'channel' => 'trendyol',
				'date_from' => $dateFrom,
				'date_to' => $dateTo,
				'totals' => ['order_count' => 0],
				'by_status' => [],
				'top_products' => [],
				'daily' => [],
			];
		}

		$params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];

		$totals = DB::execute(
			"SELECT COUNT(*) AS order_count,
				COALESCE(SUM(total_price), 0) AS revenue,
				COALESCE(AVG(total_price), 0) AS avg_basket
			 FROM trendyol_orders
			 WHERE order_date BETWEEN ? AND ?",
			$params
		);

		$totalRow = $totals[0] ?? [];

		$byStatus = DB::execute(
			"SELECT status, COUNT(*) AS cnt, COALESCE(SUM(total_price), 0) AS revenue
			 FROM trendyol_orders
			 WHERE order_date BETWEEN ? AND ?
			 GROUP BY status
			 ORDER BY cnt DESC",
			$params
		) ?: [];

		$daily = DB::execute(
			"SELECT DATE(order_date) AS day, COUNT(*) AS orders, COALESCE(SUM(total_price), 0) AS revenue
			 FROM trendyol_orders
			 WHERE order_date BETWEEN ? AND ?
			 GROUP BY DATE(order_date)
			 ORDER BY day ASC",
			$params
		) ?: [];

		return [
			'channel' => 'trendyol',
			'channel_label' => 'Trendyol',
			'date_from' => $dateFrom,
			'date_to' => $dateTo,
			'totals' => [
				'order_count' => (int) ($totalRow['order_count'] ?? 0),
				'revenue' => round((float) ($totalRow['revenue'] ?? 0), 2),
				'avg_basket' => round((float) ($totalRow['avg_basket'] ?? 0), 2),
			],
			'by_status' => $byStatus,
			'by_payment' => [],
			'top_products' => [],
			'daily' => $daily,
			'note' => 'Trendyol siparişleri trendyol_orders tablosundan okunur.',
		];
	}

	/** @return array<string, mixed> */
	public static function collectProductSeoData(int $limit = 60): array
	{
		$limit = max(10, min(120, $limit));

		$rows = DB::execute(
			"SELECT id_product, product_name, product_link, meta_title, meta_description,
				short_description, description
			 FROM products
			 WHERE active = 1
			 ORDER BY id_product DESC
			 LIMIT {$limit}"
		) ?: [];

		$issues = [
			'missing_meta_title' => 0,
			'missing_meta_description' => 0,
			'short_description_empty' => 0,
			'description_empty' => 0,
			'weak_meta_title' => 0,
		];

		$products = [];

		foreach ($rows as $row) {
			$metaTitle = trim((string) ($row['meta_title'] ?? ''));
			$metaDesc = trim((string) ($row['meta_description'] ?? ''));
			$short = trim(strip_tags((string) ($row['short_description'] ?? '')));
			$desc = trim(strip_tags((string) ($row['description'] ?? '')));
			$score = 100;
			$flags = [];

			if ($metaTitle === '') {
				$issues['missing_meta_title']++;
				$score -= 25;
				$flags[] = 'meta_title_missing';
			} elseif (mb_strlen($metaTitle) < 30 || mb_strlen($metaTitle) > 65) {
				$issues['weak_meta_title']++;
				$score -= 10;
				$flags[] = 'meta_title_length';
			}

			if ($metaDesc === '') {
				$issues['missing_meta_description']++;
				$score -= 20;
				$flags[] = 'meta_description_missing';
			} elseif (mb_strlen($metaDesc) < 80 || mb_strlen($metaDesc) > 160) {
				$score -= 8;
				$flags[] = 'meta_description_length';
			}

			if ($short === '') {
				$issues['short_description_empty']++;
				$score -= 15;
				$flags[] = 'short_description_empty';
			}

			if (mb_strlen($desc) < 120) {
				$issues['description_empty']++;
				$score -= 15;
				$flags[] = 'description_thin';
			}

			$products[] = [
				'id_product' => (int) ($row['id_product'] ?? 0),
				'product_name' => (string) ($row['product_name'] ?? ''),
				'link_rewrite' => (string) ($row['product_link'] ?? ''),
				'meta_title' => $metaTitle,
				'meta_title_len' => mb_strlen($metaTitle),
				'meta_description' => $metaDesc,
				'meta_description_len' => mb_strlen($metaDesc),
				'short_description_len' => mb_strlen($short),
				'description_len' => mb_strlen($desc),
				'seo_score' => max(0, $score),
				'flags' => $flags,
			];
		}

		usort($products, static function ($a, $b) {
			return ($a['seo_score'] ?? 0) <=> ($b['seo_score'] ?? 0);
		});

		return [
			'overview' => [
				'product_count' => count($products),
				'issues' => $issues,
				'avg_score' => count($products)
					? round(array_sum(array_column($products, 'seo_score')) / count($products), 1)
					: 0,
			],
			'products' => $products,
		];
	}

	/** @return array<string, mixed> */
	public static function collectCancelReturnReportData(string $dateFrom, string $dateTo, string $channel): array
	{
		$dateFrom = self::normalizeDate($dateFrom, '-30 days');
		$dateTo = self::normalizeDate($dateTo, 'today');
		$channel = array_key_exists($channel, self::marketplaceOptions()) ? $channel : 'all';

		if ($channel === 'trendyol') {
			return self::collectTrendyolCancelReturnData($dateFrom, $dateTo);
		}

		$cancelled = class_exists('Order') ? Order::STATUS_CANCELLED : 5;
		$returned = class_exists('Order') ? Order::STATUS_RETURNED : 6;
		$returnPending = class_exists('Order') ? Order::STATUS_RETURN_PENDING : 7;
		$params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];
		$channelSql = self::buildChannelSql($channel);

		$allOrders = DB::execute(
			"SELECT COUNT(*) AS total_orders, COALESCE(SUM(o.total), 0) AS total_revenue
			 FROM orders o
			 WHERE o.date_add BETWEEN ? AND ?
			   {$channelSql}",
			$params
		);

		$allRow = $allOrders[0] ?? [];
		$totalOrders = (int) ($allRow['total_orders'] ?? 0);

		$issues = DB::execute(
			"SELECT
				SUM(CASE WHEN o.status = ? THEN 1 ELSE 0 END) AS cancelled_count,
				SUM(CASE WHEN o.status = ? THEN 1 ELSE 0 END) AS returned_count,
				SUM(CASE WHEN o.status = ? THEN 1 ELSE 0 END) AS return_pending_count,
				COALESCE(SUM(CASE WHEN o.status = ? THEN o.total ELSE 0 END), 0) AS cancelled_revenue,
				COALESCE(SUM(CASE WHEN o.status = ? THEN o.total ELSE 0 END), 0) AS returned_revenue,
				COALESCE(SUM(CASE WHEN o.status = ? THEN o.total ELSE 0 END), 0) AS return_pending_revenue
			 FROM orders o
			 WHERE o.date_add BETWEEN ? AND ?
			   {$channelSql}",
			array_merge(
				[$cancelled, $returned, $returnPending, $cancelled, $returned, $returnPending],
				$params
			)
		);

		$issueRow = $issues[0] ?? [];
		$cancelledCount = (int) ($issueRow['cancelled_count'] ?? 0);
		$returnedCount = (int) ($issueRow['returned_count'] ?? 0);
		$returnPendingCount = (int) ($issueRow['return_pending_count'] ?? 0);

		$byStatus = DB::execute(
			"SELECT o.status, COUNT(*) AS cnt, COALESCE(SUM(o.total), 0) AS revenue
			 FROM orders o
			 WHERE o.status IN (?, ?, ?)
			   AND o.date_add BETWEEN ? AND ?
			   {$channelSql}
			 GROUP BY o.status
			 ORDER BY cnt DESC",
			array_merge([$cancelled, $returned, $returnPending], $params)
		) ?: [];

		$statusLabels = class_exists('Order') ? Order::getStatusOptions() : [];

		foreach ($byStatus as &$row) {
			$code = (int) ($row['status'] ?? 0);
			$row['status_label'] = $statusLabels[$code] ?? ('Durum ' . $code);
		}
		unset($row);

		$topProducts = DB::execute(
			"SELECT od.id_product, od.product_name,
				SUM(od.qty) AS qty,
				COALESCE(SUM(od.total), 0) AS revenue,
				SUM(CASE WHEN o.status = ? THEN od.qty ELSE 0 END) AS cancelled_qty,
				SUM(CASE WHEN o.status IN (?, ?) THEN od.qty ELSE 0 END) AS return_qty
			 FROM order_detail od
			 INNER JOIN orders o ON o.id_order = od.id_order
			 WHERE o.status IN (?, ?, ?)
			   AND o.date_add BETWEEN ? AND ?
			   {$channelSql}
			 GROUP BY od.id_product, od.product_name
			 ORDER BY qty DESC
			 LIMIT 15",
			array_merge(
				[$cancelled, $returned, $returnPending, $cancelled, $returned, $returnPending],
				$params
			)
		) ?: [];

		$byPayment = DB::execute(
			"SELECT o.payment_method, COUNT(*) AS cnt, COALESCE(SUM(o.total), 0) AS revenue
			 FROM orders o
			 WHERE o.status IN (?, ?, ?)
			   AND o.date_add BETWEEN ? AND ?
			   {$channelSql}
			 GROUP BY o.payment_method
			 ORDER BY cnt DESC
			 LIMIT 10",
			array_merge([$cancelled, $returned, $returnPending], $params)
		) ?: [];

		$daily = DB::execute(
			"SELECT DATE(o.date_add) AS day,
				SUM(CASE WHEN o.status = ? THEN 1 ELSE 0 END) AS cancelled,
				SUM(CASE WHEN o.status IN (?, ?) THEN 1 ELSE 0 END) AS returns
			 FROM orders o
			 WHERE o.date_add BETWEEN ? AND ?
			   {$channelSql}
			 GROUP BY DATE(o.date_add)
			 HAVING cancelled > 0 OR returns > 0
			 ORDER BY day ASC",
			array_merge([$cancelled, $returned, $returnPending], $params)
		) ?: [];

		$returnRequests = self::collectReturnRequestSamples($dateFrom, $dateTo);

		$lostRevenue = round(
			(float) ($issueRow['cancelled_revenue'] ?? 0)
			+ (float) ($issueRow['returned_revenue'] ?? 0)
			+ (float) ($issueRow['return_pending_revenue'] ?? 0),
			2
		);

		return [
			'channel' => $channel,
			'channel_label' => self::marketplaceOptions()[$channel] ?? $channel,
			'date_from' => $dateFrom,
			'date_to' => $dateTo,
			'totals' => [
				'total_orders' => $totalOrders,
				'cancelled_count' => $cancelledCount,
				'returned_count' => $returnedCount,
				'return_pending_count' => $returnPendingCount,
				'cancel_rate_percent' => $totalOrders > 0 ? round(($cancelledCount / $totalOrders) * 100, 1) : 0,
				'return_rate_percent' => $totalOrders > 0 ? round((($returnedCount + $returnPendingCount) / $totalOrders) * 100, 1) : 0,
				'lost_revenue' => $lostRevenue,
				'cancelled_revenue' => round((float) ($issueRow['cancelled_revenue'] ?? 0), 2),
				'returned_revenue' => round((float) ($issueRow['returned_revenue'] ?? 0), 2),
			],
			'by_status' => $byStatus,
			'by_payment' => $byPayment,
			'top_products' => $topProducts,
			'daily' => $daily,
			'return_requests' => $returnRequests,
		];
	}

	/** @return array<string, mixed> */
	private static function collectTrendyolCancelReturnData(string $dateFrom, string $dateTo): array
	{
		if (!self::hasTrendyolTable()) {
			return [
				'channel' => 'trendyol',
				'date_from' => $dateFrom,
				'date_to' => $dateTo,
				'totals' => ['total_orders' => 0],
				'by_status' => [],
				'top_products' => [],
				'daily' => [],
				'return_requests' => [],
			];
		}

		$params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];

		$allOrders = DB::execute(
			"SELECT COUNT(*) AS total_orders, COALESCE(SUM(total_price), 0) AS total_revenue
			 FROM trendyol_orders
			 WHERE order_date BETWEEN ? AND ?",
			$params
		);

		$totalOrders = (int) (($allOrders[0]['total_orders'] ?? 0));

		$byStatus = DB::execute(
			"SELECT status, COUNT(*) AS cnt, COALESCE(SUM(total_price), 0) AS revenue
			 FROM trendyol_orders
			 WHERE order_date BETWEEN ? AND ?
			   AND (LOWER(status) LIKE '%cancel%' OR LOWER(status) LIKE '%return%' OR LOWER(status) LIKE '%iade%')
			 GROUP BY status
			 ORDER BY cnt DESC",
			$params
		) ?: [];

		$issueCount = 0;
		$lostRevenue = 0.0;

		foreach ($byStatus as $row) {
			$issueCount += (int) ($row['cnt'] ?? 0);
			$lostRevenue += (float) ($row['revenue'] ?? 0);
		}

		return [
			'channel' => 'trendyol',
			'channel_label' => 'Trendyol',
			'date_from' => $dateFrom,
			'date_to' => $dateTo,
			'totals' => [
				'total_orders' => $totalOrders,
				'cancelled_count' => $issueCount,
				'returned_count' => 0,
				'return_pending_count' => 0,
				'cancel_rate_percent' => $totalOrders > 0 ? round(($issueCount / $totalOrders) * 100, 1) : 0,
				'return_rate_percent' => 0,
				'lost_revenue' => round($lostRevenue, 2),
			],
			'by_status' => $byStatus,
			'by_payment' => [],
			'top_products' => [],
			'daily' => [],
			'return_requests' => [],
			'note' => 'Trendyol iptal/iade verileri status alanından filtrelenir.',
		];
	}

	/** @return array<string, mixed> */
	private static function collectReturnRequestSamples(string $dateFrom, string $dateTo): array
	{
		$row = DB::execute("SHOW TABLES LIKE 'return_requests'");

		if (empty($row)) {
			return ['available' => false, 'count' => 0, 'samples' => []];
		}

		$params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];
		$count = (int) DB::getValue(
			'SELECT COUNT(*) FROM return_requests WHERE date_add BETWEEN ? AND ?',
			$params
		);

		$samples = DB::execute(
			"SELECT rr.id_return, rr.id_order, rr.status, rr.customer_message, rr.date_add, o.reference
			 FROM return_requests rr
			 LEFT JOIN orders o ON o.id_order = rr.id_order
			 WHERE rr.date_add BETWEEN ? AND ?
			 ORDER BY rr.date_add DESC
			 LIMIT 12",
			$params
		) ?: [];

		foreach ($samples as &$sample) {
			$msg = trim(strip_tags((string) ($sample['customer_message'] ?? '')));
			$sample['customer_message'] = mb_strlen($msg) > 200 ? mb_substr($msg, 0, 200) . '…' : $msg;
		}
		unset($sample);

		return [
			'available' => true,
			'count' => $count,
			'samples' => $samples,
		];
	}

	private static function buildChannelSql(string $channel): string
	{
		if ($channel === 'store') {
			return " AND o.payment_method NOT LIKE 'pos_%' ";
		}

		if ($channel === 'pos') {
			return " AND o.payment_method LIKE 'pos_%' ";
		}

		return '';
	}

	private static function normalizeDate(string $value, string $fallbackModifier): string
	{
		$value = trim($value);

		if ($value !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
			return $value;
		}

		return date('Y-m-d', strtotime($fallbackModifier));
	}
}
