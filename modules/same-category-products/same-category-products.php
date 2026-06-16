<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

class SameCategoryProductsModule extends ModuleBase
{
	public string $name = 'same-category-products';
	public string $title = 'Aynı Kategorideki Ürünler';
	public string $version = '1.0.0';
	public string $description = 'Ürün detay sayfasında aynı kategorideki diğer ürünleri gösterir';
	public string $author = 'FShop';

	public array $displayHooks = [
		'product' => 'Ürün detay sayfası',
	];

	public array $defaultDisplayHooks = ['product'];

	public function install(): bool
	{
		if (Settings::get('SAME_CATEGORY_TITLE') === '') {
			Settings::set('SAME_CATEGORY_TITLE', 'Aynı kategorideki ürünler');
		}

		if (Settings::get('SAME_CATEGORY_LIMIT') === '') {
			Settings::set('SAME_CATEGORY_LIMIT', '8');
		}

		if (Settings::get('SAME_CATEGORY_SORT') === '') {
			Settings::set('SAME_CATEGORY_SORT', 'newest');
		}

		return true;
	}

	public function uninstall(): bool
	{
		return true;
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';

		if (Tools::isSubmit('saveSameCategoryProducts')) {
			$postToken = (string) Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {

				$title = trim((string) Tools::getValue('title'));
				$limit = (int) Tools::getValue('limit');
				$sort = trim((string) Tools::getValue('sort'));

				if ($title === '') {
					$title = 'Aynı kategorideki ürünler';
				}

				if ($limit < 1) {
					$limit = 8;
				}

				Settings::set('SAME_CATEGORY_TITLE', $title);
				Settings::set('SAME_CATEGORY_LIMIT', (string) $limit);
				Settings::set('SAME_CATEGORY_SORT', $sort);

				$flash = 'Ayarlar kaydedildi';
			}
		}

		$smarty->assign([
			'title' => Settings::get('SAME_CATEGORY_TITLE'),
			'limit' => Settings::get('SAME_CATEGORY_LIMIT'),
			'sort' => Settings::get('SAME_CATEGORY_SORT'),
			'flash' => $flash,
		]);
	}

	private function getProductListTpl(): string
	{
		$theme = Settings::get('THEME') ?: 'default';

		return $theme . '/productList.tpl';
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'product') {
			return null;
		}

		$idProduct = (int) ($context['id_product'] ?? 0);

		if ($idProduct <= 0) {
			return null;
		}

		$product = Product::getById($idProduct);

		if (!$product || empty($product['id_category'])) {
			return null;
		}

		$limit = max(1, (int) Settings::get('SAME_CATEGORY_LIMIT'));
		$sort = (string) Settings::get('SAME_CATEGORY_SORT');
		$allowedSorts = ['newest', 'price_asc', 'price_desc', 'name_asc', 'discount'];

		if (!in_array($sort, $allowedSorts, true)) {
			$sort = 'newest';
		}

		$products = Product::getActiveList(
			(int) $product['id_category'],
			$limit + 1,
			0,
			$sort
		);

		$products = array_values(array_filter(
			$products,
			static function ($row) use ($idProduct) {
				return (int) ($row['id_product'] ?? 0) !== $idProduct;
			}
		));

		$products = array_slice($products, 0, $limit);

		if (empty($products)) {
			return null;
		}

		$html = $this->renderFrontTemplate('product', [
			'title' => Settings::get('SAME_CATEGORY_TITLE') ?: 'Aynı kategorideki ürünler',
			'products' => $products,
			'productListTpl' => $this->getProductListTpl(),
		]);

		return $html !== '' ? $html : null;
	}
}