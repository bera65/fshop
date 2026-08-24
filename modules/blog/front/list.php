<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

require_once dirname(__DIR__) . '/lib/BlogService.php';

BlogService::ensureSchema();

global $smarty, $domain, $pageTitle, $pageDesc, $skipPageRender;

$categoryId = (int) Tools::getValue('id_category');
$catSlug = trim((string) Tools::getValue('cat_slug'));
$currentCategory = null;

if ($categoryId > 0) {
	$currentCategory = BlogService::getPublishedCategory($categoryId);
} elseif ($catSlug !== '') {
	$row = DB::getRowSafe('blog_categories', 'slug = ? AND active = 1', [$catSlug]);
	if ($row) {
		$currentCategory = BlogService::enrichCategory($row);
		$categoryId = (int) $currentCategory['id_blog_category'];
	}
}

if ($categoryId > 0 && !$currentCategory) {
	http_response_code(404);
	$skipPageRender = false;
	$container = '404';
	$pageTitle = 'Kategori bulunamadı';
	$pageDesc = 'Aradığınız blog kategorisi bulunamadı';

	return;
}

if ($currentCategory) {
	$canonicalSlug = (string) $currentCategory['slug'];
	$canonicalId = (int) $currentCategory['id_blog_category'];
	$requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
	$needRedirect = ($catSlug !== $canonicalSlug || (int) Tools::getValue('id_category') !== $canonicalId);

	if (!$needRedirect && strpos($requestPath, '/blog/kategori/') === false) {
		$needRedirect = true;
	}

	if ($needRedirect) {
		header('Location: ' . BlogService::buildCategoryUrl($canonicalSlug, $canonicalId), true, 301);
		exit;
	}
}

$posts = BlogService::getList(true, 24, 0, $categoryId);
$categories = BlogService::getCategories(true);

$pageTitle = $currentCategory ? ((string) $currentCategory['name'] . ' — Blog') : 'Blog';
$pageDesc = $currentCategory
	? (string) ($currentCategory['description'] !== '' ? $currentCategory['description'] : $currentCategory['name'])
	: 'Blog yazıları ve haberler';
$skipPageRender = true;

$breadcrumb = [
	['name' => translate('Home Page'), 'url' => $domain],
	['name' => 'Blog', 'url' => $currentCategory ? (rtrim($domain, '/') . '/blog') : ''],
];

if ($currentCategory) {
	$breadcrumb[] = ['name' => $currentCategory['name'], 'url' => ''];
}

$smarty->assign([
	'blogPosts' => $posts,
	'blogCategories' => $categories,
	'blogCategory' => $currentCategory,
	'pageName' => 'blog',
	'pageTitle' => $pageTitle,
	'documentTitle' => Seo::formatDocumentTitle($pageTitle, 'blog'),
	'pageDesc' => $pageDesc,
	'breadcrumb' => $breadcrumb,
]);

$smarty->display(_THEME_BASE_DIR_ . 'header.tpl');
$smarty->display('file:' . dirname(__DIR__) . '/assets/templates/front/list.tpl');
$smarty->display(_THEME_BASE_DIR_ . 'footer.tpl');
