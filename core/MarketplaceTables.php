<?php

/**
 * Tüm pazaryeri sipariş / soru kayıtları tek tabloda tutulur.
 * platform: trendyol | hepsiburada | n11 | (gelecek platformlar)
 */
class MarketplaceTables
{
	public const ORDERS = 'marketplace_orders';
	public const QUESTIONS = 'marketplace_questions';

	private static bool $ready = false;

	public static function ensureSchema(): void
	{
		if (self::$ready) {
			return;
		}

		self::$ready = true;

		$orders = DB::execute("SHOW TABLES LIKE '" . self::ORDERS . "'");

		if (empty($orders)) {
			DB::execute(
				"CREATE TABLE `" . self::ORDERS . "` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`platform` varchar(32) NOT NULL,
					`order_number` varchar(64) NOT NULL,
					`shipment_package_id` varchar(64) NOT NULL DEFAULT '',
					`status` varchar(64) NOT NULL DEFAULT '',
					`customer_name` varchar(255) NOT NULL DEFAULT '',
					`total_price` decimal(20,2) NOT NULL DEFAULT 0.00,
					`cargo_tracking_number` varchar(128) NOT NULL DEFAULT '',
					`cargo_tracking_link` varchar(512) NOT NULL DEFAULT '',
					`cargo_provider` varchar(128) NOT NULL DEFAULT '',
					`id_product` int(11) NOT NULL DEFAULT 0,
					`lines_json` mediumtext NULL,
					`raw_json` mediumtext NULL,
					`stock_deducted` tinyint(1) NOT NULL DEFAULT 0,
					`order_date` datetime NULL,
					`last_sync_at` datetime NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `platform_order_package` (`platform`, `order_number`, `shipment_package_id`),
					KEY `platform_status` (`platform`, `status`),
					KEY `order_date` (`order_date`),
					KEY `id_product` (`id_product`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		} else {
			$col = DB::execute("SHOW COLUMNS FROM `" . self::ORDERS . "` LIKE 'id_product'");

			if (empty($col)) {
				DB::execute(
					"ALTER TABLE `" . self::ORDERS . "`
					 ADD COLUMN `id_product` int(11) NOT NULL DEFAULT 0 AFTER `cargo_provider`,
					 ADD KEY `id_product` (`id_product`)"
				);
			}

			$linkCol = DB::execute("SHOW COLUMNS FROM `" . self::ORDERS . "` LIKE 'cargo_tracking_link'");

			if (empty($linkCol)) {
				DB::execute(
					"ALTER TABLE `" . self::ORDERS . "`
					 ADD COLUMN `cargo_tracking_link` varchar(512) NOT NULL DEFAULT '' AFTER `cargo_tracking_number`"
				);
			}
		}

		$questions = DB::execute("SHOW TABLES LIKE '" . self::QUESTIONS . "'");

		if (empty($questions)) {
			DB::execute(
				"CREATE TABLE `" . self::QUESTIONS . "` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`platform` varchar(32) NOT NULL,
					`question_id` varchar(64) NOT NULL,
					`product_name` varchar(255) NOT NULL DEFAULT '',
					`barcode` varchar(64) NOT NULL DEFAULT '',
					`id_product` int(11) NOT NULL DEFAULT 0,
					`question_text` text NULL,
					`answer_text` text NULL,
					`status` varchar(64) NOT NULL DEFAULT '',
					`answered` tinyint(1) NOT NULL DEFAULT 0,
					`customer_id` varchar(64) NOT NULL DEFAULT '',
					`raw_json` mediumtext NULL,
					`question_date` datetime NULL,
					`last_sync_at` datetime NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `platform_question` (`platform`, `question_id`),
					KEY `platform_answered` (`platform`, `answered`),
					KEY `id_product` (`id_product`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		} else {
			$col = DB::execute("SHOW COLUMNS FROM `" . self::QUESTIONS . "` LIKE 'id_product'");

			if (empty($col)) {
				DB::execute(
					"ALTER TABLE `" . self::QUESTIONS . "`
					 ADD COLUMN `id_product` int(11) NOT NULL DEFAULT 0 AFTER `barcode`,
					 ADD KEY `id_product` (`id_product`)"
				);
			}
		}

		self::migrateLegacyAndDrop();
		MarketplaceLog::ensureSchema();
	}

	/** Eski platform tablolarından veri taşıyıp düşürür. */
	private static function migrateLegacyAndDrop(): void
	{
		$orderSources = [
			'trendyol' => 'trendyol_orders',
			'hepsiburada' => 'hepsiburada_orders',
			'n11' => 'n11_orders',
		];

		foreach ($orderSources as $platform => $table) {
			$exists = DB::execute("SHOW TABLES LIKE '" . $table . "'");

			if (empty($exists)) {
				continue;
			}

			DB::execute(
				"INSERT IGNORE INTO `" . self::ORDERS . "`
					(`platform`, `order_number`, `shipment_package_id`, `status`, `customer_name`,
					 `total_price`, `cargo_tracking_number`, `cargo_provider`, `id_product`,
					 `lines_json`, `raw_json`, `stock_deducted`, `order_date`, `last_sync_at`)
				 SELECT
					?, `order_number`, `shipment_package_id`, `status`, `customer_name`,
					`total_price`, `cargo_tracking_number`, `cargo_provider`, 0,
					`lines_json`, `raw_json`, `stock_deducted`, `order_date`, `last_sync_at`
				 FROM `{$table}`",
				[$platform]
			);

			DB::execute("DROP TABLE IF EXISTS `{$table}`");
		}

		$questionSources = [
			'trendyol' => 'trendyol_questions',
			'hepsiburada' => 'hepsiburada_questions',
			'n11' => 'n11_questions',
		];

		foreach ($questionSources as $platform => $table) {
			$exists = DB::execute("SHOW TABLES LIKE '" . $table . "'");

			if (empty($exists)) {
				continue;
			}

			DB::execute(
				"INSERT IGNORE INTO `" . self::QUESTIONS . "`
					(`platform`, `question_id`, `product_name`, `barcode`, `id_product`,
					 `question_text`, `answer_text`, `status`, `answered`, `customer_id`,
					 `raw_json`, `question_date`, `last_sync_at`)
				 SELECT
					?, CAST(`question_id` AS CHAR), `product_name`, `barcode`, 0,
					`question_text`, `answer_text`, `status`, `answered`, `customer_id`,
					`raw_json`, `question_date`, `last_sync_at`
				 FROM `{$table}`",
				[$platform]
			);

			DB::execute("DROP TABLE IF EXISTS `{$table}`");
		}
	}

	/** @return array<string, mixed>|null */
	public static function findOrder(string $platform, string $orderNumber, string $packageId = ''): ?array
	{
		self::ensureSchema();
		$orderNumber = trim($orderNumber);
		$packageId = trim($packageId);

		if ($orderNumber === '') {
			return null;
		}

		if ($packageId !== '') {
			$row = DB::getRowSafe(
				self::ORDERS,
				'platform = ? AND order_number = ? AND shipment_package_id = ?',
				[$platform, $orderNumber, $packageId]
			);

			if (is_array($row)) {
				return $row;
			}
		}

		// Paket id değişmiş / boş olsa bile aynı siparişi bul (durum güncellemesi için)
		$rows = DB::execute(
			'SELECT * FROM `' . self::ORDERS . '`
			 WHERE platform = ? AND order_number = ?
			 ORDER BY
				CASE WHEN shipment_package_id = ? THEN 0 ELSE 1 END,
				id DESC
			 LIMIT 1',
			[$platform, $orderNumber, $packageId]
		);

		return (!empty($rows[0]) && is_array($rows[0])) ? $rows[0] : null;
	}

	/**
	 * Varsa durum + kargo (+ satırlar) günceller, yoksa ekler. id döner.
	 *
	 * @param array<string, mixed> $row platform hariç alanlar (+ isteğe bağlı stock_deducted)
	 */
	public static function upsertOrder(string $platform, array $row, bool $fullUpdate = true): int
	{
		self::ensureSchema();

		$orderNumber = trim((string) ($row['order_number'] ?? ''));
		$packageId = trim((string) ($row['shipment_package_id'] ?? ''));

		if ($orderNumber === '') {
			return 0;
		}

		$existing = self::findOrder($platform, $orderNumber, $packageId);
		$now = (string) ($row['last_sync_at'] ?? date('Y-m-d H:i:s'));

		if ($existing) {
			$newStatus = trim((string) ($row['status'] ?? ''));
			$newTrack = trim((string) ($row['cargo_tracking_number'] ?? ''));
			$newTrackLink = trim((string) ($row['cargo_tracking_link'] ?? ''));
			$newProvider = trim((string) ($row['cargo_provider'] ?? ''));

			$update = [
				'status' => $newStatus !== '' ? $newStatus : (string) ($existing['status'] ?? ''),
				'cargo_tracking_number' => $newTrack !== ''
					? $newTrack
					: (string) ($existing['cargo_tracking_number'] ?? ''),
				'cargo_tracking_link' => $newTrackLink !== ''
					? $newTrackLink
					: (string) ($existing['cargo_tracking_link'] ?? ''),
				'cargo_provider' => $newProvider !== ''
					? $newProvider
					: (string) ($existing['cargo_provider'] ?? ''),
				'last_sync_at' => $now,
			];

			// Paket kimliği netleştiyse güncelle (unique çakışması yoksa)
			if ($packageId !== '' && $packageId !== (string) ($existing['shipment_package_id'] ?? '')) {
				$clash = DB::getRowSafe(
					self::ORDERS,
					'platform = ? AND order_number = ? AND shipment_package_id = ? AND id <> ?',
					[$platform, $orderNumber, $packageId, (int) $existing['id']]
				);

				if (!is_array($clash)) {
					$update['shipment_package_id'] = $packageId;
				}
			}

			if ($fullUpdate) {
				$customerName = trim((string) ($row['customer_name'] ?? ''));
				if ($customerName !== '') {
					$update['customer_name'] = $customerName;
				}

				if (array_key_exists('lines_json', $row) && $row['lines_json'] !== null && $row['lines_json'] !== '') {
					$update['lines_json'] = $row['lines_json'];
				}

				if (array_key_exists('raw_json', $row) && $row['raw_json'] !== null && $row['raw_json'] !== '') {
					$update['raw_json'] = $row['raw_json'];
				}

				$idProduct = (int) ($row['id_product'] ?? 0);
				if ($idProduct > 0) {
					$update['id_product'] = $idProduct;
				}

				$orderDate = $row['order_date'] ?? null;
				$update['order_date'] = $orderDate ?: ($existing['order_date'] ?? null);

				$newTotal = (float) ($row['total_price'] ?? 0);
				$oldTotal = (float) ($existing['total_price'] ?? 0);

				if ($newTotal > 1 || $oldTotal <= 0) {
					$update['total_price'] = $newTotal;
				}
			}

			DB::update(self::ORDERS, $update, 'id = :where_id', ['where_id' => (int) $existing['id']]);

			return (int) $existing['id'];
		}

		$insert = [
			'platform' => $platform,
			'order_number' => $orderNumber,
			'shipment_package_id' => $packageId,
			'status' => (string) ($row['status'] ?? ''),
			'customer_name' => (string) ($row['customer_name'] ?? ''),
			'total_price' => (float) ($row['total_price'] ?? 0),
			'cargo_tracking_number' => (string) ($row['cargo_tracking_number'] ?? ''),
			'cargo_tracking_link' => (string) ($row['cargo_tracking_link'] ?? ''),
			'cargo_provider' => (string) ($row['cargo_provider'] ?? ''),
			'id_product' => (int) ($row['id_product'] ?? 0),
			'lines_json' => $row['lines_json'] ?? null,
			'raw_json' => $row['raw_json'] ?? null,
			'stock_deducted' => (int) ($row['stock_deducted'] ?? 0),
			'order_date' => $row['order_date'] ?? null,
			'last_sync_at' => $now,
		];

		$id = DB::insert(self::ORDERS, $insert);

		if ($id) {
			return (int) $id;
		}

		$found = self::findOrder($platform, $orderNumber, $packageId);

		return $found ? (int) $found['id'] : 0;
	}

	/** @param array<string, mixed> $data */
	public static function updateOrderById(int $id, array $data): void
	{
		if ($id <= 0 || $data === []) {
			return;
		}

		self::ensureSchema();
		DB::update(self::ORDERS, $data, 'id = :where_id', ['where_id' => $id]);
	}

	public static function deleteOrder(string $platform, string $orderNumber, string $packageId = ''): int
	{
		self::ensureSchema();
		$platform = trim($platform);
		$orderNumber = trim($orderNumber);
		$packageId = trim($packageId);

		if ($platform === '' || $orderNumber === '') {
			return 0;
		}

		if ($packageId !== '') {
			$exact = self::findOrder($platform, $orderNumber, $packageId);

			if ($exact && (int) ($exact['id'] ?? 0) > 0) {
				DB::execute('DELETE FROM `' . self::ORDERS . '` WHERE id = ?', [(int) $exact['id']]);

				return 1;
			}
		}

		$rows = DB::execute(
			'SELECT id FROM `' . self::ORDERS . '` WHERE platform = ? AND order_number = ?',
			[$platform, $orderNumber]
		) ?: [];

		$n = 0;

		foreach ($rows as $row) {
			$id = (int) ($row['id'] ?? 0);

			if ($id > 0) {
				DB::execute('DELETE FROM `' . self::ORDERS . '` WHERE id = ?', [$id]);
				$n++;
			}
		}

		return $n;
	}

	/**
	 * Trendyol: cargoTrackingLink, N11: cargoTrackingLink, HB: trackingUrl vb.
	 *
	 * @param array<string, mixed> $pkg
	 */
	public static function extractCargoTrackingLink(array $pkg): string
	{
		$keys = [
			'cargoTrackingLink',
			'cargo_tracking_link',
			'trackingUrl',
			'tracking_url',
			'cargoTrackingUrl',
			'trackingLink',
			'cargoUrl',
		];

		foreach ($keys as $key) {
			$url = trim((string) ($pkg[$key] ?? ''));

			if ($url !== '' && preg_match('#^https?://#i', $url)) {
				return mb_substr($url, 0, 512);
			}
		}

		return '';
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function getRecentOrders(?string $platform = null, int $limit = 50): array
	{
		self::ensureSchema();
		$limit = max(1, min(2000, $limit));

		if ($platform !== null && $platform !== '') {
			$rows = DB::execute(
				'SELECT * FROM `' . self::ORDERS . '`
				 WHERE platform = ?
				 ORDER BY COALESCE(order_date, last_sync_at) DESC, id DESC
				 LIMIT ' . (int) $limit,
				[$platform]
			) ?: [];
		} else {
			$rows = DB::execute(
				'SELECT * FROM `' . self::ORDERS . '`
				 ORDER BY COALESCE(order_date, last_sync_at) DESC, id DESC
				 LIMIT ' . (int) $limit
			) ?: [];
		}

		foreach ($rows as &$row) {
			$lines = json_decode((string) ($row['lines_json'] ?? ''), true);
			$row['lines'] = is_array($lines) ? $lines : [];
		}
		unset($row);

		return $rows;
	}

	/** @return array<string, mixed>|null */
	public static function findQuestion(string $platform, string $questionId): ?array
	{
		self::ensureSchema();
		$questionId = trim($questionId);

		if ($questionId === '') {
			return null;
		}

		$row = DB::getRowSafe(
			self::QUESTIONS,
			'platform = ? AND question_id = ?',
			[$platform, $questionId]
		);

		return is_array($row) ? $row : null;
	}

	/**
	 * @param array<string, mixed> $row
	 */
	public static function upsertQuestion(string $platform, array $row): void
	{
		self::ensureSchema();

		$questionId = trim((string) ($row['question_id'] ?? ''));

		if ($questionId === '') {
			return;
		}

		$data = [
			'question_id' => $questionId,
			'product_name' => mb_substr((string) ($row['product_name'] ?? ''), 0, 255),
			'barcode' => (string) ($row['barcode'] ?? ''),
			'id_product' => (int) ($row['id_product'] ?? 0),
			'question_text' => (string) ($row['question_text'] ?? ''),
			'answer_text' => (string) ($row['answer_text'] ?? ''),
			'status' => (string) ($row['status'] ?? ''),
			'answered' => (int) ($row['answered'] ?? 0),
			'customer_id' => (string) ($row['customer_id'] ?? ''),
			'raw_json' => $row['raw_json'] ?? null,
			'question_date' => $row['question_date'] ?? null,
			'last_sync_at' => (string) ($row['last_sync_at'] ?? date('Y-m-d H:i:s')),
		];

		$existing = self::findQuestion($platform, $questionId);

		if ($existing) {
			DB::update(self::QUESTIONS, $data, 'id = :where_id', ['where_id' => (int) $existing['id']]);

			return;
		}

		$data['platform'] = $platform;
		DB::insert(self::QUESTIONS, $data);
	}

	/** @param array<string, mixed> $data */
	public static function updateQuestion(string $platform, string $questionId, array $data): void
	{
		self::ensureSchema();
		$questionId = trim($questionId);

		if ($questionId === '' || $data === []) {
			return;
		}

		DB::update(
			self::QUESTIONS,
			$data,
			'platform = :where_platform AND question_id = :where_qid',
			['where_platform' => $platform, 'where_qid' => $questionId]
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function getRecentQuestions(?string $platform = null, int $limit = 50, bool $unansweredOnly = false): array
	{
		self::ensureSchema();
		$limit = max(1, min(500, $limit));
		$where = [];
		$params = [];

		if ($platform !== null && $platform !== '') {
			$where[] = 'platform = ?';
			$params[] = $platform;
		}

		if ($unansweredOnly) {
			$where[] = 'answered = 0';
		}

		$sql = 'SELECT * FROM `' . self::QUESTIONS . '`';

		if ($where !== []) {
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}

		$sql .= ' ORDER BY COALESCE(question_date, last_sync_at) DESC, id DESC LIMIT ' . (int) $limit;

		return DB::execute($sql, $params) ?: [];
	}
}
