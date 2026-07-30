<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	$id 		= (int) Tools::getValue('id');
	$product 	= $id > 0 ? Product::getByIdAdmin($id) : null;
	$flash 		= '';
	$flashType 	= 'success';
	$pLink 		= '';
	$isNew 		= $id <= 0;

	if (!$isNew && !$product) {
		http_response_code(404);
		AdminPage::add('404', 'Product not found');
		return;
	}

	if (Tools::isSubmit('saveProduct')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = Product::save($_POST, $id);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';

			if ($result['success']) {
				header('Location: ' . Admin::url('product?id=' . (int) $result['id'] . '&saved=1'));
				exit;
			}
		}
	}

	if (Tools::isSubmit('uploadImage') && $id > 0) {
		$postToken = (string) Tools::getValue('token');
		$isAjax = Tools::getValue('ajax') === '1'
			|| (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

		if (!hash_equals($adminToken, $postToken)) {
			if ($isAjax) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(['success' => false, 'message' => adminT('Invalid request')]);
				exit;
			}

			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$files = [];

			if (!empty($_FILES['images']) && is_array($_FILES['images']['name'] ?? null)) {
				$count = count($_FILES['images']['name']);

				for ($i = 0; $i < $count; $i++) {
					$files[] = [
						'name' => $_FILES['images']['name'][$i] ?? '',
						'type' => $_FILES['images']['type'][$i] ?? '',
						'tmp_name' => $_FILES['images']['tmp_name'][$i] ?? '',
						'error' => $_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
						'size' => $_FILES['images']['size'][$i] ?? 0,
					];
				}
			} elseif (!empty($_FILES['image'])) {
				$files[] = $_FILES['image'];
			}

			$uploaded = [];
			$errors = [];

			foreach ($files as $file) {
				if (empty($file['tmp_name'])) {
					continue;
				}

				$result = Product::uploadImage($id, $file);

				if (!empty($result['success'])) {
					$uploaded[] = [
						'id_image' => (int) ($result['id_image'] ?? $result['id'] ?? 0),
						'url' => (string) ($result['url'] ?? ''),
						'cover' => (int) ($result['cover'] ?? 0),
					];
				} else {
					$errors[] = (string) ($result['message'] ?? 'Yükleme hatası');
				}
			}

			if ($isAjax) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode([
					'success' => $uploaded !== [],
					'message' => $uploaded !== []
						? count($uploaded) . ' görsel yüklendi'
						: ($errors[0] ?? 'Görsel yüklenemedi'),
					'images' => Product::getImages($id),
					'uploaded' => $uploaded,
					'errors' => $errors,
				]);
				exit;
			}

			$flash = $uploaded !== []
				? count($uploaded) . ' görsel yüklendi'
				: ($errors[0] ?? 'Görsel yüklenemedi');
			$flashType = $uploaded !== [] ? 'success' : 'danger';

			if ($uploaded !== []) {
				header('Location: ' . Admin::url('product?id=' . $id . '&saved=1'));
				exit;
			}
		}
	}

	if (Tools::isSubmit('setCover') && $id > 0) {
		$postToken = (string) Tools::getValue('token');
		$isAjax = Tools::getValue('ajax') === '1'
			|| (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

		if (hash_equals($adminToken, $postToken)) {
			$result = Product::setCover((int) Tools::getValue('id_image'));

			if ($isAjax) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode([
					'success' => !empty($result['success']),
					'message' => (string) ($result['message'] ?? ''),
					'images' => Product::getImages($id),
				]);
				exit;
			}

			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';
			$product = Product::getByIdAdmin($id);
		} elseif ($isAjax) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(['success' => false, 'message' => adminT('Invalid request')]);
			exit;
		}
	}

	if (Tools::isSubmit('deleteImage') && $id > 0) {
		$postToken = (string) Tools::getValue('token');
		$isAjax = Tools::getValue('ajax') === '1'
			|| (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

		if (hash_equals($adminToken, $postToken)) {
			$result = Product::deleteImage((int) Tools::getValue('id_image'));

			if ($isAjax) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode([
					'success' => !empty($result['success']),
					'message' => (string) ($result['message'] ?? ''),
					'images' => Product::getImages($id),
				]);
				exit;
			}

			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';
			$product = Product::getByIdAdmin($id);
		} elseif ($isAjax) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(['success' => false, 'message' => adminT('Invalid request')]);
			exit;
		}
	}

	if (Tools::isSubmit('quickAddCategory')) {
		$postToken = (string) Tools::getValue('token');
		$isAjax = Tools::getValue('ajax') === '1'
			|| (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

		if (!$isAjax) {
			http_response_code(400);
			exit;
		}

		if (!hash_equals($adminToken, $postToken)) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(['success' => false, 'message' => adminT('Invalid request')]);
			exit;
		}

		$result = Category::save([
			'category_name' => trim((string) Tools::getValue('category_name')),
			'id_parent' => (int) Tools::getValue('id_parent'),
			'active' => 1,
		], 0);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'success' => !empty($result['success']),
			'message' => (string) ($result['message'] ?? ''),
			'id' => (int) ($result['id'] ?? 0),
			'categoryOptions' => Category::getProductSelectOptions(),
		]);
		exit;
	}

	if (Tools::isSubmit('quickAddBrand')) {
		$postToken = (string) Tools::getValue('token');
		$isAjax = Tools::getValue('ajax') === '1'
			|| (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

		if (!$isAjax) {
			http_response_code(400);
			exit;
		}

		if (!hash_equals($adminToken, $postToken)) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(['success' => false, 'message' => adminT('Invalid request')]);
			exit;
		}

		$result = Brand::save([
			'brand_name' => trim((string) Tools::getValue('brand_name')),
			'active' => 1,
		], 0);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'success' => !empty($result['success']),
			'message' => (string) ($result['message'] ?? ''),
			'id' => (int) ($result['id'] ?? 0),
			'brandOptions' => Brand::getOptions(),
		]);
		exit;
	}

	if (Tools::isSubmit('deleteLicense') && $id > 0) {
		$postToken = (string) Tools::getValue('token');
		if (hash_equals($adminToken, $postToken)) {
			$idLicense = (int) Tools::getValue('id_license');
			$success = VirtualProduct::deleteLicenseKey($idLicense);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(['success' => $success]);
			exit;
		}
	}

	if (Tools::isSubmit('updateLicense') && $id > 0) {
		$postToken = (string) Tools::getValue('token');
		if (hash_equals($adminToken, $postToken)) {
			$idLicense = (int) Tools::getValue('id_license');
			$newKey = (string) Tools::getValue('license_key');
			$success = VirtualProduct::updateLicenseKey($idLicense, $newKey);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(['success' => $success]);
			exit;
		}
	}

	if (Tools::isSubmit('uploadVirtualFile') && $id > 0) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = VirtualProduct::uploadFile($id, $_FILES['virtual_file'] ?? []);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';

			if ($result['success']) {
				header('Location: ' . Admin::url('product?id=' . $id . '&saved=1'));
				exit;
			}
		}
	}

	if (Tools::isSubmit('deleteVirtualFile') && $id > 0) {
		$postToken = (string) Tools::getValue('token');

		if (hash_equals($adminToken, $postToken)) {
			$result = VirtualProduct::deleteFile($id);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';
			$product = Product::getByIdAdmin($id);
		}
	}

	if (!$isNew && $product) {
		$pLink = Product::getLink(['id_product' => $id, 'category_link' => $product['category_link'] ?? '', 'product_link' => $product['product_link'] ?? '']);
	}

	if (Tools::getValue('saved')) {
		$flash 		= 'Kayıt güncellendi';
		$product 	= Product::getByIdAdmin($id);
	}

	$form = $product ?: [
		'product_name' 		=> '',
		'product_link' 		=> '',
		'short_description' => '',
		'meta_title' => '',
		'meta_description' => '',
		'description' 		=> '',
		'id_category' 		=> 0,
		'id_brand' 			=> 0,
		'cost' 				=> '0.00',
		'price' 			=> '0.00',
		'doviz' 			=> 'try',
		'doviz_price' 		=> '0.00',
		'doviz_old_price'	=> '0.00',
		'old_price' 		=> '0.00',
		'vat' 				=> (string) Tax::getDefaultRate(),
		'stock' 			=> 0,
		'cargo_day' 		=> 0,
		'label' 			=> '',
		'product_video' 	=> '',
		'active' 			=> 1,
		'stock_code' 		=> '',
		'barcode' 			=> '',
		'desi' 				=> 1,
		'product_type' 		=> 'physical',
		'virtual_kind' 		=> '',
		'virtual_text' 		=> '',
		'virtual_file' 		=> '',
		'virtual_file_name' => '',
		'images' 			=> [],
	];

	$licenseStats = ['available' => 0, 'used' => 0];
	$allLicenses = [];

	if (!$isNew && $product && VirtualProduct::isVirtualProduct($product) && VirtualProduct::getKind($product) === 'license') {
		$licenseStats = [
			'available' => VirtualProduct::countAvailableLicenses($id),
			'used' => VirtualProduct::countUsedLicenses($id),
		];
		// Sadece müsait (satılmamış) lisansları listeleyelim, böylece sayfa çok uzun olmaz
		$allLicenses = VirtualProduct::getAvailableLicenses($id);
	}

	$langRows = $isNew ? [] : Product::getLangRows($id);
	$langForms = [];

	foreach (Lang::getAvailable() as $langCode) {
		$row = $langRows[$langCode] ?? [];

		if ($row === [] && $product) {
			$row = [
				'product_name' => (string) ($product['product_name'] ?? ''),
				'product_link' => (string) ($product['product_link'] ?? ''),
				'short_description' => (string) ($product['short_description'] ?? ''),
				'description' => (string) ($product['description'] ?? ''),
				'meta_title' => (string) ($product['meta_title'] ?? ''),
				'meta_description' => (string) ($product['meta_description'] ?? ''),
			];
		}

		$langForms[$langCode] = [
			'code' => $langCode,
			'label' => Lang::label($langCode),
			'product_name' => (string) ($row['product_name'] ?? ''),
			'product_link' => (string) ($row['product_link'] ?? ''),
			'short_description' => (string) ($row['short_description'] ?? ''),
			'description' => (string) ($row['description'] ?? ''),
			'meta_title' => (string) ($row['meta_title'] ?? ''),
			'meta_description' => (string) ($row['meta_description'] ?? ''),
		];
	}

	$variationRows = [];
	$hasVariations = false;
	$optionRows = [];

	if (!$isNew) {
		$rawVariations = ProductVariation::getByProduct($id);
		$hasVariations = $rawVariations !== [];
		$variationRows = array_map([ProductVariation::class, 'formatFormRow'], $rawVariations);
		$optionRows = array_map([ProductOption::class, 'formatFormRow'], ProductOption::getByProduct($id));
	}

	$smarty->assign([
		'product' 			=> $form,
		'productLangForms' 	=> $langForms,
		'variationRows'     => $variationRows,
		'hasVariations'     => $hasVariations,
		'optionRows'        => $optionRows,
		'shopLanguages' 	=> Lang::getAvailable(),
		'idProduct' 		=> $id,
		'isNew' 			=> $isNew,
		'flash' 			=> $flash,
		'flashType' 		=> $flashType,
		'pLink' 			=> $pLink,
		'categoryOptions' 	=> Category::getProductSelectOptions(),
		'brandOptions' 		=> Brand::getOptions(),
		'adminUseEditor' 	=> true,
		'licenseStats' 		=> $licenseStats,
		'allLicenses'       => $allLicenses,
		'productLogs' => (!$isNew && $id > 0) ? ProductLog::getForProduct($id, 150) : [],
		'adminHooks' => [
			'admin_product_button' => Module::renderDisplayHook('admin_product_button', [
				'id_product' => $id,
				'product' => $form,
				'is_new' => $isNew,
			]),
		],
		'shopCurrencyLabel' => Currency::label(),
		'taxOptions' => Tax::getProductOptions((float) ($form['vat'] ?? Tax::getDefaultRate())),
	]);

	AdminPage::add('product', $isNew ? 'New Product' : 'Edit product');
