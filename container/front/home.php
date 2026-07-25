<?php
	if (!defined('IN_SCRIPT')) {
		header('HTTP/1.0 404 Not Found');
		header('Location: ../404');
		exit;
	}

	$homeSeo = Seo::resolvePage('home');
	$pageTitle = $homeSeo['title'];
	$pageDesc = $homeSeo['description'];

	$featuredProducts = Product::getActiveList(null, 12);
	$topRatedProducts = Product::getTopRatedList(8);
	$categoryBlocks = [];
	$homeCategories = [];
	$categoryIcons = ['📱', '💄', '💊', '🏷️', '👕', '⌚', '🎧', '🏠', '⚽', '📦', '✨', '🛒'];

	foreach (Category::getMenuList() as $index => $cat) {
		$homeCategories[] = [
			'category' => $cat,
			'url' => Category::getUrl($cat),
			'icon' => $categoryIcons[$index % count($categoryIcons)],
			'initial' => mb_strtoupper(mb_substr((string) $cat['category_name'], 0, 1)),
		];
	}

	foreach (Category::getMenuList() as $cat) {
		$products = Product::getActiveList((int) $cat['id_category'], 5);

		if (!$products) {
			continue;
		}

		$categoryBlocks[] = [
			'category' => $cat,
			'products' => $products,
			'url' => Category::getUrl($cat),
		];
	}

	$dealProducts = Product::getDiscountedList(12);
	$newProducts = Product::getActiveList(null, 8, 0, 'newest');
	$homeBrands = Brand::getPublicList(12);
	$cmsBlogLinks = array_slice(Cms::getFooterLinks(), 0, 3);

	$smarty->assign([
		'featuredProducts' => $featuredProducts,
		'topRatedProducts' => $topRatedProducts,
		'dealProducts' => $dealProducts,
		'newProducts' => $newProducts,
		'homeBrands' => $homeBrands,
		'cmsBlogLinks' => $cmsBlogLinks,
		'homeCategories' => $homeCategories,
		'categoryBlocks' => $categoryBlocks,
		'activeOrders' => Order::getActiveOrdersForViewer(3),
	]);