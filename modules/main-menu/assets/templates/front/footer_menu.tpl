{foreach $items as $item}
<li class="mm-footer-menu__item">
	<a href="{$item.url|escape}" class="mm-footer-menu__link"{if $item.target == '_blank'} target="_blank" rel="noopener"{/if}>{$item.label|escape}</a>
</li>
{/foreach}
