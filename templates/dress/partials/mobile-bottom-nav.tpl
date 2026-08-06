<nav class="fl-bottom-nav d-lg-none" aria-label="{'Menu'|translate}">
	<a href="{$domain}" class="fl-bottom-nav__item{if $pageName == 'home'} is-active{/if}">
		<span class="fl-bottom-nav__icon" aria-hidden="true">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
		</span>
		<span class="fl-bottom-nav__label">{'Home Page'|translate}</span>
	</a>
	<a href="{$domain}favorites" class="fl-bottom-nav__item{if $pageName == 'favorites'} is-active{/if}">
		<span class="fl-bottom-nav__icon" aria-hidden="true">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
		</span>
		<span class="fl-bottom-nav__label">{'Favorites'|translate}</span>
	</a>
	<a href="{$domain}cart" class="fl-bottom-nav__item{if $pageName == 'cart'} is-active{/if}" onclick="if (typeof showCart === 'function'){ldelim}showCart();return false;{rdelim}">
		<span class="fl-bottom-nav__icon" aria-hidden="true">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
		</span>
		<span class="fl-bottom-nav__label">{'Cart'|translate}</span>
		{if $cart.count|default:0 > 0}
		<span class="fl-bottom-nav__badge" id="mobileCartBadge">{$cart.count}</span>
		{else}
		<span class="fl-bottom-nav__badge d-none" id="mobileCartBadge">0</span>
		{/if}
	</a>
	<a href="{$domain}{if $isLoggedIn}my-account{else}login{/if}" class="fl-bottom-nav__item{if $pageName == 'my-account' || $pageName == 'login'} is-active{/if}">
		<span class="fl-bottom-nav__icon" aria-hidden="true">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
		</span>
		<span class="fl-bottom-nav__label">{if $isLoggedIn}{'My Account'|translate}{else}{'Login'|translate}{/if}</span>
	</a>
</nav>
