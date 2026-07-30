<?php

namespace Hepsiburada;

class HepsiburadaAdminPages
{
	public static function handlePosts(string $adminToken): string
	{
		$flash = '';

		if (\Tools::isSubmit('saveHepsiburada')) {
			$postToken = (string) \Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				\Settings::set('HEPSIBURADA_MERCHANT_ID', trim((string) \Tools::getValue('merchant_id')));
				\Settings::set('HEPSIBURADA_API_KEY', trim((string) \Tools::getValue('api_key')));
				\Settings::set('HEPSIBURADA_API_PASS', trim((string) \Tools::getValue('api_pass')));
				$flash = 'Hepsiburada API ayarları kaydedildi';
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		if (\Tools::isSubmit('syncHepsiburadaOrders')) {
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

		if (\Tools::isSubmit('syncHepsiburadaQuestions')) {
			$postToken = (string) \Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$result = QuestionService::syncQuestions(1, 50);
				$flash = $result['message'];
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		if (\Tools::isSubmit('answerHepsiburadaQuestion')) {
			$postToken = (string) \Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$result = QuestionService::answer(
					(string) \Tools::getValue('question_id'),
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
			'hbMerchantId' => \Settings::get('HEPSIBURADA_MERCHANT_ID'),
			'hbApiKey' => \Settings::get('HEPSIBURADA_API_KEY'),
			'hbApiPass' => \Settings::get('HEPSIBURADA_API_PASS'),
			'hbConfigured' => ProductSyncService::isConfigured(),
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
			'hb_merchant_sku' => (string) ($mapping['merchant_sku'] ?? ''),
			'hb_hepsiburada_sku' => (string) ($mapping['hepsiburada_sku'] ?? ''),
			'hb_sale_price' => (float) ($mapping['sale_price'] ?? 0),
			'hb_list_price' => (float) ($mapping['list_price'] ?? 0),
			'hb_linked' => ProductSyncService::isLinked($mapping),
			'settingsUrl' => \Admin::url('marketplace-settings?platform=hepsiburada'),
		]);
	}
}
