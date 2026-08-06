{if $showTopBar|default:'1' == '1'}
<div class="fy-topbar">
	<div class="fy-container fy-topbar__inner">
		<div class="fy-topbar__left">
			{if $contactPhone}
			<a class="fy-topbar__item" href="tel:{$contactPhoneTel|default:$contactPhone|escape}">
				<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
				{$contactPhone|escape}
			</a>
			{/if}
			{if $contactEmail}
			<a class="fy-topbar__item" href="mailto:{$contactEmail|escape}">
				<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
				{$contactEmail|escape}
			</a>
			{/if}
		</div>
		<div class="fy-topbar__right">
			<div class="fy-topbar__social">
				{if $facebookLink}<a href="{$facebookLink|escape}" target="_blank" rel="noopener" aria-label="Facebook"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>{/if}
				{if $xLink}<a href="{$xLink|escape}" target="_blank" rel="noopener" aria-label="X"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4l11.5 16h4.5L8.5 4z"/><path d="M4 20L16.5 4"/></svg></a>{/if}
				{if $instagramLink}<a href="{$instagramLink|escape}" target="_blank" rel="noopener" aria-label="Instagram"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg></a>{/if}
				{if $youtubeLink}<a href="{$youtubeLink|escape}" target="_blank" rel="noopener" aria-label="YouTube"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/></svg></a>{/if}
				{if $linkedinLink}<a href="{$linkedinLink|escape}" target="_blank" rel="noopener" aria-label="LinkedIn"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg></a>{/if}
				{if $tiktokLink}<a href="{$tiktokLink|escape}" target="_blank" rel="noopener" aria-label="TikTok"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg></a>{/if}
			</div>
			<span class="fy-topbar__divider d-none d-md-inline-block"></span>
			{include file='fyazilim/plugin/lang-switcher.tpl'}
			<select class="fy-topbar__select fy-currency-switcher" aria-label="Currency" onchange="if(this.value){ location.href=this.value; }">
				{if $currencyOptions|default:[]|@count > 0}
				{foreach from=$currencyOptions item=opt}
				<option value="{$opt.url|escape}"{if $opt.is_active} selected{/if}>{$opt.code|upper|escape} {$opt.symbol|escape}</option>
				{/foreach}
				{else}
				<option value="{$siteBaseUrl|default:$domain|escape}/?set_currency=try&amp;redirect={$currencyRedirectPath|default:'/'|escape:'url'}"{if $displayCurrency|default:'try' == 'try'} selected{/if}>TRY ₺</option>
				<option value="{$siteBaseUrl|default:$domain|escape}/?set_currency=usd&amp;redirect={$currencyRedirectPath|default:'/'|escape:'url'}"{if $displayCurrency|default:'' == 'usd'} selected{/if}>USD $</option>
				<option value="{$siteBaseUrl|default:$domain|escape}/?set_currency=eur&amp;redirect={$currencyRedirectPath|default:'/'|escape:'url'}"{if $displayCurrency|default:'' == 'eur'} selected{/if}>EUR €</option>
				{/if}
			</select>
			<span class="fy-topbar__divider d-none d-md-inline-block"></span>
			{if $isLoggedIn}
			<a class="fy-topbar__auth" href="{$domain}my-account">{'My Account'|translate}</a>
			{else}
			<a class="fy-topbar__auth" href="{$domain}login" data-auth-open="login">{'Login'|translate}</a>
			<span>/</span>
			<a class="fy-topbar__auth" href="{$domain}register" data-auth-open="register">{'Register'|translate}</a>
			{/if}
		</div>
	</div>
</div>
{/if}

<header class="fy-header fy-header--with-search-icon">
	<div class="fy-container fy-header__inner">
		<button class="fy-menu-toggle" type="button" data-bs-toggle="offcanvas" href="#primeMobileMenu" aria-controls="primeMobileMenu" aria-label="Menü">
			<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h16"/></svg>
		</button>

		<a href="{$domain}" class="fy-logo" title="{$siteName}">
			<img src="{$domain}img/logo.png" alt="{$siteName}" />
		</a>

		<div class="fy-header__menu-area">
			<ul class="fy-nav" data-fy-header-nav>
				{if $hooks.main_menu}
					{$hooks.main_menu nofilter}
				{else}
				{foreach $menuCategories as $cat}
				{if $cat.id_parent == 1}
				<li>
					<a href="{$domain}{$cat.category_link}" class="{if isset($category) && $category.category_link == $cat.category_link}active{/if}">
						{$cat.category_name|escape}
					</a>
					{if $themeOptions.dropdown_subcategories|default:'1' == '1' && $cat.subcategories|default:[]|@count > 0}
					<div class="fy-nav__drop">
						{foreach $cat.subcategories as $child}
						<a href="{$domain}{$child.category_link|escape}">{$child.category_name|escape}</a>
						{/foreach}
					</div>
					{/if}
				</li>
				{/if}
				{/foreach}
				{/if}
			</ul>

			<div class="fy-header-search fy-header-search--swap" data-fy-search-slot hidden>
				{include file='fyazilim/plugin/header-search.tpl' fySearchInputId='fyHeaderSearchSwapInput'}
			</div>
		</div>

		<div class="fy-header__actions">
			<button
				type="button"
				class="fy-header-icon-btn"
				data-fy-search-open
				aria-label="{'Search'|translate}"
				aria-expanded="false"
				aria-controls="fyHeaderSearchSwapInput"
			>
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
			</button>
			{if $isLoggedIn}
				{include file='fyazilim/plugin/notifications-dropdown.tpl'}
			{/if}
			<a href="{$domain}cart" class="fy-cart-pill" title="{'Cart'|translate}">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 11-1 9"/><path d="m19 11-4-7"/><path d="M2 11h20"/><path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.7-7.4"/><path d="M4.5 15.5h15"/><path d="m5 11 4-7"/><path d="m9 11 1 9"/></svg>
				<span class="d-none d-sm-inline">{'Cart'|translate}</span>
				{if $cart.count|default:0 > 0}
					<span class="fy-cart-badge" id="cartCount">{$cart.count}</span>
				{else}
					<span class="fy-cart-badge d-none" id="cartCount">0</span>
				{/if}
			</a>
		</div>
	</div>
</header>
