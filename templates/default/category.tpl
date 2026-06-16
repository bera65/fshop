<h1 class="page-heading">{$listTitle|escape}</h1>

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
