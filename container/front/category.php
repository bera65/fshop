<?php



	if (!defined('IN_SCRIPT')) {



		exit;



	}







	$link = defined('CATEGORY_SLUG') ? CATEGORY_SLUG : (string) Tools::getValue('link');



	$category = Category::getByLink($link);







	if (!$category) {



		http_response_code(404);



		$skipPageRender = true;



		$page->add('404', translate('Page Not Found'));



		return;



	}







	$css = 'catalog.css';



	$sort = (string) Tools::getValue('sort');



	$currentPage = max(1, (int) Tools::getValue('page'));



	$perPage = Pagination::PER_PAGE;



	$idCategory = (int) $category['id_category'];



	$baseUrl = Category::getUrl($category);







	$catalogFilter = CatalogFilter::forCategory($idCategory);



	$categoryIds = $catalogFilter->getCategoryIds();



	$filterQuery = $catalogFilter->toQueryArray();



	$catalogFilterActive = $catalogFilter->hasActiveFilters();



	$paginationQuery = $filterQuery;







	if ($sort !== '' && $sort !== 'newest') {



		$paginationQuery['sort'] = $sort;



	}







	$productCount = Product::countFiltered($catalogFilter);







	$pagination = Pagination::build($productCount, $currentPage, $perPage, $baseUrl, $paginationQuery);



	$products = Product::getFilteredList(



		$catalogFilter,



		$perPage,



		$pagination['offset'],



		$sort !== '' ? $sort : 'newest'



	);







	$subcategories = Category::getChildren($idCategory);



	foreach ($subcategories as &$sub) {

		$sub['filter_url'] = $catalogFilter->buildUrl(Category::getUrl($sub), ['subcat' => null]);

	}

	unset($sub);



	$filterBrands = Category::getBrandsInCategories($categoryIds);



	$priceRange = Product::getPriceRangeForCategories($categoryIds);



	$filterVariations = $catalogFilter->getVariationFacets();

	foreach ($filterVariations as &$varGroup) {
		$groupKey = (string) ($varGroup['group'] ?? '');
		$varGroup['selected'] = $catalogFilter->variationFilters[$groupKey] ?? '';
	}
	unset($varGroup);



	$filterStock = $catalogFilter->getStockFacets();



	$filterDiscount = $catalogFilter->getDiscountFacets();







	$categorySeo = Seo::resolveEntity(



		(string) ($category['meta_title'] ?? ''),



		(string) ($category['meta_description'] ?? ''),



		$category['category_name'],



		$category['category_name'] . ' ürünleri'



	);



	$pageTitle = $categorySeo['title'];



	$pageDesc = $categorySeo['description'];







	$smarty->assign([



		'category' => $category,



		'products' => $products,



		'productCount' => $productCount,



		'listTitle' => $category['category_name'],



		'pagination' => $pagination,



		'sort' => $sort !== '' ? $sort : 'newest',



		'sortOptions' => Pagination::getSortOptions(),



		'catalogBaseUrl' => $baseUrl,



		'catalogFilterQuery' => $filterQuery,



		'catalogFilter' => [



			'subCategoryId' => $catalogFilter->subCategoryId,



			'brandId' => $catalogFilter->brandId,



			'priceMin' => $catalogFilter->priceMin,



			'priceMax' => $catalogFilter->priceMax,



			'stockStatus' => $catalogFilter->stockStatus,



			'discount' => $catalogFilter->discount,



			'variationFilters' => $catalogFilter->variationFilters,



			'hasActive' => $catalogFilterActive,



			'clearUrl' => $baseUrl,



		],



		'filterSubcategories' => $subcategories,



		'filterSubcategoryAllUrl' => $catalogFilter->buildUrl($baseUrl, ['subcat' => null]),



		'filterBrands' => $filterBrands,



		'filterPriceRange' => $priceRange,



		'filterVariations' => $filterVariations,



		'filterStock' => $filterStock,



		'filterDiscount' => $filterDiscount,



		'emptyMessage' => translate('No products in this category yet.'),



		'breadcrumb' => array_merge(
			[['name' => translate('Home Page'), 'url' => $domain]],
			Category::getBreadcrumbItems((int) ($category['id_category'] ?? 0))
		),



	]);




