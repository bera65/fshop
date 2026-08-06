<div class="auth-split" data-auth-page="login">
	<div class="auth-split__panel auth-split__panel--form">
		<div class="auth-split__form-wrap">
			<div class="auth-split__intro pt-3">
				<h1>{'Welcome to'|translate}</h1>
				<p>{'Start your experience by signing in or signing up.'|translate}</p>
			</div>

			<div class="auth-split__tabs" role="tablist">
				<a href="{$domain}login" class="auth-split__tab is-active" aria-current="page">{'Sign In'|translate}</a>
				<a href="{$domain}register" class="auth-split__tab">{'Sign Up'|translate}</a>
			</div>

			{if $authNotice}
			<div class="alert alert-info auth-notice">{$authNotice|escape}</div>
			{/if}
			{if $authError}
			<div class="alert alert-danger auth-notice">{$authError|escape}</div>
			{/if}

			<form id="loginPageForm" method="post" action="{$domain}login" class="auth-form auth-split__form">
				<input type="hidden" name="token" value="{$token}">
				<input type="hidden" name="loginUser" value="1">

				<div class="mb-3">
					<label class="form-label" for="loginPagePhone">{'Email or phone'|translate} *</label>
					<div class="auth-input">
						<span class="auth-input__icon" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
						</span>
						<input type="text" id="loginPagePhone" name="login" class="form-control" placeholder="{'Email or phone placeholder'|translate}" value="{$formData.login|default:$formData.phone|default:''|escape}" required autocomplete="username">
					</div>
				</div>

				<div class="mb-3">
					<label class="form-label" for="loginPagePassword">{'Password'|translate} *</label>
					<div class="auth-input auth-password-wrap">
						<span class="auth-input__icon" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
						</span>
						<input type="password" id="loginPagePassword" name="password" class="form-control" placeholder="{'Password placeholder'|translate}" required autocomplete="current-password">
						<button type="button" class="auth-password-toggle" data-target="#loginPagePassword" data-show-label="{'Show password'|translate|escape}" data-hide-label="{'Hide password'|translate|escape}" aria-label="{'Show password'|translate}">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
						</button>
					</div>
				</div>

				<div class="d-flex justify-content-between align-items-center mb-4">
					<div class="form-check mb-0">
						<input class="form-check-input" type="checkbox" id="loginPageRemember" name="remember" value="1" checked>
						<label class="form-check-label" for="loginPageRemember">{'Remember me'|translate}</label>
					</div>
					<a class="auth-split__link" href="{$domain}forgot-password">{'Forgot password'|translate}</a>
				</div>

				{if $hooks.auth_login}
				{$hooks.auth_login nofilter}
				{/if}

				<button type="submit" class="btn btn-details w-100" id="loginPageSubmit">{'Sign In'|translate}</button>
			</form>

			{include file='shopmore/plugin/google-login-btn.tpl'}
		</div>
	</div>

	<div class="auth-split__panel auth-split__panel--visual"{if $authAsideExists} style="background-image:url('{$domain}img/auth-aside.jpg')"{/if}>
		<div class="auth-split__visual-shade"></div>
		<div class="auth-split__visual-content">
			{if $siteLogos.header}
			<img class="auth-split__visual-logo" src="{$siteLogos.header|escape}" alt="{$siteName|escape}">
			{/if}
			<h2>{'Auth aside title'|translate}</h2>
			<p>{'Auth aside text'|translate}</p>
		</div>
	</div>
</div>
