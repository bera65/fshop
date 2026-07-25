<section class="ig-gallery">
	<div class="ig-gallery__head">
		<div>
			<h2 class="ig-gallery__title">{$settings.title|escape}</h2>
			{if $settings.subtitle}
			<p class="ig-gallery__subtitle">{$settings.subtitle|escape}</p>
			{/if}
		</div>
		{if $settings.profile_url}
		<a href="{$settings.profile_url|escape}" class="ig-gallery__profile" target="_blank" rel="noopener noreferrer">
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
			{$settings.profile_label|escape}
		</a>
		{/if}
	</div>
	<div class="ig-gallery__grid">
		{foreach $items as $item}
		<a href="{if $item.link_url}{$item.link_url|escape}{else}{$item.image_url|escape}{/if}" class="ig-gallery__item" target="_blank" rel="noopener noreferrer"{if $item.caption} title="{$item.caption|escape}"{/if}>
			<img src="{$item.image_url|escape}" alt="{$item.caption|default:$settings.title|escape}" loading="lazy" width="320" height="320">
		</a>
		{/foreach}
	</div>
</section>
