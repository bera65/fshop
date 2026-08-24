<!DOCTYPE html>
<html lang="{$adminLang|default:'tr'|escape}">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{'Forgot password'|adminT} | {$siteName|escape}</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="{$domain}templates/admin/css/bootstrap.min.css">
	<link rel="stylesheet" href="{$adminCssDir}admin.css?v={$smarty.now}">
	<link rel="icon" type="image/x-icon" href="{$domain}img/faviconAdmin.ico">
</head>
<body class="admin-login-body">
<div class="admin-login-stage">
	<div class="admin-login-stage__glow admin-login-stage__glow--a" aria-hidden="true"></div>
	<div class="admin-login-stage__glow admin-login-stage__glow--b" aria-hidden="true"></div>
	<div class="admin-login-stage__grid" aria-hidden="true"></div>

	<main class="admin-login-center">
		<div class="admin-login-card">
			<div class="admin-login-card__top">
				<div class="admin-login-brand">
					<img src="{$adminLogoUrl|escape}?v={$smarty.now}" alt="{$siteName|escape}">
					<div class="admin-login-brand__text">
						<span class="admin-login-brand__name">{$siteName|escape}</span>
						<span class="admin-login-brand__tag">{'Admin Panel'|adminT}</span>
					</div>
				</div>
				<div class="dropdown admin-login-lang">
					<button class="btn admin-login-lang__btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
						{if $adminLangSwitcher|@count}{foreach $adminLangSwitcher as $l}{if $l.active}{$l.label|escape}{/if}{/foreach}{else}TR{/if}
					</button>
					<ul class="dropdown-menu dropdown-menu-end shadow-sm">
						{foreach $adminLangSwitcher as $langItem}
						<li><a class="dropdown-item{if $langItem.active} active{/if}" href="{$langItem.url|escape}">{$langItem.label|escape}</a></li>
						{/foreach}
					</ul>
				</div>
			</div>

			<header class="admin-login-card__head">
				<h1>{'Forgot password'|adminT}</h1>
				<p>{'Enter your admin email to receive a reset link'|adminT}</p>
			</header>

			{if $loginSuccess}
			<div class="alert alert-success admin-login-alert" role="status">{$loginSuccess|escape}</div>
			<p class="admin-login-back">
				<a href="{$adminUrl}login">{'Back to login'|adminT}</a>
			</p>
			{else}
			{if $loginError}
			<div class="alert alert-danger admin-login-alert" role="alert">{$loginError|escape}</div>
			{/if}

			<form method="post" action="{$adminUrl}forgot-password" class="admin-login-form" autocomplete="on">
				<input type="hidden" name="adminForgotPassword" value="1">
				<input type="hidden" name="token" value="{$adminToken}">

				<div class="admin-login-field">
					<label class="form-label" for="adminForgotEmail">{'Email'|adminT}</label>
					<input type="email" name="email" id="adminForgotEmail" class="form-control admin-login-input" required autofocus placeholder="admin@ornek.com" autocomplete="email" value="{$formEmail|escape}">
				</div>
				<button type="submit" class="btn btn-admin-primary admin-login-submit w-100">{'Send Reset Link'|adminT}</button>
			</form>

			<p class="admin-login-back">
				<a href="{$adminUrl}login">{'← Back to login'|adminT}</a>
			</p>
			{/if}
		</div>

		<p class="admin-login-copy">&copy; {$year|escape} {$siteName|escape}</p>
	</main>
</div>
<script src="{$domain}templates/admin/js/popper.min.js"></script>
<script src="{$domain}templates/admin/js/bootstrap.min.js"></script>
</body>
</html>
