<div class="panel">

<div class="panel__body auth-page">

	<div class="row g-4 align-items-stretch justify-content-center">

		<div class="col-lg-5 d-none d-lg-flex">

			<div class="auth-aside page-card h-100 w-100">

				<h2 class="auth-aside__title">{'Register aside title'|translate}</h2>

				<p class="auth-aside__text">{'Register aside text'|translate}</p>

				<ul class="auth-aside__list">

					<li>{'Register perk 1'|translate}</li>

					<li>{'Register perk 2'|translate}</li>

					<li>{'Register perk 3'|translate}</li>

				</ul>

			</div>

		</div>



		<div class="col-lg-5 col-md-8 col-12">

			<div class="auth-card page-card">

				<h1 class="page-heading mb-1">{'Sign Up'|translate}</h1>

				<p class="auth-card__subtitle">{'Register subtitle'|translate}</p>



				{if $authNotice}

				<div class="alert alert-info auth-notice">{$authNotice|escape}</div>

				{/if}



				{if $authError}

				<div class="alert alert-danger auth-notice">{$authError|escape}</div>

				{/if}



				<form id="registerPageForm" method="post" action="{$domain}register" class="auth-form">

					<input type="hidden" name="token" value="{$token}">

					<input type="hidden" name="registerUser" value="1">



					<div class="mb-3">

						<label class="form-label" for="registerPageName">{'Full Name'|translate}</label>

						<input type="text" id="registerPageName" name="full_name" class="form-control" placeholder="{'Your full name'|translate}" value="{$formData.full_name|escape}" required autocomplete="name">

					</div>



					<div class="mb-3">

						<label class="form-label" for="registerPagePhone">{'Phone'|translate}</label>

						<input type="tel" id="registerPagePhone" name="phone" class="form-control phone-input" placeholder="{'Phone placeholder'|translate}" value="{$formData.phone|escape}" required autocomplete="tel">

					</div>



					<div class="mb-3">

						<label class="form-label" for="registerPageEmail">{'Email'|translate}</label>

						<input type="email" id="registerPageEmail" name="email" class="form-control" placeholder="{'Email placeholder'|translate}" value="{$formData.email|escape}" required autocomplete="email">

						<div class="form-text">{'Welcome email hint'|translate}</div>

					</div>



					<div class="mb-3">

						<label class="form-label" for="registerPagePassword">{'Password'|translate}</label>

						<div class="auth-password-wrap">

							<input type="password" id="registerPagePassword" name="password" class="form-control" placeholder="{'Password placeholder'|translate}" required autocomplete="new-password">

							<button type="button" class="auth-password-toggle" data-target="#registerPagePassword" aria-label="{'Show password'|translate}">

								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>

							</button>

						</div>

					</div>



					<div class="mb-4">

						<label class="form-label" for="registerPagePassword2">{'Password repeat'|translate}</label>

						<div class="auth-password-wrap">

							<input type="password" id="registerPagePassword2" name="password2" class="form-control" placeholder="{'Password repeat placeholder'|translate}" required autocomplete="new-password">

							<button type="button" class="auth-password-toggle" data-target="#registerPagePassword2" aria-label="{'Show password'|translate}">

								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>

							</button>

						</div>

					</div>



					<button type="submit" class="btn btn-primary w-100 auth-submit" id="registerPageSubmit">{'Create account'|translate}</button>

				</form>

				{include file='blue/plugin/google-login-btn.tpl'}

				<p class="auth-switch text-center mb-0 mt-4">

					{'Already a member'|translate}

					<a href="{$domain}login">{'Sign In'|translate}</a>

				</p>

			</div>

		</div>

	</div>

</div>

</div>

