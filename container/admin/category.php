<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	$id = (int) Tools::getValue('id');
	$category = $id > 0 ? Category::getByIdAdmin($id) : null;
	$flash = '';
	$isNew = $id <= 0;

	if (!$isNew && !$category) {
		http_response_code(404);
		AdminPage::add('404', 'Category not found');
		return;
	}

	if (Tools::isSubmit('saveCategory')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
		} else {
			$result = Category::save($_POST, $id);
			$flash = $result['message'];

			if ($result['success']) {
				header('Location: ' . Admin::url('category?id=' . (int) $result['id'] . '&saved=1'));
				exit;
			}
		}
	}

	if (Tools::getValue('saved')) {
		$flash = 'Kategori kaydedildi';
	}

	$form = $category ?: [
		'category_name' => '',
		'category_link' => '',
		'meta_title' => '',
		'meta_description' => '',
		'id_parent' => 0,
		'active' => 1,
		'show_on_home' => 0,
		'home_position' => 0,
	];

	$langRows = $isNew ? [] : Category::getLangRows($id);
	$langForms = [];

	foreach (Lang::getAvailable() as $langCode) {
		$row = $langRows[$langCode] ?? [];

		if ($row === [] && $category) {
			$row = [
				'category_name' => (string) ($category['category_name'] ?? ''),
				'category_link' => (string) ($category['category_link'] ?? ''),
				'meta_title' => (string) ($category['meta_title'] ?? ''),
				'meta_description' => (string) ($category['meta_description'] ?? ''),
			];
		}

		$langForms[$langCode] = [
			'code' => $langCode,
			'label' => Lang::label($langCode),
			'category_name' => (string) ($row['category_name'] ?? ''),
			'category_link' => (string) ($row['category_link'] ?? ''),
			'meta_title' => (string) ($row['meta_title'] ?? ''),
			'meta_description' => (string) ($row['meta_description'] ?? ''),
		];
	}

	$smarty->assign([
		'category' => $form,
		'categoryLangForms' => $langForms,
		'shopLanguages' => Lang::getAvailable(),
		'idCategory' => $id,
		'isNew' => $isNew,
		'flash' => $flash,
		'parentOptions' => Category::getParentOptions($id),
	]);

	AdminPage::add('category', $isNew ? 'New category' : 'Edit category');
