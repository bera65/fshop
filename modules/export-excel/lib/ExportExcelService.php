<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

class ExportExcelService
{
	public static function exportProducts(string $query = '', int $idCategory = 0, int $idBrand = 0, int $activeFilter = -1): void
	{
		self::loadXlsxLibs();

		$total = Product::countAdmin($query, $idCategory, $idBrand, $activeFilter);
		$books = [[
			'<b>Product Name</b>',
			'<b>Barcode</b>',
			'<b>Stock Code</b>',
			'<b>Desi</b>',
			'<b>Price</b>',
			'<b>Old Price</b>',
			'<b>Vat</b>',
			'<b>Stock</b>',
			'<b>short Description</b>',
			'<b>Description</b>',
			'<b>Meta Title</b>',
			'<b>Meta Description</b>',
			'<b>Slug</b>',
			'<b>Category Name</b>',
			'<b>Brand Name</b>',
			'<b>Images</b>',
			'<b>Active</b>',
		]];

		$exportProducts = Product::getAdminList($query, $idCategory, $idBrand, $activeFilter, max(1, $total), 0);
		$exportLang = Lang::getDefault();

		foreach ($exportProducts as $gd) {
			$gd = Lang::applyProductForLang($gd, $exportLang);

			$books[] = [
				$gd['product_name'],
				$gd['barcode'],
				$gd['stock_code'],
				$gd['desi'],
				$gd['price'],
				$gd['old_price'],
				$gd['vat'],
				$gd['stock'],
				SimpleXLSXGen::raw($gd['short_description']),
				SimpleXLSXGen::raw(self::decodeHtmlEntities((string) $gd['description'])),
				$gd['meta_title'],
				$gd['meta_description'],
				$gd['product_link'],
				$gd['category_name'],
				$gd['brand_name'],
				Product::getExportImageUrls((int) $gd['id_product']),
				$gd['active_label'],
			];
		}

		$name = 'product-list.xlsx';
		header('Content-Disposition: attachment; filename="' . $name . '"');
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		SimpleXLSXGen::fromArray($books)->downloadAs($name);
		exit;
	}

	/** @return array{success:bool,message:string} */
	public static function importProductsFromUpload(array $file): array
	{
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
			return ['success' => false, 'message' => adminT('Select an Excel file')];
		}

		$ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

		if ($ext !== 'xlsx') {
			return ['success' => false, 'message' => adminT('Only .xlsx files are allowed')];
		}

		self::loadXlsxLibs();

		$xlsx = SimpleXLSX::parse($file['tmp_name']);

		if (!$xlsx) {
			return ['success' => false, 'message' => 'Excel okunamadı: ' . SimpleXLSX::parseError()];
		}

		return Product::importFromExcel($xlsx->rows());
	}

	public static function exportOrders(int $status = 0, array $filters = []): void
	{
		self::loadXlsxLibs();

		$filters = Order::normalizeAdminFilters($filters);
		$total = Order::countAdmin($status, $filters['date_from'], $filters['date_to'], $filters);
		$rows = Order::enrichAdminRows(
			Order::getAdminList($status, max(1, $total), 0, $filters['date_from'], $filters['date_to'], $filters)
		);

		$books = [[
			'<b>Reference</b>',
			'<b>Customer</b>',
			'<b>Email</b>',
			'<b>Phone</b>',
			'<b>Status</b>',
			'<b>Payment</b>',
			'<b>Subtotal</b>',
			'<b>Shipping</b>',
			'<b>Discount</b>',
			'<b>Total</b>',
			'<b>Date</b>',
			'<b>Note</b>',
		]];

		foreach ($rows as $row) {
			$discount = (float) ($row['coupon_discount'] ?? 0)
				+ (float) ($row['promotion_discount'] ?? 0)
				+ (float) ($row['payment_discount'] ?? 0);

			$books[] = [
				(string) ($row['reference'] ?? ''),
				(string) ($row['customer_name'] ?? ''),
				(string) ($row['customer_email'] ?? ''),
				(string) ($row['customer_phone'] ?? ''),
				(string) ($row['status_label'] ?? ''),
				(string) ($row['payment_label'] ?? ''),
				(float) ($row['subtotal'] ?? 0),
				(float) ($row['shipping'] ?? 0),
				$discount,
				(float) ($row['total'] ?? 0),
				(string) ($row['date_add'] ?? ''),
				(string) ($row['note'] ?? ''),
			];
		}

		$name = 'order-list.xlsx';
		header('Content-Disposition: attachment; filename="' . $name . '"');
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		SimpleXLSXGen::fromArray($books)->downloadAs($name);
		exit;
	}

	public static function setFlash(string $message, string $type = 'success'): void
	{
		$_SESSION['export_excel_flash'] = [
			'message' => $message,
			'type' => $type,
		];
	}

	/** @return array{message:string,type:string}|null */
	public static function consumeFlash(): ?array
	{
		if (empty($_SESSION['export_excel_flash']) || !is_array($_SESSION['export_excel_flash'])) {
			return null;
		}

		$flash = $_SESSION['export_excel_flash'];
		unset($_SESSION['export_excel_flash']);

		return [
			'message' => (string) ($flash['message'] ?? ''),
			'type' => (string) ($flash['type'] ?? 'success'),
		];
	}

	private static function loadXlsxLibs(): void
	{
		$root = dirname(__DIR__, 3);

		if (!class_exists('SimpleXLSX', false)) {
			require_once $root . '/libs/SimpleXLSX.php';
		}

		if (!class_exists('SimpleXLSXGen', false)) {
			require_once $root . '/libs/SimpleGEN.php';
		}
	}

	private static function decodeHtmlEntities(string $string): string
	{
		return html_entity_decode($string, ENT_QUOTES | ENT_XHTML | ENT_HTML5, 'UTF-8');
	}
}
