{* Flower header — duyuru + logo/aksiyonlar + menü/arama *}
<header class="fl-header">
	{assign var="flAnnouncement" value=$themeOptions.announcement|default:''}
	{if $flAnnouncement != ''}
	<div class="fl-topbar">
		<div class="fl-container">
			<p class="fl-topbar__text">{$flAnnouncement|escape}</p>
		</div>
	</div>
	{elseif $freeShippingMin > 0}
	<div class="fl-topbar">
		<div class="fl-container">
			<p class="fl-topbar__text">{Tools::displayPrice($freeShippingMin)} {'Free shipping over'|translate}</p>
		</div>
	</div>
	{/if}

	<div class="fl-header__main">
		<div class="fl-container fl-header__main-row">
			<div class="fl-header__brand">
				<button type="button" class="fl-icon-btn fl-icon-btn--ghost d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#primeMobileMenu" aria-label="{'Menu'|translate}">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16M4 12h16M4 19h16"/></svg>
				</button>

				<a href="{$domain}" class="fl-logo" title="{$siteName|escape}">
					<img src="{$siteLogos.header|escape}?v={$minute|default:1}" alt="{$siteName|escape}" class="fl-logo__img" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
					<span class="fl-logo__fallback" style="display:none">
						<span class="fl-logo__name">{$siteName|escape}</span>
						{if $themeOptions.tagline|default:'' != ''}
						<span class="fl-logo__tag">{$themeOptions.tagline|escape}</span>
						{elseif $footerDescription|default:'' != ''}
						<span class="fl-logo__tag">{$footerDescription|escape|truncate:48}</span>
						{/if}
					</span>
				</a>
				{if $themeOptions.tagline|default:'' != ''}
				<span class="fl-logo__tag d-none d-md-inline fl-logo__tag--beside">{$themeOptions.tagline|escape}</span>
				{/if}
			</div>
			<div class="fl-search-wrap" data-sm-search>
				<form class="fl-search" action="{$domain}search" method="get" role="search" autocomplete="off">
					<input type="search" name="q" class="fl-search__input" placeholder="{'Search product..'|translate}" value="{$searchQuery|default:''|escape}" autocomplete="off" data-sm-search-input>
					<button type="submit" class="fl-search__btn" aria-label="{'Search'|translate}">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
					</button>
				</form>
				<div class="fl-search__suggest" data-sm-search-results role="listbox" hidden></div>
			</div>
			<div class="fl-header__actions">
				{assign var="flCurrMeta" value=Currency::getMetaMap()}
				{assign var="flCurrCode" value=$displayCurrency|default:$shopCurrencyCode|default:'try'}
				{assign var="flCurrList" value=Currency::getAvailable()}
				{if $flCurrList|@count > 1}
				<div class="dropdown d-none d-md-inline-flex">
					<button type="button" class="fl-pill-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
						{if isset($flCurrMeta[$flCurrCode])}{$flCurrMeta[$flCurrCode].symbol|escape}{/if}
						<span style="text-transform:uppercase">{$flCurrCode|escape}</span>
					</button>
					<ul class="dropdown-menu dropdown-menu-end">
						{foreach $flCurrList as $code}
						<li>
							<a class="dropdown-item{if $code == $flCurrCode} active{/if}" href="{$domain}?set_currency={$code|escape}&amp;redirect={$smarty.server.REQUEST_URI|escape:'url'}">
								{if isset($flCurrMeta[$code])}{$flCurrMeta[$code].symbol|escape} {/if}<span style="text-transform:uppercase">{$code|escape}</span>
							</a>
						</li>
						{/foreach}
					</ul>
				</div>
				{/if}

				<a href="{$domain}{if $isLoggedIn}my-account{else}login{/if}" class="fl-btn-primary d-none d-md-inline-flex" title="{'My Account'|translate}">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
					<span class="d-none d-sm-inline">{if $isLoggedIn}{'My Account'|translate}{else}{'Login'|translate}{/if}</span>
				</a>

				{if $isLoggedIn}
				<div class="fl-header__notify">
					{include file='dress/plugin/notifications-dropdown.tpl'}
				</div>
				{else}
				<a href="{$domain}login" class="fl-icon-btn d-none d-md-inline-flex" title="{'Notifications'|translate}">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
				</a>
				{/if}

				<a href="{$domain}cart" class="fl-icon-btn" title="{'Cart'|translate}" onclick="if (typeof showCart === 'function'){ldelim}showCart();return false;{rdelim}">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
					{if $cart.count|default:0 > 0}
					<span class="fl-badge" id="cartCount">{$cart.count}</span>
					{else}
					<span class="fl-badge d-none" id="cartCount">0</span>
					{/if}
				</a>
			</div>
		</div>
	</div>

	<div class="fl-header__nav d-none d-lg-block">
		<div class="fl-container fl-header__nav-row">
			<nav class="fl-nav" aria-label="{'Categories'|translate}">
				{if $hooks.main_menu}
				<ul class="fl-nav__list fl-nav__list--hook">
					{$hooks.main_menu nofilter}
				</ul>
				{else}
				<ul class="fl-nav__list">
					{foreach $menuCategories as $cat name=flNav}
					{if $cat.id_parent == 1}
					<li>
						<a href="{$domain}{$cat.category_link|escape}" class="fl-nav__link{if isset($category) && $category.category_link == $cat.category_link} is-active{/if}">
							{$cat.category_name|escape}
						</a>
					</li>
					{if $smarty.foreach.flNav.iteration == 3}
					<li class="fl-nav__sep" aria-hidden="true"></li>
					{/if}
					{/if}
					{/foreach}
					<li><a href="{$domain}special" class="fl-nav__link{if $pageName == 'special'} is-active{/if}">{'Specilas'|translate}</a></li>
					<li><a href="{$domain}contact" class="fl-nav__link{if $pageName == 'contact'} is-active{/if}">{'Contact'|translate}</a></li>
				</ul>
				{/if}
			</nav>
			
				<a href="{$domain}truck" class="fl-pill-btn d-none d-md-inline-flex" title="{'Order Traking'|translate}">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>
					{'Order Traking'|translate}
				</a>
		</div>
	</div>
</header>
