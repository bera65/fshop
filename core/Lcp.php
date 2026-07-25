<?php

/**
 * Largest Contentful Paint — ön yükleme URL'si ve sayfa bazlı LCP görseli.
 */
class Lcp
{
	public static function assignPreload(string $container): void
	{
		global $smarty;

		$vars = $smarty->getTemplateVars();
		$url = self::resolveUrl($container, is_array($vars) ? $vars : []);

		if ($url !== '') {
			$smarty->assign('lcpImage', $url);
		}
	}

	/**
	 * @param array<string, mixed> $vars
	 */
	public static function resolveUrl(string $container, array $vars): string
	{
		if ($container === 'product') {
			$imageUrl = trim((string) ($vars['imageUrl'] ?? ''));

			return $imageUrl !== '' ? $imageUrl : '';
		}

		if ($container === 'category' || $container === 'search') {
			$products = $vars['products'] ?? [];

			if (is_array($products) && isset($products[0]['image_url'])) {
				$url = trim((string) $products[0]['image_url']);

				return $url !== '' ? $url : '';
			}
		}

		if ($container === 'home') {
			return self::resolveHomeUrl();
		}

		return '';
	}

	private static function resolveHomeUrl(): string
	{
		if (Module::isEnabled('ftheme-edit')) {
			require_once Module::path('ftheme-edit') . '/lib/FthemeBlocks.php';
			$units = FthemeBlocks::buildRenderUnits(FthemeBlocks::getEnabledBlocks());

			foreach ($units as $unit) {
				if (($unit['type'] ?? '') === 'banner_row') {
					$banners = $unit['banners'] ?? [];

					if (is_array($banners) && isset($banners[0])) {
						$url = self::publicAssetUrl((string) ($banners[0]['image'] ?? ''));

						if ($url !== '') {
							return $url;
						}
					}
				}

				if (($unit['type'] ?? '') === 'block' && ($unit['block']['type'] ?? '') === 'slider') {
					$url = self::firstHeroSlideUrl();

					if ($url !== '') {
						return $url;
					}
				}
			}
		}

		return self::firstHeroSlideUrl();
	}

	private static function firstHeroSlideUrl(): string
	{
		if (!Module::isEnabled('slider')) {
			return '';
		}

		$file = Module::path('slider') . '/slider.php';

		if (!is_file($file)) {
			return '';
		}

		require_once $file;
		$slides = SliderModule::getList('hero');

		if ($slides === [] || empty($slides[0]['image_url'])) {
			return '';
		}

		return (string) $slides[0]['image_url'];
	}

	private static function publicAssetUrl(string $path): string
	{
		global $domain;

		$path = trim(str_replace('\\', '/', $path));

		if ($path === '') {
			return '';
		}

		if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
			return $path;
		}

		return rtrim((string) $domain, '/') . '/' . ltrim($path, '/');
	}
}
