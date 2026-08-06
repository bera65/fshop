<div class="t4-widget-header-tools t4-header-tools">
	{if $showMenuBtn|default:false}
	<button type="button" class="sm-icon-btn t4-icon-btn t4-hide-desktop" data-bs-toggle="offcanvas" data-bs-target="#primeMobileMenu" aria-label="{$labelMenu|default:'Menu'|escape}">
		<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16M4 12h16M4 19h16"/></svg>
	</button>
	{/if}
	{if $showNotifications|default:false && $isLoggedIn}
	<div class="sm-header__action-slot t4-header-tools__notify">
		{include file='theme4/plugin/notifications-dropdown.tpl'}
	</div>
	{/if}
	{if $showAccount|default:false}
	<a href="{$domain}{if $isLoggedIn}my-account{else}login{/if}" class="t4-tool-icon" title="{$labelAccount|default:'My Account'|escape}" aria-label="{$labelAccount|default:'My Account'|escape}">
		<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
	</a>
	{/if}
	{if $showFavorites|default:false}
	<a href="{$domain}favorites" class="t4-tool-icon" title="{$labelFavorites|default:'Favorites'|escape}" aria-label="{$labelFavorites|default:'Favorites'|escape}">
		<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
		{if $favoriteCount|default:0 > 0}
		<span class="t4-tool-icon__badge">{$favoriteCount}</span>
		{else}
		<span class="t4-tool-icon__badge">0</span>
		{/if}
	</a>
	{/if}
	{if $showCart|default:false}
	<a href="#" class="t4-cart-btn js-show-cart" title="{$labelCart|default:'Cart'|escape}" aria-label="{$labelCart|default:'Cart'|escape}">
		<span class="t4-cart-btn__icon">
			<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
			{if $cart.count|default:0 > 0}
			<span class="t4-cart-btn__badge sm-cart-count-badge">{$cart.count}</span>
			{else}
			<span class="t4-cart-btn__badge sm-cart-count-badge">0</span>
			{/if}
		</span>
		<span class="t4-cart-btn__total">{$cart.total_formatted|default:'0,00 ₺'}</span>
	</a>
	{/if}
</div>
