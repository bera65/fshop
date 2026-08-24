<?php

/**
 * Smarty ortak ayarları.
 *
 * compile_check = true → şablon değişince otomatik yeniden derlenir (cache klasörünü elle silmeye gerek kalmaz).
 * force_compile   → sadece APP_DEBUG açıkken her istekte yeniden derler (geliştirme).
 */
function fshop_configure_smarty(Smarty\Smarty $smarty): void
{
	$root = dirname(__DIR__);
	$compileDir = $root . '/cache/force';
	$cacheDir = $root . '/cache/cache';

	foreach ([$compileDir, $cacheDir] as $dir) {
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
	}

	$smarty->setCompileDir($compileDir);
	$smarty->setCacheDir($cacheDir);
	$smarty->setConfigDir($root . '/configs/');
	$smarty->cache_lifetime = 0;
	$smarty->caching = false;
	$smarty->compile_check = true;

	$cacheEnabled = class_exists('Performance', false) ? Performance::isCacheEnabled() : true;
	$smarty->force_compile = App::isDebug() || !$cacheEnabled;

	$smarty->registerPlugin('modifier', 'contains', static function ($haystack, $needle) {
		if ($needle === '' || $needle === null) {
			return false;
		}

		return strpos((string) $haystack, (string) $needle) !== false;
	});

	$smarty->registerPlugin('modifier', 'split', static function ($value, $delimiter = ',') {
		$parts = explode((string) $delimiter, (string) $value);
		$result = [];

		foreach ($parts as $part) {
			$part = trim($part);

			if ($part !== '') {
				$result[] = $part;
			}
		}

		return $result;
	});

	$smarty->registerPlugin('modifier', 'asset_url', static function ($url) {
		return class_exists('Performance', false)
			? Performance::versionedUrl((string) $url)
			: (string) $url;
	});

	$smarty->registerPlugin('modifier', 'js', static function ($value) {
		return Security::jsString($value);
	});

	$smarty->registerPlugin('modifier', 'json_script', static function ($value) {
		return Security::jsonForHtmlScript($value);
	});
}

function fshop_clear_smarty_compile_cache(): int
{
	$dir = dirname(__DIR__) . '/cache/force';
	$count = 0;

	if (!is_dir($dir)) {
		return 0;
	}

	foreach (scandir($dir) ?: [] as $entry) {
		if ($entry === '.' || $entry === '..') {
			continue;
		}

		$path = $dir . '/' . $entry;

		if (is_file($path) && unlink($path)) {
			$count++;
		}
	}

	return $count;
}
