<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<title>{if $pageTitle && $pageTitle != $siteName}{$pageTitle|escape} | {$siteName|escape}{else}{$siteName|escape}{/if}</title>
	{if $pageDesc}
	<meta name="description" content="{$pageDesc|escape}">
	<meta property="og:description" content="{$pageDesc|escape}">
	{/if}
	<meta property="og:title" content="{if $pageTitle && $pageTitle != $siteName}{$pageTitle|escape} | {$siteName|escape}{else}{$siteName|escape}{/if}">
	<meta property="og:type" content="website">
	<meta property="og:site_name" content="{$siteName|escape}">
	<meta name="application-name" content="{$siteName|escape}">
	<meta property="og:url" content="{$domain|escape}">
	<meta name="twitter:title" content="{if $pageTitle && $pageTitle != $siteName}{$pageTitle|escape} | {$siteName|escape}{else}{$siteName|escape}{/if}">
	{if $pageDesc}
	<meta name="twitter:description" content="{$pageDesc|escape}">
	{/if}
	<meta name="author" content="FriSay">
	<link rel="canonical" href="{$domain|escape}">
	<meta name="robots" content="index, follow">
	<meta name="publisher" content="{$siteName|escape}" />
	<meta name="language" content="{$selectLang}">
	<link rel="icon" type="image/x-icon" href="{$domain}img/favicon.ico?v=1">
	<meta name="theme-color" content="#fff7ef">
	<link rel="manifest" href="{$domain}manifest.json">
    <link href="{$css_dir}bootstrap.min.css" rel="stylesheet">
    <link href="{$css_dir}colors.css" rel="stylesheet">
    <link href="{$css_dir}custom.css?v={$minute}" rel="stylesheet">
    <link href="{$css_dir}app.css" rel="stylesheet">
    <link href="{$css_dir}cart-modal.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
	{if $css}
	<link rel="stylesheet" href="{$css_dir}{$css}" />
	{/if}
	{foreach $moduleAssets.css as $moduleCss}
	<link rel="stylesheet" href="{$moduleCss}" />
	{/foreach}
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
		var baseDir = '{$domain}';
		window.baseDir = '{$domain}';
		window.imgDir = '{$domain}img/';
		window.cssDir = '{$css_dir}';
		var productApiUrl = "{$domain}api/product.php";
		window.cartI18n = {$cartI18nJson nofilter};
	</script>
	<script type="application/ld+json">
	{
	  "@context": "https://schema.org",
	  "@type": "WebPage",
	  "url": "{$domain}",
	  "name": "{if $pageTitle && $pageTitle != $siteName}{$pageTitle|escape} | {$siteName|escape}{else}{$siteName|escape}{/if}",
	  "description": "{$pageDesc|escape}"
	},
	{
	 "@type":"SoftwareApplication",
	 "name":"FriSay",
	 "applicationCategory":"BusinessApplication"
	}
	</script>
	<script type="application/ld+json">
	{
	  "@context": "https://schema.org",
	  "@type": "Organization",
	  "name": "{$siteName}",
	  "legalName": "{$siteName}",
	  "url": "{$domain}",
	  "logo": "{$domain}img/logo.png",
	  "email": "{$contactEmail}",
	  "address": {
		"@type": "PostalAddress",
		"streetAddress": "{$contactAddress}",
		"addressLocality": "{$contactCity}",
		"postalCode": "{$postalCode}",
		"addressCountry": "{$addressCountry}"
	  },
	  "contactPoint": [
		{
		  "@type": "ContactPoint",
		  "telephone": "{$contactPhone}",
		  "contactType": "customer service",
		  "availableLanguage": ["{$selectLang}"]
		}
	  ],
	  "geo": {
		"@type": "GeoCoordinates",
		"latitude": {$latitude},
		"longitude": {$longitude}
	  },
	  "openingHoursSpecification": {
		"@type": "OpeningHoursSpecification",
		"dayOfWeek": [
		  "Monday",
		  "Tuesday",
		  "Wednesday",
		  "Thursday",
		  "Friday",
		  "Saturday",
		  "Sunday"
		],
		"opens": "{$openHour}",
		"closes": "{$closeHour}"
	  },
	  "sameAs": [
		{if $facebookLink}
			"{$facebookLink}",
		{/if}
		{if $xLink}
			"{$xLink}",
		{/if}
		{if $instagramLink}
			"{$instagramLink}"
		{/if}
		{if $youtubeLink}
			"{$youtubeLink}"
		{/if}
	  ]
	}
	</script>

	<script type="application/ld+json">
	{
	  "@context": "https://schema.org",
	  "@type": "WebSite",
	  "name": "{$siteName}", 
	  "url": "{$domain}",
	  "potentialAction": {
		"@type": "SearchAction",
		"target": "{$domain}search?query={literal}{search_term_string}{/literal}",
		"query-input": "required name=search_term_string"
	  }
	}
	</script>
</head>
<body id="{$pageName}">

<nav class="navbar navbar-expand-lg sticky-top">
	<div class="container">
		<a class="navbar-brand" href="{$domain}" title="{$siteName}">
			<i class="bi bi-egg-fried me-2"></i>{$siteName}
		</a>
		
		<form class="d-none d-md-flex search-bar mx-4 flex-grow-1" action="{$domain}search" method="get">
			<i class="bi bi-search text-muted"></i>
			<input type="search" name="q" id="query" placeholder="{'Search product..'|translate}" value="{$searchQuery|default:''|escape}">
		</form>

		<div class="d-flex align-items-center gap-3 position-relative">
			<div class="dropdown d-none d-sm-block">
				<button type="button" data-bs-toggle="dropdown" class="btn nav-btn btn-outline-dark dropdown-toggle">{'My Account'|translate}</button>
				<ul class="dropdown-menu dropdown-menu-end">
					{if $isLoggedIn}
						<li><a class="dropdown-item" title="{'My Account'|translate}" href="{$domain}my-account">{'My Account'|translate}</a></li>
					{else}
						<li><a class="dropdown-item" title="{'Login'|translate}" href="{$domain}login">{'Login'|translate}</a></li>
					{/if}
					<li><a class="dropdown-item" href="{$domain}favorites" title="{'My Favorites'|translate}">{'My Favorites'|translate}</a></li>
					<li><a class="dropdown-item" href="{$domain}orders" title="{'My Orders'|translate}">{'My Orders'|translate}</a></li>
					<li><a class="dropdown-item" href="{$domain}my-account#notifications" title="{'Notifications'|translate}">{'Notifications'|translate} ({$notificationCount})</a></li>
					<li><a class="dropdown-item" href="{$domain}my-account#addresses" title="{'My Addresses'|translate}">{'My Addresses'|translate}</a></li>
					{if $isLoggedIn}
						<li><hr class="dropdown-divider"></li>
						<li><a class="dropdown-item" href="{$domain}login?exit=1" title="{'Logout'|translate}">{'Logout'|translate}</a></li>
					{/if}
				</ul>
			</div>
			<button type="button" class="btn nav-btn btn-cart cart-open-btn">
				<i class="bi bi-cart3 me-2"></i>{if $cart.total_formatted}{$cart.total_formatted}{else}{Tools::displayPrice(0)}{/if}
			</button>
		</div>
	</div>
</nav>
{if $pageName != 'home'}<div class="container mt-3">{/if}