<?php

/**
 * Pazaryeri modülleri (Trendyol, Hepsiburada vb.) bu arayüzü uygular.
 * Admin sayfa kabuğu çekirdekte kalır; modül veri ve panel HTML sağlar.
 */
interface MarketplaceProvider
{
	public function getMarketplaceKey(): string;

	public function getMarketplaceLabel(): string;

	public function isMarketplaceConfigured(): bool;

	public function getMarketplaceSettingsUrl(): string;

	public function handleMarketplaceAdminPosts(string $adminToken): string;

	public function countMarketplaceCatalog(string $query = '', string $filter = 'all'): int;

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function getMarketplaceCatalog(
		string $query = '',
		string $filter = 'all',
		int $limit = 30,
		int $offset = 0
	): array;

	public function renderMarketplaceProductPanelHtml(int $idProduct): string;

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function getMarketplaceOrders(int $limit = 50): array;

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function getMarketplaceQuestions(int $limit = 50): array;

	/**
	 * Ürünler sayfasına özel parçalar (import modal, API URL'leri vb.)
	 *
	 * @return array<string, mixed>
	 */
	public function getMarketplaceProductsPageExtras(): array;

	/**
	 * @return array{css: string[], js: string[]}
	 */
	public function getMarketplaceAdminAssets(): array;

	/**
	 * Ayarlar sekmesi için Smarty değişkenleri.
	 *
	 * @return array<string, mixed>
	 */
	public function getMarketplaceSettingsVars(string $tab): array;

	public function renderMarketplaceSettingsHtml(string $tab): string;
}
