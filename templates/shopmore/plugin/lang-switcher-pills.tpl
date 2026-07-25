{if $langSwitcher|@count > 1}
<div class="lang-switcher-pills" role="group" aria-label="{'Language'|translate}">
	{foreach $langSwitcher as $langItem}
	<a href="{$langItem.url|escape}" class="lang-switcher-pills__item{if $langItem.active} is-active{/if}" hreflang="{$langItem.code|escape}"{if $langItem.active} aria-current="true"{/if}>
		{$langItem.label|escape}
	</a>
	{/foreach}
</div>
{/if}
