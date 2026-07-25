{if $products|@count}
<section class="sm-section">
	<div class="sm-section__head">
		<h2 class="sm-section__title">{$title|escape}</h2>
		{if $url|default:'' != ''}
		<a href="{$url|escape}" class="sm-section__link">{'View All'|translate} →</a>
		{/if}
	</div>
	{include file='../productList.tpl' products=$products id=$listId|default:'categoryProducts'}
</section>
{/if}
