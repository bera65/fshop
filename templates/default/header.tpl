<!DOCTYPE>
<html xmlns="http://www.w3.org/1999/xhtml" lang="tr">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
	<title>{if $pageTitle && $pageTitle != $siteName}{$pageTitle|escape} | {$siteName|escape}{else}{$siteName|escape}{/if}</title>
	{if $pageDesc}
	<meta name="description" content="{$pageDesc|escape}">
	<meta property="og:title" content="{if $pageTitle}{$pageTitle|escape}{else}{$siteName|escape}{/if}">
	<meta property="og:description" content="{$pageDesc|escape}">
	<meta property="og:type" content="website">
	{/if}
	{include file='./plugin/schema-jsonld.tpl'}
	<link rel="stylesheet" href="{$css_dir}bootstrap.min.css" />
	<link rel="stylesheet" href="{$css_dir}app.css?v={$minute}" />
	<link rel="stylesheet" href="{$css_dir}colors.css?v={$minute}" />
	{if $css}
	<link rel="stylesheet" href="{$css_dir}{$css}?v={$minute}" />
	{/if}
	{foreach $moduleAssets.css as $moduleCss}
	<link rel="stylesheet" href="{$moduleCss}?v={$minute}" />
	{/foreach}
	<link rel="icon" type="image/x-icon" href="{$domain}img/favicon.ico">
	<meta name="theme-color" content="#000000">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel=stylesheet>
	<script src="{$js_dir}jquery-3.2.1.min.js"></script>
	<link rel="manifest" href="{$domain}manifest.json">
	<meta name="mobile-web-app-capable" content="yes">
	<link rel="apple-touch-icon" href="{$img_dir}favicon.png">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="apple-mobile-web-app-title" content="{$siteName}">
	<script>
		if ('serviceWorker' in navigator) {
			window.addEventListener('load', () => {
				navigator.serviceWorker.register('{$domain}sw.js');
			});
		}
		var domain = "{$domain}";
		var csrfToken = "{$token}";
		var cartApiUrl = "{$domain}api/cart.php";
		var couponApiUrl = "{$domain}api/coupon.php";
		var authApiUrl = "{$domain}api/auth.php";
		var favoriteApiUrl = "{$domain}api/favorite.php";
		var accountApiUrl = "{$domain}api/account.php";
		var isLoggedIn = {if $isLoggedIn}true{else}false{/if};
	</script>
</head>
<body id="{$pageName}">
<div id="header" class="d-none d-lg-block">
	<div class="container site-container">
		<div class="row align-items-center">
			<div class="col-3">
				<ul class="p-0 m-0">
					<li class="list-inline-item">
						<a href="tel:{$contactPhoneTel|escape}">
							<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone-icon lucide-phone"><path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"/></svg>
							<span class="d-none d-lg-inline ms-1">{$contactPhone|escape}</span>
						</a>
					</li>
				</ul>
			</div>
			<div class="col-6 text-center">
				<a href="{$domain}special" class="d-inline-block" style="font-size:12px; letter-spacing:.08em; text-transform:uppercase; color:#fff; text-decoration:none; padding:.55rem 0;">
					{$freeShippingMin|escape} TL üzeri kargo bedava — Kampanyaları keşfet
				</a>
			</div>
			<div class="col-3">
				<ul class="p-0 m-0 text-right">
					<li class="list-inline-item">
						<a href="{$domain}truck" title="Kargo Takip">
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-truck-icon lucide-truck"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
							<span class="ms-1">Kargo Takip</span>
						</a>
					</li>
					<li class="list-inline-item ms-3">
						<a href="{$domain}contact">
							<span>Yardım</span>
						</a>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>
<div class="header2 w-100">
	<div id="headerMenu" class="container site-container d-none d-lg-block">
	  <div class="row align-items-center">
		<div class="col-5 col-md-3">
		  <a href="{$domain}" title="{$siteName}" class="d-block">
			<img src="{$siteLogos.header|escape}?v={$minute}" class="img-fluid" alt="{$siteName}" width="200px" height="auto" />
		  </a>
		</div>

		<div class="col-md-5 col-xl-5 d-none d-md-block">
			<form class="position-relative search-form-premium" action="{$domain}search" method="get">
				<input type="text" name="q" class="form-control" placeholder="Sitede Ara.." value="{$searchQuery|default:''|escape}"/>
				<button type="submit" class="btn btn-sm btn-dark searchButton">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search-icon lucide-search"><path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/></svg>
				</button>
			</form>
		</div>

		<!-- SAĞ TARAF -->
		<div class="col-7 col-md-4 col-xl-4 d-flex justify-content-end align-items-center">
			{include file='./plugin/headerTools.tpl'}
		</div>
	  </div>
	</div>
