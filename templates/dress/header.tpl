<!DOCTYPE html>
<html lang="{$selectLang|escape}">
<head>
	<meta charset="utf-8">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<title>{$documentTitle|default:$siteName|escape}</title>
	{if $pageDesc}
	<meta name="description" content="{$pageDesc|escape}">
	<meta property="og:description" content="{$pageDesc|escape}">
	{/if}
	<meta property="og:title" content="{$documentTitle|default:$siteName|escape}">
	<meta property="og:type" content="website">
	<meta property="og:site_name" content="{$siteName|escape}">
	<meta name="application-name" content="{$siteName|escape}">
	<meta property="og:url" content="{$domain|escape}">
	<meta name="twitter:title" content="{$documentTitle|default:$siteName|escape}">
	{if $pageDesc}
	<meta name="twitter:description" content="{$pageDesc|escape}">
	{/if}
	<meta name="author" content="FriSay">
	<link rel="canonical" href="{$domain|escape}">
	<meta name="robots" content="index, follow">
	<meta name="publisher" content="{$siteName|escape}" />
	<meta name="language" content="{$selectLang}">
	<link rel="icon" type="image/x-icon" href="{$domain}img/favicon.ico?v=1">
	{if $lcpImage}
	<link rel="preload" as="image" href="{$lcpImage|escape}" fetchpriority="high">
	{/if}

	{if $hooks.head.top}
	{$hooks.head.top nofilter}
	{else}
	<meta name="theme-color" content="#5C4033">
	<link rel="manifest" href="{$domain}manifest.php">
	<script>
		if ('serviceWorker' in navigator) {
			window.addEventListener('load', () => {
				navigator.serviceWorker.register({($domain|cat:'sw.php')|js nofilter});
			});
		}
	</script>
	{/if}
	<meta name="viewport" content="width=device-width, minimum-scale=0.25, maximum-scale=2, initial-scale=1.0">
	{if $themeOptions.google_font_url}
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link rel="stylesheet" href="{$themeOptions.google_font_url|escape}">
	{/if}
	{include file='./plugin/theme-stylesheets.tpl'}
	{foreach $moduleAssets.css as $moduleCss}
	<link rel="stylesheet" href="{$moduleCss}" />
	{/foreach}

	<script>
		var domain = {$domain|js nofilter};
		var csrfToken = {$token|js nofilter};
		window.csrfToken = csrfToken;
		var cartApiUrl = domain + 'api/cart.php';
		var couponApiUrl = domain + 'api/coupon.php';
		var authApiUrl = domain + 'api/auth.php';
		var favoriteApiUrl = domain + 'api/favorite.php';
		var accountApiUrl = domain + 'api/account.php';
		var isLoggedIn = {if $isLoggedIn}true{else}false{/if};
		var baseDir = domain;
		window.baseDir = domain;
		window.imgDir = domain + 'img/';
		window.cssDir = {$css_dir|js nofilter};
		window.searchSuggestUrl = domain + 'api/search-suggest.php';
	</script>

</head>
<body id="{$pageName}" class="fl-body sm-body prime-body">
{if !isset($authMode)}
<div class="offcanvas offcanvas-start prime-mobile-menu" tabindex="-1" id="primeMobileMenu" aria-labelledby="primeMobileMenuLabel">
	<div class="offcanvas-body p-0">
		{include file='./plugin/left.tpl'}
	</div>
</div>
{include file="./_mini/{$themeOptions.header|default:'header1'}.tpl"}
{elseif $authMode != 'gate' && $authMode != 'login' && $authMode != 'register' && $authMode != 'forgot' && $authMode != 'reset'}
<header class="auth-simple-header text-center py-4 mt-3">
	{if $siteLogos.header}
	<a href="{$domain}">
		<img src="{$siteLogos.header|escape}" alt="{$siteName|escape}" style="max-height: 60px;">
	</a>
	{/if}
</header>
{/if}
{if isset($authMode) && ($authMode == 'gate' || $authMode == 'login' || $authMode == 'register' || $authMode == 'forgot' || $authMode == 'reset')}
{else}
<section class="py-2 page" role="main">
{if $pageName != 'home'}
<div class="sm-container sm-page-wrap">
{include file='./partials/breadcrumb.tpl'}
{/if}
{/if}