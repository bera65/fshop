<h1 class="page-heading">Kampanyalar</h1>

<div class="row g-3 mb-4">
	<div class="col-md-6">
		<div class="page-card h-100">
			<div class="fs-4 mb-2">🎁</div>
			<h2 class="fs-6">1500₺ Üzeri Ücretsiz Kargo</h2>
			<p class="text-muted small mb-0">Sepet tutarı 1500₺ ve üzeri olduğunda kargo bedeli alınmaz.</p>
		</div>
	</div>
	<div class="col-md-6">
		<div class="page-card h-100">
			<div class="fs-4 mb-2">💳</div>
			<h2 class="fs-6">Havalede %3 İndirim</h2>
			<p class="text-muted small mb-0">Havale / EFT ile ödemelerde ek indirim fırsatı.</p>
		</div>
	</div>
</div>

<h2 class="fs-5 mb-1">İndirimli Ürünler</h2>
{assign var="listTitle" value="İndirimli ürünler"}
{assign var="emptyMessage" value="Şu an indirimli ürün bulunmuyor."}

{include file='./plugin/catalogToolbar.tpl'}

{if !$products|@count}
	<div class="catalog-empty text-center py-5">
		<p class="text-muted mb-3">{$emptyMessage|escape}</p>
		<a href="{$domain}" class="btn btn-dark btn-sm">Ana Sayfaya Dön</a>
	</div>
{else}
	{include file='./productGrid.tpl' products=$products}
	{include file='./plugin/pagination.tpl'}
{/if}