</div>
<nav id="desktopNav" class="d-none d-lg-block" aria-label="Ana menü">
	<div class="container site-container">
		<ul class="desktop-nav-list">
			<li><a href="{$domain}" class="desktop-nav-link{if $pageName == 'home'} active{/if}">Ana Sayfa</a></li>
			{foreach $menuCategories as $cat}
			<li><a href="{$domain}{$cat.category_link}" class="desktop-nav-link{if isset($category) && $category.category_link == $cat.category_link} active{/if}">{$cat.category_name|escape}</a></li>
			{/foreach}
			<li><a href="{$domain}special" class="desktop-nav-link{if $pageName == 'special'} active{/if}">Kampanyalar</a></li>
			<li><a href="{$domain}contact" class="desktop-nav-link{if $pageName == 'contact'} active{/if}">İletişim</a></li>
		</ul>
	</div>
</nav>
<div id="appHeader">
  <div class="header-inner">
    <button class="icon-btn" data-bs-toggle="offcanvas" href="#leftMenu" role="button" aria-controls="leftMenu">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu-icon lucide-menu"><path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h16"/></svg>
    </button>
    <a href="{$domain}" class="logo">
      <img src="{$siteLogos.bar|escape}?v={$minute}" alt="">
      <span>fyazilim</span>
    </a>
    <div class="header-actions">
      <a href="{$domain}favoriler" class="icon-btn" title="Favorilerim">
		<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"/></svg>
	  </a>
      {if $isLoggedIn}
      <a href="{$domain}hesabim" class="icon-btn" title="Hesabım">
		<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-icon lucide-user"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
	  </a>
      {else}
      <a class="icon-btn" href="{$domain}login" title="Giriş Yap">
		<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
	  </a>
      {/if}
      <a href="#" onclick="showCart(); return false;" class="icon-btn position-relative" title="Sepetim">
		<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
		<span id="mobileCartBadge" class="header-tool__badge{if $cart.count == 0} d-none{/if}">{$cart.count}</span>
	  </a>
    </div>
  </div>
  <div id="searchDiv" class="mt-3 search-form-premium">
	<form action="{$domain}search" method="get">
		<input type="text" name="q" class="form-control" placeholder="Sitede Ara.." value="{$searchQuery|default:''|escape}"/>
		<button type="submit" class="btn btn-sm btn-dark searchButton">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search-icon lucide-search"><path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/></svg>
		</button>
	</form>
  </div>
</div>
<div class="containerDiv"></div>

<div class="offcanvas offcanvas-start custom-menu" tabindex="-1" id="leftMenu">
  {include file='./plugin/left.tpl'}
</div>
{if $pageName == 'home'}
<div id="slide" class="container">
	{if $hooks.home_slider}
	{$hooks.home_slider nofilter}
	{else}
	<div class="container site-container">
		{include file='./plugin/slide.tpl'}
	</div>
	{/if}
</div>
{/if}
<div class="container site-container">
{include file='./plugin/cart.tpl'}
{if $pageName != 'home' && $breadcrumb}
<!-- Breadcrumb -->
	<nav aria-label="breadcrumb" class="mt-3 mb-3">
		<ol class="breadcrumb">
			{foreach $breadcrumb as $crumb name=crumbs}
			<li class="breadcrumb-item{if $smarty.foreach.crumbs.last} active{/if}"{if $smarty.foreach.crumbs.last} aria-current="page"{/if}>
				{if $crumb.url && !$smarty.foreach.crumbs.last}
					<a href="{$crumb.url}">{$crumb.name}</a>
				{else}
					{$crumb.name}
				{/if}
			</li>
			{/foreach}
		</ol>
	</nav>
{/if}