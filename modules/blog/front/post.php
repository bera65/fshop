<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

require_once dirname(__DIR__) . '/lib/BlogService.php';

BlogService::ensureSchema();

global $smarty, $domain, $pageTitle, $pageDesc, $skipPageRender;

$id = (int) Tools::getValue('id');
$slug = trim((string) Tools::getValue('slug'));

$post = null;

if ($id > 0) {
	$post = BlogService::getPublishedById($id);
} elseif ($slug !== '') {
	$post = BlogService::getBySlug($slug);
}

if (!$post) {
	http_response_code(404);
	$skipPageRender = false;
	$container = '404';
	$pageTitle = 'Yazı bulunamadı';
	$pageDesc = 'Aradığınız yazı bulunamadı';

	return;
}

$post = BlogService::enrich($post);
$canonicalSlug = (string) $post['slug'];
$canonicalId = (int) $post['id_blog_post'];
$needRedirect = ($id !== $canonicalId || $slug !== $canonicalSlug);

if (!$needRedirect) {
	$requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
	if (strpos($requestPath, '/blog/') === false) {
		$needRedirect = true;
	}
}

if ($needRedirect) {
	header('Location: ' . BlogService::buildUrl($canonicalSlug, $canonicalId), true, 301);
	exit;
}

$pageTitle = (string) ($post['meta_title'] !== '' ? $post['meta_title'] : $post['title']);
$pageDesc = (string) ($post['meta_description'] !== '' ? $post['meta_description'] : $post['excerpt']);
$skipPageRender = true;

$recentPosts = [];
foreach (BlogService::getList(true, 8, 0) as $row) {
	if ((int) ($row['id_blog_post'] ?? 0) === $canonicalId) {
		continue;
	}
	$recentPosts[] = $row;
	if (count($recentPosts) >= 5) {
		break;
	}
}

$smarty->assign([
	'blogPost' => $post,
	'blogCategories' => BlogService::getCategories(true),
	'blogRecentPosts' => $recentPosts,
	'pageName' => 'blog-post',
	'pageTitle' => $pageTitle,
	'documentTitle' => Seo::formatDocumentTitle($pageTitle, 'blog-post'),
	'pageDesc' => $pageDesc,
	'breadcrumb' => [
		['name' => translate('Home Page'), 'url' => $domain],
		['name' => 'Blog', 'url' => rtrim($domain, '/') . '/blog'],
		['name' => $post['title'], 'url' => ''],
	],
]);

$smarty->display(_THEME_BASE_DIR_ . 'header.tpl');
$smarty->display('file:' . dirname(__DIR__) . '/assets/templates/front/post.tpl');
$smarty->display(_THEME_BASE_DIR_ . 'footer.tpl');
