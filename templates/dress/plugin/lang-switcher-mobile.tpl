{if $langSwitcher|@count > 1}
<div class="prime-mobile-lang" role="group" aria-label="{'Language'|translate}">
	{foreach $langSwitcher as $langItem}
	<a href="{$langItem.url|escape}" class="prime-mobile-lang__btn{if $langItem.active} is-active{/if}" hreflang="{$langItem.code|escape}"{if $langItem.active} aria-current="true"{/if}>{$langItem.label|escape}</a>
	{/foreach}
</div>
{/if}
