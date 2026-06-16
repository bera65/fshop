<h1 class="page-heading">Ürün Ara</h1>

<form action="{$domain}search" method="get" class="catalog-search mb-4">
	<div class="input-group">
		<input type="text" name="q" class="form-control" placeholder="Ürün, marka veya açıklama ara..." value="{$searchQuery|escape}" minlength="2">
		<button type="submit" class="btn btn-dark">Ara</button>
	</div>
</form>

{if $searchQuery != ''}
<h2 class="fs-5 mb-3">{$listTitle|escape}</h2>
{include file='./catalog.tpl'}
{/if}
