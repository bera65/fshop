{assign var="listProducts" value=$products|default:[]}
{if $listProducts|@count == 0 && isset($product)}
	{assign var="listProducts" value=$product}
{/if}
{if $listProducts|@count > 0}
<div class="fl-grid">
	{foreach $listProducts as $p}
		{include file='./partials/fl-product-card.tpl' p=$p eager=$p@first}
	{/foreach}
</div>
{/if}
