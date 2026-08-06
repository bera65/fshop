{if $title|default:'' != '' || ($items|default:[])}
<div class="t4-widget-links">
	{if $title|default:'' != ''}
	<h3 class="t4-widget-links__title">{$title|escape}</h3>
	{/if}
	{if $items|default:[]}
	<ul class="t4-widget-links__list">
		{foreach $items as $item}
		<li><a href="{$item.url|escape}">{$item.label|escape}</a></li>
		{/foreach}
	</ul>
	{/if}
</div>
{/if}
