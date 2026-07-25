<div class="prime-catalog">
	<div class="prime-catalog__layout">
		<aside class="prime-catalog__sidebar prime-hide-mobile" aria-label="Filtreler">
			{include file='./plugin/catalogFilters.tpl'}
		</aside>
		<div class="prime-catalog__main">
			<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
				<h1 class="sm-section__title mb-0">{$listTitle|escape}</h1>
				<button type="button" class="btn btn-outline-secondary btn-sm prime-hide-desktop" data-bs-toggle="offcanvas" data-bs-target="#primeCatalogFilters">Filtrele</button>
			</div>
			{if $catalogFilter.hasActive}
			<div class="prime-filter-chips mb-3">
				<a href="{$catalogFilter.clearUrl|escape}" class="prime-filter-chip">Filtreleri temizle</a>
			</div>
			{/if}
			{include file='./plugin/catalogToolbar.tpl'}
			{if !$products|@count}
			<div class="prime-empty text-center py-5">
				<p class="text-muted">{$emptyMessage|escape}</p>
				<a href="{$domain}" class="sm-hero-slide__btn">{'Home Page'|translate}</a>
			</div>
			{else}
			{include file='./productGrid.tpl' products=$products}
			{include file='./plugin/pagination.tpl'}
			{/if}
		</div>
	</div>
</div>
<div class="offcanvas offcanvas-start" tabindex="-1" id="primeCatalogFilters">
	<div class="offcanvas-header">
		<h2 class="offcanvas-title h6">Filtreler</h2>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
	</div>
	<div class="offcanvas-body">
		{include file='./plugin/catalogFilters.tpl'}
	</div>
</div>
