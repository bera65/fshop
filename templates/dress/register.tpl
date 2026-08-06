<div class="auth-split" data-auth-page="register">
	<div class="auth-split__panel auth-split__panel--form">
		<div class="auth-split__form-wrap pt-3">

			<div class="auth-split__intro">
				<h1>{'Welcome to'|translate}</h1>
				<p>{'Start your experience by signing in or signing up.'|translate}</p>
			</div>

			<div class="auth-split__tabs" role="tablist">
				<a href="{$domain}login" class="auth-split__tab">{'Sign In'|translate}</a>
				<a href="{$domain}register" class="auth-split__tab is-active" aria-current="page">{'Sign Up'|translate}</a>
			</div>

			{if isset($registerSuccess) && $registerSuccess}
			<div class="auth-split__success">
				<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
				<p>{$authNotice|escape}</p>
				<a href="{$domain}login" class="btn auth-split__submit w-100">{'Sign In'|translate}</a>
			</div>
			{else}
			{if $authNotice}
			<div class="alert alert-info auth-notice">{$authNotice|escape}</div>
			{/if}
			{if $authError}
			<div class="alert alert-danger auth-notice">{$authError|escape}</div>
			{/if}

			<form id="registerPageForm" method="post" action="{$domain}register" class="auth-form auth-split__form">
				<input type="hidden" name="token" value="{$token}">
				<input type="hidden" name="registerUser" value="1">

				<div class="mb-3">
					<label class="form-label" for="registerPageName">{'Full Name'|translate} *</label>
					<div class="auth-input">
						<span class="auth-input__icon" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
						</span>
						<input type="text" id="registerPageName" name="full_name" class="form-control" placeholder="{'Your full name'|translate}" value="{$formData.full_name|escape}" required autocomplete="name">
					</div>
				</div>

				<div class="mb-3">
					<label class="form-label" for="registerPagePhone">{'Phone'|translate} *</label>
					<div class="auth-input">
						<span class="auth-input__icon" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.81.36 1.6.7 2.34a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.74.34 1.53.57 2.34.7A2 2 0 0 1 22 16.92z"/></svg>
						</span>
						<input type="tel" id="registerPagePhone" name="phone" class="form-control phone-input" placeholder="{'Phone placeholder'|translate}" value="{$formData.phone|escape}" required autocomplete="tel">
					</div>
				</div>

				<div class="mb-3">
					<label class="form-label" for="registerPageEmail">{'Email'|translate} *</label>
					<div class="auth-input">
						<span class="auth-input__icon" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
						</span>
						<input type="email" id="registerPageEmail" name="email" class="form-control" placeholder="{'Email placeholder'|translate}" value="{$formData.email|escape}" required autocomplete="email">
					</div>
				</div>

				<div class="mb-3">
					<label class="form-label" for="registerPagePassword">{'Password'|translate} *</label>
					<div class="auth-input auth-password-wrap">
						<span class="auth-input__icon" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
						</span>
						<input type="password" id="registerPagePassword" name="password" class="form-control" placeholder="{'Password placeholder'|translate}" required autocomplete="new-password" minlength="8">
						<button type="button" class="auth-password-toggle" data-target="#registerPagePassword" data-show-label="{'Show password'|translate|escape}" data-hide-label="{'Hide password'|translate|escape}" aria-label="{'Show password'|translate}">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
						</button>
					</div>
					<div class="form-text auth-password-hint">{'Password requirements hint'|translate}</div>
				</div>

				<div class="mb-4">
					<label class="form-label" for="registerPagePassword2">{'Password repeat'|translate} *</label>
					<div class="auth-input auth-password-wrap">
						<span class="auth-input__icon" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
						</span>
						<input type="password" id="registerPagePassword2" name="password2" class="form-control" placeholder="{'Password repeat placeholder'|translate}" required autocomplete="new-password" minlength="8">
						<button type="button" class="auth-password-toggle" data-target="#registerPagePassword2" data-show-label="{'Show password'|translate|escape}" data-hide-label="{'Hide password'|translate|escape}" aria-label="{'Show password'|translate}">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
						</button>
					</div>
				</div>

				{if $hooks.auth_register}
				{$hooks.auth_register nofilter}
				{/if}

				<button type="submit" class="btn btn-details w-100" id="registerPageSubmit">{'Create account'|translate}</button>
			</form>

			{include file='shopmore/plugin/google-login-btn.tpl'}
			{/if}
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
