{if !$isLoggedIn}
<div class="modal fade" id="authModal" tabindex="-1" aria-labelledby="authModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content auth-modal">
			<div class="modal-header border-0 pb-0">
				<ul class="nav nav-pills auth-modal__tabs" id="authModalTabs" role="tablist">
					<li class="nav-item" role="presentation">
						<button class="nav-link active" id="auth-login-tab" data-bs-toggle="pill" data-bs-target="#auth-login-pane" type="button" role="tab">{'Sign In'|translate}</button>
					</li>
					<li class="nav-item" role="presentation">
						<button class="nav-link" id="auth-register-tab" data-bs-toggle="pill" data-bs-target="#auth-register-pane" type="button" role="tab">{'Sign Up'|translate}</button>
					</li>
				</ul>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body pt-3">
				<div class="tab-content">
					<div class="tab-pane fade show active" id="auth-login-pane" role="tabpanel">
						<p class="auth-modal__lead">{'Login subtitle'|translate}</p>
						<div class="auth-modal__alert d-none" id="authLoginAlert"></div>
						<form id="authModalLoginForm" class="auth-form">
							<div class="mb-3">
								<label class="form-label" for="authModalLoginPhone">{'Email or phone'|translate}</label>
								<input type="text" id="authModalLoginPhone" name="login" class="form-control" placeholder="{'Email or phone placeholder'|translate}" required autocomplete="username">
							</div>
							<div class="mb-3">
								<label class="form-label" for="authModalLoginPassword">{'Password'|translate}</label>
								<input type="password" id="authModalLoginPassword" name="password" class="form-control" placeholder="{'Password placeholder'|translate}" required autocomplete="current-password">
							</div>
							<div class="form-check mb-3">
								<input class="form-check-input" type="checkbox" id="authModalRemember" value="1" checked>
								<label class="form-check-label" for="authModalRemember">{'Remember me'|translate}</label>
							</div>
							{if $hooks.auth_login}
							{$hooks.auth_login nofilter}
							{/if}
							<button type="submit" class="btn btn-primary w-100">{'Sign In'|translate}</button>
						</form>
					</div>
					<div class="tab-pane fade" id="auth-register-pane" role="tabpanel">
						<p class="auth-modal__lead">{'Register subtitle'|translate}</p>
						<div class="auth-modal__alert d-none" id="authRegisterAlert"></div>
						<form id="authModalRegisterForm" class="auth-form">
							<div class="mb-3">
								<label class="form-label" for="authModalRegisterName">{'Full Name'|translate}</label>
								<input type="text" id="authModalRegisterName" name="full_name" class="form-control" placeholder="{'Your full name'|translate}" required autocomplete="name">
							</div>
							<div class="mb-3">
								<label class="form-label" for="authModalRegisterPhone">{'Phone'|translate}</label>
								<input type="tel" id="authModalRegisterPhone" name="phone" class="form-control phone-input" placeholder="{'Phone placeholder'|translate}" required autocomplete="tel">
							</div>
							<div class="mb-3">
								<label class="form-label" for="authModalRegisterEmail">{'Email'|translate}</label>
								<input type="email" id="authModalRegisterEmail" name="email" class="form-control" placeholder="{'Email placeholder'|translate}" required autocomplete="email">
							</div>
							<div class="mb-3">
								<label class="form-label" for="authModalRegisterPassword">{'Password'|translate}</label>
								<input type="password" id="authModalRegisterPassword" name="password" class="form-control" placeholder="{'Password placeholder'|translate}" required autocomplete="new-password">
							</div>
							{if $hooks.auth_register}
							{$hooks.auth_register nofilter}
							{/if}
							<button type="submit" class="btn btn-primary w-100">{'Create account'|translate}</button>
						</form>
					</div>
				</div>
				{include file='blue/plugin/google-login-btn.tpl'}
			</div>
		</div>
	</div>
</div>
{/if}
