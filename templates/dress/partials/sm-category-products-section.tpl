{if $products|@count}
<section class="fl-section">
	<div class="fl-section__head">
		<div>
			<h2 class="fl-section__title">{$title|default:{'All Products'|translate}|escape}</h2>
			{if $products|@count}
			<p class="fl-section__sub">{$products|@count} {'products'|translate}</p>
			{/if}
		</div>
		{if $url|default:'' != ''}
		<a href="{$url|escape}" class="fl-section__link">
			{'View All'|translate}
			<span class="fl-section__link-icon" aria-hidden="true">→</span>
		</a>
		{/if}
	</div>
	{include file='../productList.tpl' products=$products id=$listId|default:'categoryProducts'}
</section>
{/if}
