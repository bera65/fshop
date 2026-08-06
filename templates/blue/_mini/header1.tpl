<div id="header">
<div class="container">
	<!-- Mobile -->
	<div class="mobile-logo-area d-flex d-lg-none">
		<div class="header-actions">
			<div data-bs-toggle="offcanvas" href="#primeMobileMenu" role="button" aria-controls="primeMobileMenu">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu-icon lucide-menu"><path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h16"/></svg>
			</div>
			
			<a href="{$domain}" class="site-logo-img" title="{$siteName}">
				<img src="{$domain}img/logo.png" alt="{$siteName}" width="60%"/>
			</a>
		</div>
		<div class="header-actions">
			{include file='blue/plugin/notifications-dropdown.tpl'}
			<a href="{$domain}my-account" class="action-item" title="{'My Account'|translate}">
				<div class="icon-box">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
				</div>
			</a>
			<a href="{$domain}cart" class="action-item" title="{'Cart'|translate}">
				<div class="icon-box">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-bag-icon lucide-shopping-bag"><path d="M16 10a4 4 0 0 1-8 0"/><path d="M3.103 6.034h17.794"/><path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"/></svg>
					{if $cart.count|default:0 > 0}
					<em class="prime-mobile-header__badge prime-tool__badge">{$cart.count}</em>
					{/if}
				</div>
			</a>
		</div>
	</div>

	<!-- Desktop -->
	<div class="row align-items-center d-none d-lg-flex">
		<div class="col-lg-3 d-flex gap-3">
			<a href="{$domain}" class="site-logo-img" title="{$siteName}">
				<img src="{$domain}img/logo.png" alt="{$siteName}" width="60%"/>
			</a>
		</div>
		<div class="col-lg-6 d-flex justify-content-center">
			<div class="search-container">
			<form autocomplete="off" class="d-flex flex-grow-1" action="{$domain}search" method="POST">
				<span class="search-icon-fixed">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
				</span>
				<input type="text" name="search" id="query" placeholder="{'Search product..'|translate}">
			</form>
			<div id="sonuc"></div>
			</div>
		</div>
		<div class="col-lg-3 d-flex justify-content-end">
			<div class="header-actions">
				{include file='blue/plugin/notifications-dropdown.tpl'}
				<div data-bs-toggle="dropdown" class="action-item dropdown-toggle cursorPointer">
					<div class="icon-box">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
					</div>
					<span class="d-none d-xl-inline">{'My Account'|translate}</span>
				</div>
				<a href="{$domain}cart" class="action-item" title="{'Cart'|translate}">
					<div class="icon-box">
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-bag-icon lucide-shopping-bag"><path d="M16 10a4 4 0 0 1-8 0"/><path d="M3.103 6.034h17.794"/><path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"/></svg>
					</div>
					<span class="d-none d-xl-inline">{if $cart.total_formatted}{$cart.total_formatted}{else}{Tools::displayPrice(0)}{/if}</span>
				</a>
				  <ul class="dropdown-menu">
					{if $isLoggedIn}
						<li><a class="dropdown-item" title="{'My Account'|translate}" href="{$domain}my-account">{'My Account'|translate}</a></li>
					{else}
						<li><a class="dropdown-item" title="{'Login'|translate}" href="{$domain}login">{'Login'|translate}</a></li>
					{/if}
					<li><a class="dropdown-item" href="{$domain}login" title="{'My Favorites'|translate}">{'My Favorites'|translate}</a></li>
					<li><a class="dropdown-item" href="{$domain}my-account" title="{'My Orders'|translate}">{'My Orders'|translate}</a></li>
					<li><a class="dropdown-item" href="{$domain}my-account#notifications" title="{'Notifications'|translate}">{'Notifications'|translate} ({$notificationCount})</a></li>
					<li><a class="dropdown-item" href="{$domain}my-account#addresses" title="{'My Addresses'|translate}">{'My Addresses'|translate}</a></li>
					{if $isLoggedIn}
						<li><hr class="dropdown-divider"></li>
						<li><a class="dropdown-item" href="{$domain}login?exit=1" title="{'Logout'|translate}">{'Logout'|translate}</a></li>
					{/if}
				  </ul>
			</div>
		</div>
	</div>

	<!-- Mobil Arama Satırı (Mavi Bar) -->
	<div class="mobile-search-row d-lg-none">
		<div class="search-container">
			<span class="search-icon-fixed">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
			</span>
			<input type="text" placeholder="{'Search product..'|translate}">
		</div>
	</div>
</div>
</div>

<div class="menu-card-wrapper container">
<div id="menu" class="menu-card">
	<ul class="nav-list">
		{if $hooks.main_menu}
			{$hooks.main_menu nofilter}
		{else}
		<li><a href="{$domain}" class="desktop-nav-link{if $pageName == 'home'} active{/if}">{'Home Page'|translate}</a></li>
		{foreach $menuCategories as $cat}
			<li><a href="{$domain}{$cat.category_link}" class="desktop-nav-link{if isset($category) && $category.category_link == $cat.category_link} active{/if}">{$cat.category_name|escape}</a></li>
		{/foreach}
		<li><a href="{$domain}special" class="desktop-nav-link{if $pageName == 'special'} active{/if}">{'Specilas'|translate}</a></li>
		<li><a href="{$domain}contact" class="desktop-nav-link{if $pageName == 'contact'} active{/if}">{'Contact'|translate}</a></li>
		{/if}
	</ul>
</div>
</div>