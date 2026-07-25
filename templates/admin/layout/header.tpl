<!DOCTYPE html>
<html lang="{$adminLang|escape}">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{if $pageTitle}{$pageTitle|escape} | {/if}Admin — {$siteName|escape}</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="{$adminCssDir|default:''}bootstrap.min.css">
	<link rel="stylesheet" href="{$adminCssDir|default:''}admin.css?v={$smarty.now}">
	{if $pageName == 'marketplace-products' || $pageName == 'marketplace-orders' || $pageName == 'marketplace-questions' || $pageName == 'marketplace-settings'}
	<link rel="stylesheet" href="{$adminCssDir|default:''}marketplace.css?v={$smarty.now}">
	{/if}
	{if $marketplaceAdminAssets.css|@count}
	{foreach $marketplaceAdminAssets.css as $marketplaceCss}
	<link rel="stylesheet" href="{$marketplaceCss}?v={$smarty.now}">
	{/foreach}
	{/if}
	{if $moduleAdminAssets.css|@count}
	{foreach $moduleAdminAssets.css as $moduleCss}
	<link rel="stylesheet" href="{$moduleCss}?v={$smarty.now}">
	{/foreach}
	{/if}
	<link rel="icon" type="image/x-icon" href="{$domain}img/faviconAdmin.ico">
