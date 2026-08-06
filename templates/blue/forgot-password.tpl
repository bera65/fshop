<div class="panel">

<div class="panel__body auth-page">

	<div class="row g-4 justify-content-center">

		<div class="col-lg-5 col-md-8 col-12">

			<div class="auth-card page-card">

				<h1 class="page-heading mb-1">{'Forgot Password'|translate}</h1>

				<p class="auth-card__subtitle">{'Reset link subtitle'|translate}</p>



				{if $authSuccess}

				<div class="alert alert-success auth-notice">{$authSuccess|escape}</div>

				<p class="auth-switch text-center mb-0 mt-3">

					<a href="{$domain}login">{'Back to login'|translate}</a>

				</p>

				{else}

				{if $authError}

				<div class="alert alert-danger auth-notice">{$authError|escape}</div>

				{/if}



				<form method="post" action="{$domain}forgot-password" class="auth-form">

					<input type="hidden" name="token" value="{$token}">

					<input type="hidden" name="forgotPassword" value="1">



					<div class="mb-4">

						<label class="form-label" for="forgotEmail">{'Email'|translate}</label>

						<input type="email" id="forgotEmail" name="email" class="form-control" placeholder="{'Email placeholder'|translate}" value="{$formData.email|escape}" required autocomplete="email">

					</div>



					<button type="submit" class="btn btn-primary w-100 auth-submit">{'Send Reset Link'|translate}</button>

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

