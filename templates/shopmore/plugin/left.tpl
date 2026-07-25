<div class="dress-mobile-menu">
	<div class="dress-mobile-menu__head">
		<a href="{$domain}" class="dress-mobile-menu__brand">
			<span class="dress-mobile-menu__logo">
				<img src="{$siteLogos.bar|escape}?v={$minute}" alt="{$siteName|escape}">
			</span>
			<span class="dress-mobile-menu__brand-text">
				<strong>{$siteName|escape}</strong>
				<small>{'Online Shopping'|translate}</small>
			</span>
		</a>
		<button type="button" class="dress-mobile-menu__close" data-bs-dismiss="offcanvas" aria-label="{'Menu'|translate}">
			<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
		</button>
	</div>

	<nav class="dress-mobile-menu__nav" aria-label="{'Menu'|translate}">
		{if $mainMenuItems|default:[]|@count > 0}
		{foreach $mainMenuItems as $item}
		{if $item.children|default:[]|@count > 0}
		<div class="mm-m-group">
			<div class="mm-m-head">
				<a href="{$item.url|escape}" class="dress-mobile-menu__item" {if $item.target == '_blank'}target="_blank" rel="noopener"{/if}>
					<span class="dress-mobile-menu__icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
					</span>
					<span class="dress-mobile-menu__label">{$item.label|escape}</span>
				</a>
				<button type="button" class="mm-m-toggle" data-mm-m-toggle aria-expanded="false" aria-label="{$item.label|escape} alt menü">
					<span class="mm-caret" aria-hidden="true"></span>
				</button>
			</div>
			<div class="mm-m-children">
				{foreach $item.children as $child}
				<a href="{$child.url|escape}" class="dress-mobile-menu__item dress-mobile-menu__item--child">
					<span class="dress-mobile-menu__icon" aria-hidden="true"></span>
					<span class="dress-mobile-menu__label">{$child.label|escape}</span>
				</a>
				{/foreach}
			</div>
		</div>
		{else}
		<a href="{$item.url|escape}" class="dress-mobile-menu__item" {if $item.target == '_blank'}target="_blank" rel="noopener"{/if}>
			<span class="dress-mobile-menu__icon" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
			</span>
			<span class="dress-mobile-menu__label">{$item.label|escape}</span>
		</a>
		{/if}
		{/foreach}
		{else}
		{foreach $menuCategories as $cat}
		<a href="{$domain}{$cat.category_link}" class="dress-mobile-menu__item{if isset($category) && $category.category_link == $cat.category_link} is-active{/if}">
			<span class="dress-mobile-menu__icon" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
			</span>
			<span class="dress-mobile-menu__label">{$cat.category_name|escape}</span>
		</a>
		{/foreach}
		{/if}

		<a href="{$domain}special" class="dress-mobile-menu__item{if $pageName == 'special'} is-active{/if}">
			<span class="dress-mobile-menu__icon" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/></svg>
			</span>
			<span class="dress-mobile-menu__label">{'Specilas'|translate}</span>
		</a>

		{foreach $cmsFooterLinks as $cmsLink}
		<a href="{$cmsLink.url}" class="dress-mobile-menu__item">
			<span class="dress-mobile-menu__icon" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
			</span>
			<span class="dress-mobile-menu__label">{$cmsLink.title|escape}</span>
		</a>
		{/foreach}

		{if $hooks.mobile_menu}
		{$hooks.mobile_menu nofilter}
		{/if}
	</nav>

	<div class="dress-mobile-menu__lang">
		{include file='shopmore/plugin/lang-switcher-pills.tpl'}
	</div>
</div>
