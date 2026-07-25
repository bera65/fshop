<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}
	$sonuc = '';
	$sonucType = 'success';
	if (Tools::isSubmit('deleteProduct'))
	{
		$formToken = md5(Tools::getValue('deleteProduct'));
		if (md5($adminToken) == $formToken)
		{
			$idProduct 	= (int)Tools::getValue('idProduct');
			$isHave		= DB::getRow('products', 'id_product = '.(int)$idProduct.'', 'product_name');
			if ($isHave)
			{
				DB::execute('DELETE FROM products WHERE id_product = '.(int)$idProduct.' LIMIT 1');
				$sonuc = $isHave.' ürünü başarıyla silindi';
			}
		}
	}

	if (Tools::isSubmit('bulkProductAction'))
	{
		$formToken = md5(Tools::getValue('bulkProductToken'));
		if (md5($adminToken) === $formToken)
		{
			$action = trim((string) Tools::getValue('bulkProductAction'));
			$productIds = Tools::getValue('productIds');
			$ids = is_array($productIds) ? $productIds : [];

			if ($action === 'activate') {
				$result = Product::bulkSetActive($ids, 1);
			} elseif ($action === 'deactivate') {
				$result = Product::bulkSetActive($ids, 0);
			} elseif ($action === 'delete') {
				$result = Product::bulkDelete($ids);
			} else {
				$result = ['success' => false, 'message' => 'Geçersiz toplu işlem'];
			}

			$sonuc = (string) ($result['message'] ?? '');
			$sonucType = !empty($result['success']) ? 'success' : 'danger';
		}
	}


	$currentPage 	= max(1, (int) Tools::getValue('page'));
	$query 			= trim((string) Tools::getValue('q'));
	$idCategory 	= (int) Tools::getValue('category');
	$idBrand 		= (int) Tools::getValue('brand');
	$activeFilter 	= Tools::getIsset('active') ? (int) Tools::getValue('active') : -1;
	$perPage 		= 30;

	$total = Product::countAdmin($query, $idCategory, $idBrand, $activeFilter);
	$queryParams = array_filter([
		'q' 			=> $query,
		'category' 		=> $idCategory > 0 ? $idCategory : null,
		'brand' 		=> $idBrand > 0 ? $idBrand : null,
		'active' 		=> $activeFilter >= 0 ? $activeFilter : null,
	], static fn($v) 	=> $v !== null && $v !== '');

	$pagination = Pagination::build($total, $currentPage, $perPage, Admin::url('products'), $queryParams);
	$products 	= Product::getAdminList($query, $idCategory, $idBrand, $activeFilter, $perPage, $pagination['offset']);

	$flash = class_exists('ExportExcelService', false) ? ExportExcelService::consumeFlash() : null;
	if ($flash && $flash['message'] !== '') {
		$sonuc = $flash['message'];
		$sonucType = $flash['type'] === 'danger' ? 'danger' : 'success';
	}

	$smarty->assign([
		'products' 			=> $products,
		'pagination' 		=> $pagination,
		'searchQuery' 		=> $query,
		'categoryFilter' 	=> $idCategory,
		'brandFilter' 		=> $idBrand,
		'activeFilter' 		=> $activeFilter,
		'categoryOptions' 	=> Category::getMenuList(),
		'brandOptions' 		=> Brand::getOptions(),
		'sonuc' 			=> $sonuc,
		'sonucType' 		=> $sonucType,
	]);

	AdminPage::add('products', 'Products');
