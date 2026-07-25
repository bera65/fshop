<!DOCTYPE html>
<html lang="{$adminLang|default:'tr'|escape}">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{'Admin Login'|adminT} | {$siteName|escape}</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="{$domain}templates/admin/css/bootstrap.min.css">
	<link rel="stylesheet" href="{$adminCssDir}admin.css?v={$smarty.now}">
	{if $recaptchaModuleCss}
	<link rel="stylesheet" href="{$recaptchaModuleCss|escape}">
	{/if}
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
				<h1>{'Welcome back'|adminT}</h1>
				<p>{'Sign in to the admin panel'|adminT}</p>
			</header>

			{if $loginError}
			<div class="alert alert-danger admin-login-alert" role="alert">{$loginError|escape}</div>
			{/if}

			<form method="post" action="{$adminUrl}login" class="admin-login-form" autocomplete="on">
				<input type="hidden" name="adminLogin" value="1">
				<input type="hidden" name="token" value="{$adminToken}">

				<div class="admin-login-field">
					<label class="form-label" for="adminLoginEmail">{'Email'|adminT}</label>
					<input type="email" name="email" id="adminLoginEmail" class="form-control admin-login-input" required autofocus placeholder="admin@ornek.com" autocomplete="username">
				</div>
				<div class="admin-login-field">
					<label class="form-label" for="adminLoginPassword">{'Password'|adminT}</label>
					<div class="admin-login-password">
						<input type="password" name="password" id="adminLoginPassword" class="form-control admin-login-input" required placeholder="••••••••" autocomplete="current-password">
						<button type="button" class="admin-login-password__toggle" id="adminLoginTogglePass" aria-label="{'Show password'|adminT}" data-show-label="{'Show password'|adminT}" data-hide-label="{'Hide password'|adminT}">
							<svg class="icon-show" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
							<svg class="icon-hide d-none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 19c-6.5 0-10-7-10-7a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
						</button>
					</div>
				</div>
				{if $recaptchaAdminLogin}
				<div class="admin-login-field">
					{$recaptchaAdminLogin nofilter}
				</div>
				{/if}
				<button type="submit" class="btn btn-admin-primary admin-login-submit w-100">{'Sign In'|adminT}</button>
			</form>

			<p class="admin-login-back">
				<a href="{$domain}">{'← Back to store'|adminT}</a>
			</p>
		</div>

		<p class="admin-login-copy">&copy; {$year|escape} {$siteName|escape}</p>
	</main>
</div>
<script src="{$domain}templates/admin/js/popper.min.js"></script>
<script src="{$domain}templates/admin/js/bootstrap.min.js"></script>
<script>
(function () {
	var btn = document.getElementById('adminLoginTogglePass');
	var input = document.getElementById('adminLoginPassword');
	if (!btn || !input) return;
	btn.addEventListener('click', function () {
		var show = input.type === 'password';
		input.type = show ? 'text' : 'password';
		btn.setAttribute('aria-label', show ? btn.getAttribute('data-hide-label') : btn.getAttribute('data-show-label'));
		btn.querySelector('.icon-show').classList.toggle('d-none', show);
		btn.querySelector('.icon-hide').classList.toggle('d-none', !show);
	});
})();
</script>
{if $recaptchaConfigJson && $recaptchaModuleJs}
<script>window.fshopRecaptcha={$recaptchaConfigJson nofilter};</script>
<script src="{$recaptchaModuleJs|escape}"></script>
{/if}
</body>
</html>
