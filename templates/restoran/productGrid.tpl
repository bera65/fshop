{assign var="listProducts" value=$products|default:[]}
{if $listProducts|@count > 0}
<div class="row g-3 menu-product-grid">
	{foreach from=$listProducts item=p}
	<div class="col-12 col-md-6 col-xl-4">
		{include file='./plugin/productCardList.tpl' product=$p}
	</div>
	{/foreach}
</div>
{/if}
