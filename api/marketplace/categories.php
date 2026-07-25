<?php

if (!defined('IN_SCRIPT')) {
	exit;
}


header('Content-Type: application/json; charset=utf-8');

if (!Admin::isLoggedIn()) {
	echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim'], JSON_UNESCAPED_UNICODE);
	exit;
}


$name = trim((string) Tools::getValue('name', ''));

if (mb_strlen($name) < 2) {
	echo json_encode(['success' => false, 'message' => 'En az 2 karakter yazın'], JSON_UNESCAPED_UNICODE);
	exit;
}

if (!Trendyol\ProductSyncService::isConfigured()) {
	echo json_encode(['success' => false, 'message' => 'API kimlik bilgileri eksik'], JSON_UNESCAPED_UNICODE);
	exit;
}

$result = Trendyol\ProductSyncService::api()->getCategories($name);

if (Trendyol\ProductSyncService::isApiError($result)) {
	echo json_encode([
		'success' => false,
		'message' => (string) ($result['message'] ?? 'Kategori araması başarısız'),
	], JSON_UNESCAPED_UNICODE);
	exit;
}

$tree = [];

if (isset($result['categories']) && is_array($result['categories'])) {
	$tree = $result['categories'];
} elseif (isset($result[0]) && is_array($result[0])) {
	$tree = $result;
} elseif (isset($result['id'])) {
	$tree = [$result];
}

$leaves = [];
flattenCategoryLeaves($tree, [], $leaves);

// Yaprak olmayan eşleşmeleri de göstermemek için sadece leaf; aramada çok sonuç varsa sınırla
usort($leaves, static function ($a, $b) {
	return strcmp($a['path'], $b['path']);
});

$leaves = array_slice($leaves, 0, 40);

echo json_encode([
	'success' => true,
	'categories' => $leaves,
], JSON_UNESCAPED_UNICODE);
exit;

/**
 * @param array<int, mixed> $nodes
 * @param array<int, string> $path
 * @param array<int, array{id:int,name:string,path:string}> $out
 */
function flattenCategoryLeaves(array $nodes, array $path, array &$out): void
{
	foreach ($nodes as $node) {
		if (!is_array($node)) {
			continue;
		}

		$id = (int) ($node['id'] ?? 0);
		$name = trim((string) ($node['name'] ?? ''));

		if ($id <= 0 || $name === '') {
			continue;
		}

		$newPath = array_merge($path, [$name]);
		$subs = $node['subCategories'] ?? null;

		if (!is_array($subs) || $subs === []) {
			$out[] = [
				'id' => $id,
				'name' => $name,
				'path' => implode(' › ', $newPath),
			];
			continue;
		}

		flattenCategoryLeaves($subs, $newPath, $out);
	}
}
