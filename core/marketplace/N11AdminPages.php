<?php

namespace N11;

class N11AdminPages
{
	public static function handlePosts(string $adminToken): string
	{
		$flash = '';

		if (\Tools::isSubmit('saveN11')) {
			$postToken = (string) \Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				\Settings::set('N11_API_KEY', trim((string) \Tools::getValue('api_key')));
				\Settings::set('N11_API_SECRET', trim((string) \Tools::getValue('api_secret')));
				$flash = 'N11 API ayarları kaydedildi';
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		if (\Tools::isSubmit('syncN11Orders')) {
			$postToken = (string) \Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$result = OrderService::syncOrders();
				$flash = $result['message'];
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		if (\Tools::isSubmit('syncN11Questions')) {
			$postToken = (string) \Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$result = QuestionService::syncQuestions(0, 50);
				$flash = $result['message'];
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		if (\Tools::isSubmit('answerN11Question')) {
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
	public static function settingsVars(): array
	{
		return [
			'n11ApiKey' => \Settings::get('N11_API_KEY'),
			'n11ApiSecret' => \Settings::get('N11_API_SECRET'),
			'n11Configured' => ProductSyncService::isConfigured(),
		];
	}

	/** @return array<string, mixed> */
	public static function productPanelVars(int $idProduct): array
	{
		$product = \Product::getByIdAdmin($idProduct);
		$mapping = ProductSyncService::findMapping($idProduct) ?: [];
		$urls = \Trendyol\TrendyolAdminPages::commonUrls();

		return array_merge($urls, [
			'id_product' => $idProduct,
			'mapping' => $mapping,
			'configured' => ProductSyncService::isConfigured(),
			'product_stock_code' => (string) ($product['stock_code'] ?? ''),
			'product_price' => (float) ($product['price'] ?? 0),
			'product_stock' => $product ? (int) \Product::getStock($product) : 0,
			'n11_stock_code' => (string) ($mapping['stock_code'] ?? ''),
			'n11_sale_price' => (float) ($mapping['sale_price'] ?? 0),
			'n11_list_price' => (float) ($mapping['list_price'] ?? 0),
			'n11_linked' => ProductSyncService::isLinked($mapping),
			'settingsUrl' => \Admin::url('marketplace-settings?platform=n11'),
		]);
	}
}
