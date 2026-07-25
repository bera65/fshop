<?php

namespace Trendyol;

class TrendyolAdminPages
{
	public static function handlePosts(string $adminToken): string
	{
		$flash = '';

		if (\Tools::isSubmit('saveTrendyol')) {
			$postToken = (string) \Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				\Settings::set('TRENDYOL_MERCHANT_ID', trim((string) \Tools::getValue('merchant_id')));
				\Settings::set('TRENDYOL_API_KEY', trim((string) \Tools::getValue('api_key')));
				\Settings::set('TRENDYOL_API_SECRET', trim((string) \Tools::getValue('api_secret')));
				$flash = 'Trendyol API ayarları kaydedildi';
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		if (\Tools::isSubmit('saveFiyattrend')) {
			$postToken = (string) \Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				\Settings::set('TRENDYOL_FIYATTREND_TOKEN', trim((string) \Tools::getValue('fiyattrend_token')));
				$flash = 'FiyatTrend ayarları kaydedildi';
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		if (\Tools::isSubmit('syncTrendyolOrders')) {
			$postToken = (string) \Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$start = trim((string) \Tools::getValue('start_date'));
				$end = trim((string) \Tools::getValue('end_date'));
				$result = OrderService::syncOrders($start !== '' ? $start : null, $end !== '' ? $end : null);
				$flash = $result['message'];
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		if (\Tools::isSubmit('syncTrendyolQuestions')) {
			$postToken = (string) \Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$result = QuestionService::syncQuestions(0, 50);
				$flash = $result['message'];
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		if (\Tools::isSubmit('answerTrendyolQuestion')) {
			$postToken = (string) \Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$result = QuestionService::answer(
					(int) \Tools::getValue('question_id'),
					(string) \Tools::getValue('answer_text')
				);
				$flash = $result['message'];
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		return $flash;
	}

	/** @return array<string, mixed> */
	public static function commonUrls(): array
	{
		$domain = rtrim((string) \Settings::get('DOMAIN'), '/') . '/';
		$api = rtrim($domain, '/') . '/api/marketplace.php';
		$adminJs = rtrim((string) ($GLOBALS['adminCssDir'] ?? $domain . 'templates/admin/css/'), '/');
		$adminJs = preg_replace('#/css/?$#', '/js', $adminJs) ?: rtrim($domain, '/') . '/templates/admin/js';

		return [
			'syncUrl' => $api . '?action=sync',
			'priceUrl' => $api . '?action=update-price',
			'unlinkUrl' => $api . '?action=unlink',
			'refreshUrl' => $api . '?action=refresh',
			'brandsUrl' => $api . '?action=brands',
			'categoriesUrl' => $api . '?action=categories',
			'attributesUrl' => $api . '?action=attributes',
			'importUrl' => $api . '?action=import-product',
			'linkExistingUrl' => $api . '?action=link-existing',
			'updateStockUrl' => $api . '?action=update-stock',
			'assetsJsUrl' => $adminJs . '/marketplace-admin.js',
			'importJsUrl' => $adminJs . '/marketplace-import.js',
			'settingsUrl' => \Admin::url('marketplace-settings'),
			'productsUrl' => \Admin::url('marketplace-products'),
			'ordersUrl' => \Admin::url('marketplace-orders'),
			'questionsUrl' => \Admin::url('marketplace-questions'),
			'cronOrdersUrl' => $api . '?action=cron&type=orders&token=' . urlencode((string) \Settings::get('SHOP_TOKEN')),
			'cronQuestionsUrl' => $api . '?action=cron&type=questions&token=' . urlencode((string) \Settings::get('SHOP_TOKEN')),
		];
	}

	/** @return array<string, mixed> */
	public static function settingsVars(): array
	{
		return [
			'tyMerchantId' => \Settings::get('TRENDYOL_MERCHANT_ID'),
			'tyApiKey' => \Settings::get('TRENDYOL_API_KEY'),
			'tyApiSecret' => \Settings::get('TRENDYOL_API_SECRET'),
			'tyFiyattrendToken' => \Settings::get('TRENDYOL_FIYATTREND_TOKEN'),
		];
	}

	/** @return array<string, mixed> */
	public static function productPanelVars(int $idProduct): array
	{
		$product = \Product::getByIdAdmin($idProduct);
		$mapping = ProductSyncService::findMapping($idProduct) ?: [];
		$urls = self::commonUrls();

		$attrs = [];

		if (!empty($mapping['attributes_json'])) {
			$decoded = json_decode((string) $mapping['attributes_json'], true);
			$attrs = is_array($decoded) ? $decoded : [];
		}

		return array_merge($urls, [
			'id_product' => $idProduct,
			'mapping' => $mapping,
			'configured' => ProductSyncService::isConfigured(),
			'product_barcode' => (string) ($product['barcode'] ?? ''),
			'product_price' => (float) ($product['price'] ?? 0),
			'product_old_price' => (float) ($product['old_price'] ?? 0),
			'product_stock' => $product ? (int) \Product::getStock($product) : 0,
			'ty_sale_price' => (float) ($mapping['sale_price'] ?? 0),
			'ty_list_price' => (float) ($mapping['list_price'] ?? 0),
			'ty_has_price' => ProductSyncService::hasTrendyolPrice($mapping),
			'ty_brand_id' => (int) ($mapping['brand_id'] ?? 0),
			'ty_brand_name' => '',
			'ty_category_id' => (int) ($mapping['category_id'] ?? 0),
			'ty_category_name' => '',
			'ty_attributes' => $attrs,
			'ty_attributes_json' => json_encode($attrs, JSON_UNESCAPED_UNICODE),
		]);
	}
}