</head>
<body class="admin-body">
<div class="ps-admin" id="psAdmin">
	<aside class="sidebar" id="adminSidebar" aria-label="{'Admin Panel'|adminT}">
		<div class="sidebar-header">
			<a href="{$adminUrl}dashboard" class="sidebar-brand">
				<span class="sidebar-brand__logo">
					<img src="{$adminLogoUrl|escape}" alt="{$siteName|escape}" />
				</span>
				<span class="sidebar-brand__text">
					<strong>{$siteName|escape}</strong>
					<small>FriSay</small>
				</span>
			</a>
		</div>

		<nav class="sidebar-menu">
			<div class="menu-title">{'General'|adminT}</div>
			<a href="{$adminUrl}dashboard" class="menu-item {if $pageName == 'dashboard'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="layout-dashboard"></i></span>
				<span class="menu-item__label">{'Dashboard'|adminT}</span>
			</a>
			<a href="{$adminUrl}customers" class="menu-item {if $pageName == 'customers' || $pageName == 'customer'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="users"></i></span>
				<span class="menu-item__label">{'Customers'|adminT}</span>
			</a>
			<a href="{$adminUrl}messages" class="menu-item {if $pageName == 'messages' || $pageName == 'message'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="message-square"></i></span>
				<span class="menu-item__label">{'Messages'|adminT}</span>
				{if $adminNavBadges.messages > 0}<span class="nav-badge nav-badge--green">{$adminNavBadges.messages}</span>{/if}
			</a>
			<a href="{$adminUrl}notifications" class="menu-item {if $pageName == 'notifications'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="bell"></i></span>
				<span class="menu-item__label">{'Notifications'|adminT}</span>
				{if $adminNavBadges.notifications > 0}<span class="nav-badge bg-warning">{$adminNavBadges.notifications}</span>{/if}
			</a>
			{if $adminMenuItems.general|@count}
			{include file='admin/layout/admin-menu-hook-items.tpl' hookMenuItems=$adminMenuItems.general}
			{/if}
			
			<div class="menu-title">{'Sales'|adminT}</div>
			<a href="{$adminUrl}orders" class="menu-item {if $pageName == 'orders' || $pageName == 'order'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="shopping-bag"></i></span>
				<span class="menu-item__label">{'Orders'|adminT}</span>
				{if $adminNavBadges.orders > 0}<span class="nav-badge bg-dark">{$adminNavBadges.orders}</span>{/if}
			</a>
			<a href="{$adminUrl}returns" class="menu-item {if $pageName == 'returns' || $pageName == 'return'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="undo-2"></i></span>
				<span class="menu-item__label">{'Returns'|adminT}</span>
				{if $adminNavBadges.returns > 0}<span class="nav-badge bg-danger">{$adminNavBadges.returns}</span>{/if}
			</a>
			<a href="{$adminUrl}cancellations" class="menu-item {if $pageName == 'cancellations' || $pageName == 'cancel'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="x-circle"></i></span>
				<span class="menu-item__label">{'Cancellations'|adminT}</span>
				{if $adminNavBadges.cancellations > 0}<span class="nav-badge">{$adminNavBadges.cancellations}</span>{/if}
			</a>
			<a href="{$adminUrl}coupons" class="menu-item {if $pageName == 'coupons' || $pageName == 'coupon' || $pageName == 'cart-promotion'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="ticket-percent"></i></span>
				<span class="menu-item__label">{'Coupons'|adminT}</span>
			</a>
			<a href="{$adminUrl}cargos" class="menu-item {if $pageName == 'cargos'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="truck"></i></span>
				<span class="menu-item__label">{'Shipping'|adminT}</span>
			</a>
			<a href="{$adminUrl}stock-analysis" class="menu-item {if $pageName == 'stock-analysis'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="bar-chart-3"></i></span>
				<span class="menu-item__label">{'Stock Analysis'|adminT}</span>
			</a>

			<div class="menu-title">{'Catalog'|adminT}</div>
			<a href="{$adminUrl}products" class="menu-item {if $pageName == 'products' || $pageName == 'product'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="package"></i></span>
				<span class="menu-item__label">{'Products'|adminT}</span>
			</a>
			<a href="{$adminUrl}categories" class="menu-item {if $pageName == 'categories' || $pageName == 'category'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="folder-tree"></i></span>
				<span class="menu-item__label">{'Categories'|adminT}</span>
			</a>
			<a href="{$adminUrl}brands" class="menu-item {if $pageName == 'brands' || $pageName == 'brand'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="tag"></i></span>
				<span class="menu-item__label">{'Brands'|adminT}</span>
			</a>
			<a href="{$adminUrl}cms" class="menu-item {if $pageName == 'cms' || $pageName == 'cms-edit'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="file-text"></i></span>
				<span class="menu-item__label">{'Pages'|adminT}</span>
			</a>
			<a href="{$adminUrl}languages" class="menu-item {if $pageName == 'languages'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="languages"></i></span>
				<span class="menu-item__label">{'Languages'|adminT}</span>
			</a>
			<a href="{$adminUrl}translations" class="menu-item {if $pageName == 'translations'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="book-a"></i></span>
				<span class="menu-item__label">{'UI Translations'|adminT}</span>
			</a>
			<a href="{$adminUrl}currencies" class="menu-item {if $pageName == 'currencies'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="coins"></i></span>
				<span class="menu-item__label">{'Currencies'|adminT}</span>
			</a>
			<a href="{$adminUrl}taxes" class="menu-item {if $pageName == 'taxes'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="percent"></i></span>
				<span class="menu-item__label">{'Taxes'|adminT}</span>
			</a>
			<a href="{$adminUrl}seo" class="menu-item {if $pageName == 'seo'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="search"></i></span>
				<span class="menu-item__label">{'SEO'|adminT}</span>
			</a>
			{if $adminMenuItems.catalog|@count}
			{include file='admin/layout/admin-menu-hook-items.tpl' hookMenuItems=$adminMenuItems.catalog}
			{/if}
			<div class="menu-title">{'Marketplace'|adminT}</div>
			<a href="{$adminUrl}marketplace-products" class="menu-item {if $pageName == 'marketplace-products'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="store"></i></span>
				<span class="menu-item__label">{'Products'|adminT}</span>
			</a>
			<a href="{$adminUrl}marketplace-orders" class="menu-item {if $pageName == 'marketplace-orders'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="shopping-cart"></i></span>
				<span class="menu-item__label">{'Orders'|adminT}</span>
			</a>
			<a href="{$adminUrl}marketplace-questions" class="menu-item {if $pageName == 'marketplace-questions'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="message-circle-question"></i></span>
				<span class="menu-item__label">{'Questions'|adminT}</span>
			</a>
			<a href="{$adminUrl}marketplace-settings" class="menu-item {if $pageName == 'marketplace-settings'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="settings"></i></span>
				<span class="menu-item__label">{'Settings'|adminT}</span>
			</a>
			{if $adminMenuItems.marketplace|@count}
			{include file='admin/layout/admin-menu-hook-items.tpl' hookMenuItems=$adminMenuItems.marketplace}
			{/if}
			
			<div class="menu-title">{'System'|adminT}</div>
			{if $adminMenuItems.system|@count}
			{include file='admin/layout/admin-menu-hook-items.tpl' hookMenuItems=$adminMenuItems.system}
			{/if}
			{if $adminMenuItems.sales|@count}
			{include file='admin/layout/admin-menu-hook-items.tpl' hookMenuItems=$adminMenuItems.sales}
			{/if}
			
			<a href="{$adminUrl}modules" class="menu-item {if $moduleNavActive}active{/if}">
				<span class="menu-item__icon"><i data-lucide="blocks"></i></span>
				<span class="menu-item__label">{'Modules'|adminT}</span>
			</a>
			<a href="https://frisay.com/modules" target="_blank" rel="noopener" class="menu-item">
				<span class="menu-item__icon"><i data-lucide="store"></i></span>
				<span class="menu-item__label">{'Module Store'|adminT}</span>
			</a>
			<a href="{$adminUrl}templates" class="menu-item {if $pageName == 'templates'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="palette"></i></span>
				<span class="menu-item__label">{'Themes'|adminT}</span>
			</a>
			<a href="{$adminUrl}settings" class="menu-item {if $pageName == 'settings'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="settings"></i></span>
				<span class="menu-item__label">{'Settings'|adminT}</span>
			</a>
			<a href="{$adminUrl}account" class="menu-item {if $pageName == 'account'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="user-cog"></i></span>
				<span class="menu-item__label">{'My Account'|adminT}</span>
			</a>
			<a href="{$adminUrl}performance" class="menu-item {if $pageName == 'performance'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="zap"></i></span>
				<span class="menu-item__label">{'Performance'|adminT}</span>
			</a>
			<a href="{$adminUrl}api" class="menu-item {if $pageName == 'api'}active{/if}">
				<span class="menu-item__icon"><i data-lucide="link-2"></i></span>
				<span class="menu-item__label">API</span>
			</a>
		</nav>

		<div class="sidebar-footer">
			<a href="{$domain}" class="sidebar-footer__link" target="_blank" rel="noopener">
				<i data-lucide="external-link"></i>
				{'View Store'|adminT}
			</a>
		</div>
	</aside>

	<div class="sidebar-backdrop" id="sidebarBackdrop" hidden></div>

	<div class="admin-main">
		<header class="header">
			<div class="header-left">
				<button type="button" class="header-menu-btn" id="mobileMenuBtn" aria-label="{'Toggle menu'|adminT}">
					<i data-lucide="menu"></i>
				</button>
				{if $pageName != 'dashboard' && $pageTitle}
				<div class="header-page-title d-none d-md-block">
					<h1>{$pageTitle|escape}</h1>
				</div>
				{/if}
			</div>
			<div class="header-right">
				<button type="button" class="header-cmdk-trigger" id="adminCmdkOpen" aria-label="{'Search'|adminT}">
					<i data-lucide="search"></i>
					<span class="header-cmdk-trigger__label">{'Search'|adminT}…</span>
					<kbd class="header-cmdk-trigger__kbd">Ctrl K</kbd>
				</button>
				<div class="dropdown">
					<button class="header-icon-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{'Language'|adminT}">
						<i data-lucide="globe"></i>
					</button>
					<ul class="dropdown-menu dropdown-menu-end shadow-sm">
						{foreach $adminLangSwitcher as $langItem}
						<li><a class="dropdown-item{if $langItem.active} active{/if}" href="{$langItem.url|escape}">{$langItem.label|escape}</a></li>
						{/foreach}
					</ul>
				</div>
				<a href="{$adminUrl}notifications" class="header-icon-btn" title="{'Notifications'|adminT}">
					<i data-lucide="bell"></i>
					{if $adminNavBadges.notifications > 0}<span class="header-icon-btn__badge">{$adminNavBadges.notifications}</span>{/if}
				</a>
				<div class="dropdown">
					<button class="header-user dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
						<span class="header-user__avatar">{$adminInitial|escape}</span>
						<span class="header-user__name d-none d-lg-inline">{$adminUser.full_name|default:'Admin'|escape}</span>
					</button>
					<ul class="dropdown-menu dropdown-menu-end shadow-sm">
						<li class="dropdown-header small">{$adminUser.email|default:''|escape}</li>
						<li><a class="dropdown-item" href="{$adminUrl}account">{'My Account'|adminT}</a></li>
						<li><a class="dropdown-item" href="{$adminUrl}settings">{'Settings'|adminT}</a></li>
						<li><hr class="dropdown-divider"></li>
						<li><a class="dropdown-item text-danger" href="{$adminUrl}logout">{'Sign Out'|adminT}</a></li>
					</ul>
				</div>
			</div>
		</header>

		<div class="main-wrapper">
			{if $pageName != 'dashboard' && $pageTitle}
			<div class="admin-page-head d-md-none">
				<h1>{$pageTitle|escape}</h1>
			</div>
			{/if}
			<div class="admin-content">
			{if $adminUriIsDefault|default:false AND $pageName == 'dashboard'}
			<div class="adminAlert mb-3" role="alert">
				<h5>{'Please change your admin URL'|adminT}</h5>
				<p class="mb-2 mt-2 small">{'The default <em>/admin</em> path is easy for bots to find. Hide it with a custom slug:'|adminT nofilter}</p>
				<ol class="small mb-2 ps-3">
					<li>{'Open <em>config/env.php</em> and set for example:'|adminT nofilter} <em>'ADMIN_URI' =&gt; 'bo_9xK2m7',</em></li>
					<li>{'Use only letters, numbers, underscore and hyphen. Do not rename the physical <em>admin/</em> folder.'|adminT nofilter}</li>
					<li>{'Open the panel at the new URL (e.g. <em>/bo_9xK2m7/</em>) and bookmark it. The old <em>/admin</em> path will return 404.'|adminT nofilter}</li>
				</ol>
				<p class="small mb-0">{'Details:'|adminT} <em>docs/ADMIN_URI.md</em></p>
			</div>
			{/if}
			{if !empty($adminHooks.admin_header)}
			<div class="admin-layout-hook admin-layout-hook--header mb-3">
				{$adminHooks.admin_header nofilter}
			</div>
			{/if}
