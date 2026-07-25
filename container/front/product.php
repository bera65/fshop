<?php
	if (!defined('IN_SCRIPT')) {
		header("HTTP/1.0 404 Not Found");
		header('Location: ../404');
		exit;
	}

	$js = 'product.js';
	$css = 'product.css';
	$idProduct = (int) Tools::getValue('id');
	$product = Product::getById($idProduct);

	if (!$product) {
		http_response_code(404);
		$skipPageRender = true;
		$page->add('404', translate('Page Not Found'));
		return;
	}

	$images = Product::getImages($idProduct);

	if (!$images) {
		$images = [[
			'id_image' => 0,
			'url' => $product['image_url'],
			'cover' => 1,
		]];
	}

	$pageTitle = trim((string) ($product['meta_title'] ?? ''));
	if ($pageTitle === '') {
		$pageTitle = $product['product_name'];
	}

	$pageDesc = trim((string) ($product['meta_description'] ?? ''));
	if ($pageDesc === '') {
		$metaSource = trim((string) ($product['short_description'] ?? ''));
		if ($metaSource === '') {
			$metaSource = strip_tags((string) $product['description']);
		}
		$pageDesc = Tools::strlen($metaSource) > 160
			? mb_substr($metaSource, 0, 157, 'UTF-8') . '...'
			: $metaSource;
	}

	$globalCargoDay = max(0, (int) Settings::get('CARGO_DAY'));
	$productCargoDay = max(0, (int) ($product['cargo_day'] ?? 0));
	$variationData = ProductVariation::getForStorefront($idProduct, (float) $product['price']);
	$optionData = ProductOption::getForStorefront($idProduct);

	$relatedData = Product::getRelatedForProduct($product, 5);

	$havalePercent = Module::getPaymentModule('bank_transfer')
		? (float) Module::getPaymentDiscount('bank_transfer', 100)['percent']
		: (float) (Settings::get('BANKWIRE_DISCOUNT_PERCENT') ?: Settings::get('HAVALE') ?: 0);

	$smarty->assign([
		'product' 			=> $product,
		'productUrl'		=> Product::getLink($product),
		'productName' 		=> $product['product_name'],
		'brandName' 		=> $product['brand_name'],
		'brandUrl' 			=> Brand::getUrl(['brand_link' => $product['brand_link']]),
		'price' 			=> (float) $product['price'],
		'oldPrice' 			=> (float) $product['old_price'],
		'shortDescription' 	=> trim((string) ($product['short_description'] ?? '')),
		'description' 		=> $product['description'],
		'imageUrl' 			=> $images[0]['url'],
		'images' 			=> $images,
		'inStock' 			=> $product['in_stock'],
		'stock' 			=> (int) $product['stock'],
		'stockCode' 		=> $product['stock_code'],
		'productVideoEmbed' => Product::getYoutubeEmbedUrl((string) ($product['product_video'] ?? '')),
		'productLabel' 		=> trim((string) ($product['label'] ?? '')),
		'cargoDay'			=> $productCargoDay > 0 ? $productCargoDay : $globalCargoDay,
		'freeCargo' 		=> ($cargoHints = Cargo::getDisplayHints())['free_shipping_min'],
		'cargoPrice' 		=> $cargoHints['shipping_fee'],
		'havale'			=> $havalePercent,
		'isFavorite' 		=> Favorite::isFavorite($idProduct),
		'relatedProducts'	=> $relatedData['products'],
		'relatedProductsTitle' => $relatedData['title'],
		'hasVariations'     => !empty($variationData['has_variations']),
		'variationGroups'   => $variationData['groups'],
		'variationItemsJson' => json_encode($variationData['items'], JSON_UNESCAPED_UNICODE),
		'hasOptions'        => !empty($optionData['has_options']),
		'optionGroups'      => $optionData['groups'],
		'requiredOptionGroups' => count(array_filter($optionData['groups'], static function (array $group): bool {
			return !empty($group['required']);
		})),
		'breadcrumb' => [
			['name' => translate('Home Page'), 'url' => $domain],
			['name' => $product['category_name'], 'url' => $domain . $product['category_link']],
			['name' => $product['product_name'], 'url' => ''],
		],
		'schemaJsonLd' => SchemaOrg::getProductScripts(
			$product,
			$images,
			[
				['name' => translate('Home Page'), 'url' => rtrim($domain, '/') . '/'],
				['name' => $product['category_name'], 'url' => $domain . $product['category_link']],
				['name' => $product['product_name'], 'url' => Product::getLink($product)],
			],
			$pageTitle,
			$pageDesc,
			(float) $cargoHints['free_shipping_min'],
			(float) $cargoHints['shipping_fee']
		),
	]);

	Module::refreshHook($smarty, 'product', ['id_product' => $idProduct]);
	Module::refreshHook($smarty, 'product_tab', ['id_product' => $idProduct]);
	Module::refreshHook($smarty, 'product_tab_content', ['id_product' => $idProduct]);
	Module::refreshHook($smarty, 'product_inf', ['id_product' => $idProduct]);
	Module::refreshHook($smarty, 'product_detail', ['id_product' => $idProduct]);
