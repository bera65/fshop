<div class="container page-section py-4">
	{if $listTitle}<h1 class="fw-bold mb-4">{$listTitle|escape}</h1>{/if}

	{include file='./plugin/catalogToolbar.tpl'}

	{if !$products|@count}
	<div class="empty-state text-center py-5">
		<i class="bi bi-emoji-frown fs-1 text-muted"></i>
		<p class="text-muted mt-3 mb-4">{$emptyMessage|default:'Ürün bulunamadı.'|escape}</p>
		<a href="{$domain}" class="btn btn-dark">{'Home Page'|translate}</a>
	</div>
	{else}
	{include file='./productGrid.tpl' products=$products}
	{include file='./plugin/pagination.tpl'}
	{/if}
</div>
