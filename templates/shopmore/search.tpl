<div class="prime-container prime-page">
	<h1 class="prime-page__title">Ürün Ara</h1>
	{if $searchQuery != ''}
		{include file='./catalog.tpl'}
	{else}
		<div class="alert alert-warning">Sonuç bulunamadı</div>
	{/if}
</div>
