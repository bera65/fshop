<div class="panel">

<div class="panel__body auth-page">

	<div class="row g-4 justify-content-center">

		<div class="col-lg-5 col-md-8 col-12">

			<div class="auth-card page-card">

				<h1 class="page-heading mb-1">{'Set New Password'|translate}</h1>

				<p class="auth-card__subtitle">{'Reset password subtitle'|translate}</p>



				{if $authSuccess}

				<div class="alert alert-success auth-notice">{$authSuccess|escape}</div>

				<p class="auth-switch text-center mb-0 mt-3">

					<a href="{$domain}login">{'Back to login'|translate}</a>

				</p>

				{elseif $authError && $resetToken == ''}

				<div class="alert alert-danger auth-notice">{$authError|escape}</div>

				<p class="auth-switch text-center mb-0 mt-3">

					<a href="{$domain}forgot-password">{'Request new reset link'|translate}</a>

				</p>

				{else}

				{if $authError}

				<div class="alert alert-danger auth-notice">{$authError|escape}</div>

				{/if}



				<form method="post" action="{$domain}reset-password?token={$resetToken|escape:'url'}" class="auth-form">

					<input type="hidden" name="token" value="{$token}">

					<input type="hidden" name="resetPassword" value="1">



					<div class="mb-3">

						<label class="form-label" for="resetPassword">{'New Password'|translate}</label>

						<div class="auth-password-wrap">

							<input type="password" id="resetPassword" name="password" class="form-control" minlength="6" required autocomplete="new-password">

							<button type="button" class="auth-password-toggle" data-target="#resetPassword" aria-label="{'Show password'|translate}">

								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>

							</button>

						</div>

					</div>



					<div class="mb-4">

						<label class="form-label" for="resetPassword2">{'Password repeat'|translate}</label>

						<div class="auth-password-wrap">

							<input type="password" id="resetPassword2" name="password2" class="form-control" minlength="6" required autocomplete="new-password">

							<button type="button" class="auth-password-toggle" data-target="#resetPassword2" aria-label="{'Show password'|translate}">

								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>

							</button>

						</div>

					</div>



					<button type="submit" class="btn btn-primary w-100 auth-submit">{'Update Password'|translate}</button>

				</form>



				<p class="auth-switch text-center mb-0 mt-4">

					<a href="{$domain}login">{'Back to login'|translate}</a>

				</p>

				{/if}

			</div>

		</div>

	</div>

</div>

</div>

