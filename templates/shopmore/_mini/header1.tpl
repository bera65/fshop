<header class="sm-header">
	<div class="sm-container">
		{* Mobile *}
		<div class="sm-header-mobile">
			<div class="sm-header__row">
				<button type="button" class="sm-icon-btn" data-bs-toggle="offcanvas" data-bs-target="#primeMobileMenu" aria-label="Menu">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16M4 12h16M4 19h16"/></svg>
				</button>
				<a href="{$domain}" class="sm-logo" title="{$siteName|escape}">
					<img src="{$siteLogos.header|escape}?v={$minute|default:1}" alt="{$siteName|escape}" onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
					<span style="display:none">Shop<span style="color:var(--sm-primary)">more</span></span>
				</a>
				<div class="sm-header__actions">
					{if $isLoggedIn}
					<div class="sm-header__action-slot">
						{include file='shopmore/plugin/notifications-dropdown.tpl'}
					</div>
					{/if}
					<a href="{$domain}{if $isLoggedIn}my-account{else}login{/if}" class="sm-icon-btn" title="{'My Account'|translate}">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
					</a>
					<a href="#" class="sm-icon-btn js-show-cart" title="{'Cart'|translate}">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
						{if $cart.count|default:0 > 0}<span class="sm-icon-btn__badge sm-cart-count-badge">{$cart.count}</span>{else}<span class="sm-icon-btn__badge sm-cart-count-badge d-none">0</span>{/if}
					</a>
				</div>
			</div>
			<div class="sm-search-wrap">
				{include file='shopmore/plugin/header-search.tpl' smSearchInputId='smSearchMobile'}
			</div>
		</div>

		{* Desktop *}
		<div class="sm-header-desktop">
			<div class="sm-header-desktop__top">
				<a href="{$domain}" class="sm-logo" title="{$siteName|escape}">
					<img src="{$siteLogos.header|escape}?v={$minute|default:1}" alt="{$siteName|escape}" onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
					<span style="display:none">Shop<span style="color:var(--sm-primary)">more</span></span>
				</a>
				<div class="sm-search-desktop">
					{include file='shopmore/plugin/header-search.tpl' smSearchInputId='smSearchDesktop'}
				</div>
				<div class="sm-header__actions">
					{if $isLoggedIn}
					<div class="sm-header__action-slot">
						{include file='shopmore/plugin/notifications-dropdown.tpl'}
					</div>
					{/if}
					<a href="{$domain}{if $isLoggedIn}my-account{else}login{/if}" class="sm-icon-btn" title="{'My Account'|translate}">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
					</a>
					<a href="#" class="sm-icon-btn js-show-cart" title="{'Cart'|translate}">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
						{if $cart.count|default:0 > 0}<span class="sm-icon-btn__badge sm-cart-count-badge">{$cart.count}</span>{else}<span class="sm-icon-btn__badge sm-cart-count-badge d-none">0</span>{/if}
					</a>
				</div>
			</div>
		</div>

		{* Kategori menüsü (main_menu modülü veya varsayılan) *}
		<nav class="sm-header-menu" aria-label="{'Categories'|translate}">
			{if $hooks.main_menu}
				<ul class="sm-header-menu__list sm-header-menu__list--hook">
					{$hooks.main_menu nofilter}
				</ul>
			{else}
				<ul class="sm-header-menu__list">
					{foreach $menuCategories as $cat}
					{if $cat.id_parent == 1}
					<li>
						<a href="{$domain}{$cat.category_link|escape}" class="sm-header-menu__link{if isset($category) && $category.category_link == $cat.category_link} is-active{/if}">{$cat.category_name|escape}</a>
					</li>
					{/if}
					{/foreach}
				</ul>
			{/if}
		</nav>
	</div>
</header>
