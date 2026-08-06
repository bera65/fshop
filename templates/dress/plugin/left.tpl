<div class="fl-mmenu">
	<div class="fl-mmenu__head">
		<a href="{$domain}" class="fl-mmenu__brand" title="{$siteName|escape}">
			<img src="{$siteLogos.header|escape}?v={$minute|default:1}" alt="{$siteName|escape}" class="fl-mmenu__logo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
			<span class="fl-mmenu__brand-text" style="display:none">
				<strong>{$siteName|escape}</strong>
				{if $themeOptions.tagline|default:'' != ''}
				<small>{$themeOptions.tagline|escape}</small>
				{/if}
			</span>
		</a>
		<button type="button" class="fl-mmenu__close" data-bs-dismiss="offcanvas" aria-label="{'Close'|translate}">
			<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
		</button>
	</div>

	<div class="fl-mmenu__search-wrap" data-sm-search>
		<form class="fl-mmenu__search" action="{$domain}search" method="get" role="search" autocomplete="off">
			<input type="search" name="q" placeholder="{'Search product..'|translate}" value="{$searchQuery|default:''|escape}" autocomplete="off" data-sm-search-input>
			<button type="submit" aria-label="{'Search'|translate}">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
			</button>
		</form>
		<div class="fl-search__suggest fl-search__suggest--menu" data-sm-search-results role="listbox" hidden></div>
	</div>

	<div class="fl-mmenu__cats">
		<div class="fl-mmenu__cats-label">{'Categories'|translate}</div>
		<nav class="fl-mmenu__cat-list" aria-label="{'Categories'|translate}">
			{if $mainMenuItems|default:[]|@count > 0}
				{foreach $mainMenuItems as $item}
				<a href="{$item.url|escape}" class="fl-mmenu__cat"{if $item.target == '_blank'} target="_blank" rel="noopener"{/if}>
					<span>{$item.label|escape}</span>
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
				</a>
				{/foreach}
			{else}
				{foreach $menuCategories as $cat}
				{if $cat.id_parent == 1}
				<a href="{$domain}{$cat.category_link|escape}" class="fl-mmenu__cat{if isset($category) && $category.category_link == $cat.category_link} is-active{/if}">
					<span>{$cat.category_name|escape}</span>
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
				</a>
				{/if}
				{/foreach}
			{/if}

			{if $hooks.mobile_menu}
			{$hooks.mobile_menu nofilter}
			{/if}
		</nav>
	</div>

	{if $langSwitcher|@count > 1}
	<div class="fl-mmenu__lang">
		{include file='dress/plugin/lang-switcher-pills.tpl'}
	</div>
	{/if}
</div>
