<?php

class ProductTabsService
{
	public static function getTabsForProduct(int $idProduct): array
	{
		if ($idProduct <= 0) {
			return [];
		}

		$rows = DB::execute(
			'SELECT t.*
			 FROM product_tabs t
			 WHERE t.active = 1
			   AND (
					t.scope = \'all\'
					OR EXISTS (
						SELECT 1 FROM product_tab_products tp
						WHERE tp.id_tab = t.id_tab AND tp.id_product = ?
					)
			   )
			 ORDER BY t.position ASC, t.id_tab ASC',
			[$idProduct]
		) ?: [];

		return array_map(static function (array $row): array {
			$row['slug'] = 'pct-' . (int) ($row['id_tab'] ?? 0);

			return $row;
		}, $rows);
	}

	/** @return array<int, array<string, mixed>> */
	public static function getAdminList(): array
	{
		$rows = DB::execute(
			'SELECT * FROM product_tabs ORDER BY position ASC, id_tab ASC'
		) ?: [];

		return array_map(static function (array $row): array {
			$row['product_count'] = self::countProducts((int) ($row['id_tab'] ?? 0));
			$row['scope_label'] = ($row['scope'] ?? '') === 'selected' ? 'Seçili ürünler' : 'Tüm ürünler';

			return $row;
		}, $rows);
	}

	public static function getById(int $idTab): ?array
	{
		$row = DB::getRowSafe('product_tabs', 'id_tab = ?', [$idTab]);

		if (!$row) {
			return null;
		}

		$row['product_ids'] = self::getProductIds($idTab);

		return $row;
	}

	/** @return int[] */
	public static function getProductIds(int $idTab): array
	{
		if ($idTab <= 0) {
			return [];
		}

		$rows = DB::execute(
			'SELECT id_product FROM product_tab_products WHERE id_tab = ? ORDER BY id_product ASC',
			[$idTab]
		) ?: [];

		return array_map(static fn(array $row): int => (int) ($row['id_product'] ?? 0), $rows);
	}

	public static function countProducts(int $idTab): int
	{
		if ($idTab <= 0) {
			return 0;
		}

		return (int) DB::getValue(
			'SELECT COUNT(*) FROM product_tab_products WHERE id_tab = ?',
			[$idTab]
		);
	}

	/** @param array<string, mixed> $data */
	public static function save(array $data): array
	{
		$idTab = (int) ($data['id_tab'] ?? 0);
		$isUpdate = $idTab > 0;
		$title = trim((string) ($data['title'] ?? ''));
		$content = Security::sanitizeHtml(trim((string) ($data['content'] ?? '')));
		$scope = (string) ($data['scope'] ?? 'all');
		$scope = $scope === 'selected' ? 'selected' : 'all';
		$position = (int) ($data['position'] ?? 0);
		$active = !empty($data['active']) ? 1 : 0;
		$productIds = array_values(array_unique(array_filter(array_map('intval', (array) ($data['product_ids'] ?? [])))));

		if ($title === '') {
			return self::fail('Sekme başlığı zorunludur');
		}

		if ($content === '') {
			return self::fail('Sekme içeriği boş olamaz');
		}

		if ($scope === 'selected' && $productIds === []) {
			return self::fail('Seçili ürün modunda en az bir ürün seçmelisiniz');
		}

		$now = date('Y-m-d H:i:s');
		$row = [
			'title' => mb_substr($title, 0, 128),
			'content' => $content,
			'scope' => $scope,
			'position' => $position,
			'active' => $active,
			'date_upd' => $now,
		];

		if ($idTab > 0 && !DB::getRowSafe('product_tabs', 'id_tab = ?', [$idTab])) {
			return self::fail('Sekme bulunamadı');
		}

		if ($idTab > 0) {
			$ok = DB::update('product_tabs', $row, 'id_tab = :where_id', ['where_id' => $idTab]);

			if ($ok === false) {
				return self::fail('Sekme güncellenemedi');
			}
		} else {
			$row['date_add'] = $now;
			$newId = DB::insert('product_tabs', $row);

			if (!$newId) {
				return self::fail('Sekme eklenemedi');
			}

			$idTab = (int) $newId;
		}

		self::syncProducts($idTab, $scope, $productIds);

		return [
			'success' => true,
			'message' => $isUpdate ? 'Sekme güncellendi' : 'Sekme eklendi',
			'id_tab' => $idTab,
		];
	}

	/** @param int[] $productIds */
	private static function syncProducts(int $idTab, string $scope, array $productIds): void
	{
		DB::execute('DELETE FROM product_tab_products WHERE id_tab = ?', [$idTab]);

		if ($scope !== 'selected' || $productIds === []) {
			return;
		}

		foreach ($productIds as $idProduct) {
			if ($idProduct <= 0) {
				continue;
			}

			DB::insert('product_tab_products', [
				'id_tab' => $idTab,
				'id_product' => $idProduct,
			]);
		}
	}

	public static function delete(int $idTab): array
	{
		if ($idTab <= 0 || !DB::getRowSafe('product_tabs', 'id_tab = ?', [$idTab])) {
			return self::fail('Sekme bulunamadı');
		}

		DB::execute('DELETE FROM product_tab_products WHERE id_tab = ?', [$idTab]);
		DB::execute('DELETE FROM product_tabs WHERE id_tab = ?', [$idTab]);

		return ['success' => true, 'message' => 'Sekme silindi'];
	}

	public static function toggleActive(int $idTab): array
	{
		$row = DB::getRowSafe('product_tabs', 'id_tab = ?', [$idTab]);

		if (!$row) {
			return self::fail('Sekme bulunamadı');
		}

		$newActive = (int) ($row['active'] ?? 0) === 1 ? 0 : 1;
		DB::update('product_tabs', ['active' => $newActive, 'date_upd' => date('Y-m-d H:i:s')], 'id_tab = :where_id', [
			'where_id' => $idTab,
		]);

		return ['success' => true, 'message' => 'Durum güncellendi'];
	}

	/** @return array<int, array<string, mixed>> */
	public static function getProductOptions(int $limit = 500): array
	{
		return Product::getAdminList('', 0, 0, 1, $limit, 0);
	}

	/** @return array{success: bool, message: string, id_tab?: int} */
	private static function fail(string $message): array
	{
		return ['success' => false, 'message' => $message];
	}
}
