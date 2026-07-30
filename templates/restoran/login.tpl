<div class="panel">

<div class="panel__body auth-page">

	<div class="row g-4 align-items-stretch justify-content-center">

		<div class="col-lg-5 d-none d-lg-flex">

			<div class="auth-aside page-card h-100 w-100">

				<h2 class="auth-aside__title">{'Welcome back'|translate}</h2>

				<p class="auth-aside__text">{'Login aside text'|translate}</p>

				<ul class="auth-aside__list">

					<li>{'Login perk 1'|translate}</li>

					<li>{'Login perk 2'|translate}</li>

					<li>{'Login perk 3'|translate}</li>

				</ul>

			</div>

		</div>



		<div class="col-lg-5 col-md-8 col-12">

			<div class="auth-card page-card">

				<h1 class="page-heading mb-1">{'Sign In'|translate}</h1>

				<p class="auth-card__subtitle">{'Login subtitle'|translate}</p>



				{if $authNotice}

				<div class="alert alert-info auth-notice">{$authNotice|escape}</div>

				{/if}



				{if $authError}

				<div class="alert alert-danger auth-notice">{$authError|escape}</div>

				{/if}



				<form id="loginPageForm" method="post" action="{$domain}login" class="auth-form">

					<input type="hidden" name="token" value="{$token}">

					<input type="hidden" name="loginUser" value="1">



					<div class="mb-3">

						<label class="form-label" for="loginPagePhone">{'Phone'|translate}</label>

						<input type="tel" id="loginPagePhone" name="phone" class="form-control phone-input" placeholder="{'Phone placeholder'|translate}" value="{$formData.phone|escape}" required autocomplete="tel">

					</div>



					<div class="mb-3">

						<label class="form-label" for="loginPagePassword">{'Password'|translate}</label>

						<div class="auth-password-wrap">

							<input type="password" id="loginPagePassword" name="password" class="form-control" placeholder="{'Password placeholder'|translate}" required autocomplete="current-password">

							<button type="button" class="auth-password-toggle" data-target="#loginPagePassword" aria-label="{'Show password'|translate}">

								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>

							</button>

						</div>

					</div>



					<div class="form-check mb-4">

						<input class="form-check-input" type="checkbox" id="loginPageRemember" name="remember" value="1" checked>

						<label class="form-check-label" for="loginPageRemember">{'Remember me'|translate}</label>

					</div>



					<button type="submit" class="btn btn-primary w-100 auth-submit" id="loginPageSubmit">{'Sign In'|translate}</button>

				</form>

				{include file='blue/plugin/google-login-btn.tpl'}

				<p class="auth-switch text-center mb-2 mt-3">

					<a href="{$domain}forgot-password">{'Forgot password'|translate}</a>

				</p>

				<p class="auth-switch text-center mb-0 mt-2">

					{'No account yet'|translate}

					<a href="{$domain}register">{'Sign Up'|translate}</a>

				</p>

			</div>

		</div>

	</div>

</div>

</div>

