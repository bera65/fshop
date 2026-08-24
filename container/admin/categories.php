<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}
	$sonuc = '';
	if (Tools::isSubmit('deleteCategory'))
	{
		$formToken = md5(Tools::getValue('deleteCategory'));
		if (md5($adminToken) == $formToken)
		{
			$idCategory 	= (int)Tools::getValue('idCategory');
			$isHave		= DB::getRow('categories', 'id_category = '.(int)$idCategory.'', 'category_name');
			if ($isHave)
			{
				DB::execute('DELETE FROM categories WHERE id_category = '.(int)$idCategory.' LIMIT 1');
				$sonuc = $isHave.' kategorisi başarıyla silindi';
			}
		}
	}

	if (Tools::isSubmit('toggleHomeCategory')) {
		$postToken = (string) Tools::getValue('token');

		if (hash_equals((string) $adminToken, $postToken)) {
			$idCategory = (int) Tools::getValue('idCategory');
			$show = (int) Tools::getValue('show_on_home') === 1;

			if (Category::setShowOnHome($idCategory, $show)) {
				$sonuc = $show
					? adminT('Category added to homepage')
					: adminT('Category removed from homepage');
			}
		} else {
			$sonuc = adminT('Invalid request');
		}
	}

	$activeFilter = Tools::getIsset('active') ? (int) Tools::getValue('active') : -1;
	$categories = Category::getAdminList($activeFilter);

	$smarty->assign([
		'categories' 	=> $categories,
		'activeFilter' 	=> $activeFilter,
		'sonuc' 		=> $sonuc,
	]);

	AdminPage::add('categories', 'Categories');
