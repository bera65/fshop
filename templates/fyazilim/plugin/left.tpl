<div class="fy-drawer">
	<div class="fy-drawer__hero">
		<div class="fy-drawer__hero-top">
			<a href="{$domain}my-account" class="fy-drawer__brand" title="{$siteName|escape}">
				<span class="fy-drawer__brand-mark">Fri<span>say</span></span>
			</a>
			<button type="button" class="fy-drawer__close" data-bs-dismiss="offcanvas" aria-label="Kapat">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
			</button>
		</div>
		{if $isLoggedIn}
		<p class="fy-drawer__hello">Merhaba{if $customer.user_full_name}, {$customer.user_full_name|escape|truncate:18}{/if}</p>
		{else}
		<p class="fy-drawer__hello">Hoş geldiniz</p>
		{/if}
	</div>

	<nav class="fy-drawer__nav" aria-label="{'Menu'|translate}">
		<p class="fy-drawer__section">Keşfet</p>
		<a href="{$domain}" class="fy-drawer__link{if $pageName == 'home'} is-active{/if}">
			<span class="fy-drawer__ico" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
			</span>
			<span>{'Home Page'|translate}</span>
		</a>
		<a href="{$domain}special" class="fy-drawer__link{if $pageName == 'special'} is-active{/if}">
			<span class="fy-drawer__ico" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/></svg>
			</span>
			<span>{'Specilas'|translate}</span>
		</a>

		{if $mainMenuItems|default:[]|@count > 0}
		<p class="fy-drawer__section">Menü</p>
		{foreach $mainMenuItems as $item}
		{if $item.children|default:[]|@count > 0}
		<div class="mm-m-group">
			<div class="mm-m-head">
				<a href="{$item.url|escape}" class="fy-drawer__link" {if $item.target == '_blank'}target="_blank" rel="noopener"{/if}>
					<span class="fy-drawer__ico fy-drawer__ico--soft" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
					</span>
					<span>{$item.label|escape}</span>
				</a>
				<button type="button" class="mm-m-toggle" data-mm-m-toggle aria-expanded="false" aria-label="{$item.label|escape} alt menü">
					<span class="mm-caret" aria-hidden="true"></span>
				</button>
			</div>
			<div class="mm-m-children">
				{foreach $item.children as $child}
				<a href="{$child.url|escape}" class="fy-drawer__link fy-drawer__link--child">
					<span class="fy-drawer__ico fy-drawer__ico--soft" aria-hidden="true"></span>
					<span>{$child.label|escape}</span>
				</a>
				{/foreach}
			</div>
		</div>
		{else}
		<a href="{$item.url|escape}" class="fy-drawer__link" {if $item.target == '_blank'}target="_blank" rel="noopener"{/if}>
			<span class="fy-drawer__ico fy-drawer__ico--soft" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
			</span>
			<span>{$item.label|escape}</span>
		</a>
		{/if}
		{/foreach}
		{elseif $menuCategories|@count > 0}
		<p class="fy-drawer__section">Kategoriler</p>
		{foreach $menuCategories as $cat}
		<a href="{$domain}{$cat.category_link}" class="fy-drawer__link{if isset($category) && $category.category_link == $cat.category_link} is-active{/if}">
			<span class="fy-drawer__ico fy-drawer__ico--soft" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
			</span>
			<span>{$cat.category_name|escape}</span>
		</a>
		{/foreach}
		{/if}

		<p class="fy-drawer__section">Hesabım</p>
		{if $isLoggedIn}
		<a href="{$domain}orders" class="fy-drawer__link{if $pageName == 'orders' || $pageName == 'order'} is-active{/if}">
			<span class="fy-drawer__ico fy-drawer__ico--soft" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect width="8" height="4" x="8" y="2" rx="1"/><path d="M9 14h6"/><path d="M9 18h6"/></svg>
			</span>
			<span>{'My Orders'|translate}</span>
		</a>
		<a href="{$domain}favorites" class="fy-drawer__link{if $pageName == 'favorites'} is-active{/if}">
			<span class="fy-drawer__ico fy-drawer__ico--soft" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
			</span>
			<span>{'Favorites'|translate}</span>
		</a>
		<a href="{$domain}my-account" class="fy-drawer__link{if $pageName == 'my-account'} is-active{/if}">
			<span class="fy-drawer__ico fy-drawer__ico--soft" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
			</span>
			<span>{'My Account'|translate}</span>
		</a>
		{else}
		<a href="{$domain}register" class="fy-drawer__link" data-auth-open="register">
			<span class="fy-drawer__ico fy-drawer__ico--soft" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
			</span>
			<span>{'Sign Up'|translate}</span>
		</a>
		{/if}
		<a href="{$domain}contact" class="fy-drawer__link{if $pageName == 'contact'} is-active{/if}">
			<span class="fy-drawer__ico fy-drawer__ico--soft" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
			</span>
			<span>{'Contact Us'|translate}</span>
		</a>
		<a href="{$domain}truck" class="fy-drawer__link{if $pageName == 'truck'} is-active{/if}">
			<span class="fy-drawer__ico fy-drawer__ico--soft" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
			</span>
			<span>{'Order Traking'|translate}</span>
		</a>

		{if $cmsFooterLinks|@count > 0}
		<p class="fy-drawer__section">Sayfalar</p>
		{foreach $cmsFooterLinks as $cmsLink}
		<a href="{$cmsLink.url}" class="fy-drawer__link">
			<span class="fy-drawer__ico fy-drawer__ico--soft" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
			</span>
			<span>{$cmsLink.title|escape}</span>
		</a>
		{/foreach}
		{/if}

		{if $hooks.mobile_menu}
		{$hooks.mobile_menu nofilter}
		{/if}
	</nav>

	<div class="fy-drawer__foot">
		{if $contactPhone}
		<a class="fy-drawer__phone" href="tel:{$contactPhoneTel|default:$contactPhone|escape}">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
			{$contactPhone|escape}
		</a>
		{/if}
		<div class="fy-drawer__lang">
			{include file='fyazilim/plugin/lang-switcher-pills.tpl'}
		</div>
	</div>
</div>
