<header class="header-wrapper">
	<div class="top-bar">
		<div class="container d-flex align-items-center justify-content-between">
			<ul class="top-menu">
				<li class="top-menu-item">{include file='blue/plugin/lang-switcher.tpl'}</li>
				<li class="top-menu-item"><a href="{$domain}contact" title="{'Contact Us'|translate}">{'Contact Us'|translate}</a></li>
				<li class="top-menu-item"><a href="{$domain}truck" title="{'Order Traking'|translate}">{'Order Traking'|translate}</a></li>
			</ul>
			<button class="mobile-menu-toggle" type="button" data-bs-toggle="offcanvas" href="#primeMobileMenu" role="button" aria-controls="primeMobileMenu">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu-icon lucide-menu"><path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h16"/></svg>
			</button>
			<div class="logo ps-2 mobileLogoDiv">
				<a href="{$domain}" class="site-logo-img mobileLogo" title="{$siteName}">
					<img src="{$domain}img/logo.png" alt="{$siteName}"/>
				</a>
			</div>
			<div class="top-actions">
				<button class="action-icon" onclick="toggleSearch()">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
						<path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
					</svg>
				</button>
				{include file='blue/plugin/notifications-dropdown.tpl'}
				<a class="action-icon" title="{'My Account'|translate}" href="{$domain}my-account">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
						<path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
					</svg>
				</a>
				<a href="{$domain}cart" class="action-icon" title="{'Cart'|translate}">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-basket-icon lucide-shopping-basket"><path d="m15 11-1 9"/><path d="m19 11-4-7"/><path d="M2 11h20"/><path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.7-7.4"/><path d="M4.5 15.5h15"/><path d="m5 11 4-7"/><path d="m9 11 1 9"/></svg>
					{if $cart.count|default:0 > 0}<span class="cart-badge" id="cartCount">{$cart.count}</span>{else}<span class="cart-badge d-none" id="cartCount">0</span>{/if}
				</a>
			</div>
		</div>
		<div class="search-overlay" id="searchOverlay">
			<div class="container">
				<form action="{$domain}search" method="POST">
					<div class="d-flex align-items-center gap-3">
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search-icon lucide-search"><path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/></svg>
						<input type="text" name="query" class="form-control border-0 bg-transparent fs-5 fw-light" placeholder="{'Search product..'|translate}" style="outline: none; box-shadow: none; font-family: var(--font-main);">
						<button class="btn btn-dark btn-sm" name="search">{'Search'|translate}</button>
						<input type="hidden" name="csf" value="{$token}" />
					</div>
				</form>
			</div>
		</div>
	</div>
	<div class="divider"></div>

	<div class="main-bar position-relative">
		<div class="container d-flex align-items-center justify-content-between w-full">
			<div class="logo-area">
				<a href="{$domain}" class="site-logo-img" title="{$siteName}">
					<img src="{$domain}img/logo.png" alt="{$siteName}" width="60%"/>
				</a>
			</div>
			<ul class="categories-menu d-none d-xl-flex">
				{if $hooks.main_menu}
					{$hooks.main_menu nofilter}
				{else}
				<li class="category-item"><a href="{$domain}" class="desktop-nav-link">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-house-icon lucide-house"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
					{'Home Page'|translate}
				</a></li>
				{foreach $menuCategories as $cat}
					<li class="category-item"><a href="{$domain}{$cat.category_link}" class="desktop-nav-link{if isset($category) && $category.category_link == $cat.category_link} active{/if}">{$cat.category_name|escape}</a></li>
				{/foreach}
				<li class="category-item"><a href="{$domain}special" class="desktop-nav-link{if $pageName == 'special'} active{/if}">{'Specilas'|translate}</a></li>
				<li class="category-item"><a href="{$domain}contact" class="desktop-nav-link{if $pageName == 'contact'} active{/if}">{'Contact Us'|translate}</a></li>
				{/if}
			</ul>
		</div>
	</div>

</header>