{if $hookName == 'main_menu'}
<nav class="t4-widget t4-widget--menu" aria-label="Menu">
	<ul class="sm-header-menu__list sm-header-menu__list--hook t4-nav-list t4-widget-menu">
		{$hookHtml nofilter}
	</ul>
</nav>
{else}
<div class="t4-widget t4-widget--hook" data-hook="{$hookName|escape}">
	{$hookHtml nofilter}
</div>
{/if}
