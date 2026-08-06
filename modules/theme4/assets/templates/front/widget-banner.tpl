<div class="t4-widget t4-widget--banner">
	{if $link != ''}
	<a href="{$link|escape}" class="t4-widget-banner__link">
		<img src="{$image|escape}" alt="{$alt|escape}" class="t4-widget-banner__img" loading="lazy">
	</a>
	{else}
	<img src="{$image|escape}" alt="{$alt|escape}" class="t4-widget-banner__img" loading="lazy">
	{/if}
</div>
