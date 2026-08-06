<div class="prime-container prime-page">
	{if $listTitle}<h1 class="prime-page__title">{$listTitle|escape}</h1>{/if}

	{include file='./plugin/catalogToolbar.tpl'}

	{if !$products|@count}
	<div class="prime-empty">
		<i class="fa-solid fa-box-open"></i>
		<p>{$emptyMessage|default:'Ürün bulunamadı.'|escape}</p>
		<a href="{$domain}" class="prime-btn prime-btn--primary">Ana Sayfaya Dön</a>
	</div>
	{else}
	{include file='./productGrid.tpl' products=$products}
	{include file='./plugin/pagination.tpl'}
	{/if}
</div>
