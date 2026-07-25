{include file='admin/marketplace/_nav.tpl'}

<div class="d-flex flex-wrap gap-2 mb-3">
	{foreach $marketplacePlatforms as $platform}
	{if $platform.active}
	<a href="{$adminUrl}marketplace-settings?platform={$platform.key|escape:url}&amp;tab={$tab|escape:url}"
		class="btn btn-sm {if $marketplacePlatform == $platform.key}btn-dark{else}btn-outline-secondary{/if}">
		{$platform.label|escape}
	</a>
	{else}
	<span class="btn btn-sm btn-outline-secondary disabled" title="Yakında">{$platform.label|escape} · Yakında</span>
	{/if}
	{/foreach}
</div>

{if $marketplacePlatform == 'trendyol'}
{include file='admin/marketplace/settings_trendyol.tpl'}
{else}
<div class="admin-panel p-4 text-center">
	<h2 class="h5 mb-2">Yakında</h2>
	<p class="text-muted mb-0">Bu pazaryeri entegrasyonu üzerinde çalışıyoruz.</p>
</div>
{/if}

{include file='admin/marketplace/_close.tpl'}
