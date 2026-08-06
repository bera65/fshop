<div class="fl-grid">
{foreach $products as $p}
	{include file='../partials/fl-product-card.tpl' p=$p eager=$p@first}
{/foreach}
</div>
