<div class="auth-page">
	<div class="row g-4 justify-content-center">
		<div class="col-lg-5 col-md-8 col-12">
			<div class="auth-card page-card">
				<h1 class="page-heading mb-1">Yeni Şifre Belirle</h1>
				<p class="auth-card__subtitle">Hesabınız için yeni bir şifre oluşturun</p>

				{if $authSuccess}
				<div class="alert alert-success auth-notice">{$authSuccess|escape}</div>
				<p class="auth-switch text-center mb-0 mt-3">
					<a href="{$domain}login">Giriş Yap</a>
				</p>
				{elseif $resetToken}
				{if $authError}
				<div class="alert alert-danger auth-notice">{$authError|escape}</div>
				{/if}

				<form method="post" action="{$domain}sifre-sifirla?token={$resetToken|escape:url}" class="auth-form">
					<input type="hidden" name="token" value="{$token}">
					<input type="hidden" name="resetPassword" value="1">

					<div class="mb-3">
						<label class="form-label" for="resetPassword">Yeni Şifre</label>
						<div class="auth-password-wrap">
							<input type="password" id="resetPassword" name="password" class="form-control" placeholder="En az 6 karakter" required autocomplete="new-password">
							<button type="button" class="auth-password-toggle" data-target="#resetPassword" aria-label="Şifreyi göster">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
							</button>
						</div>
					</div>

					<div class="mb-4">
						<label class="form-label" for="resetPassword2">Yeni Şifre Tekrar</label>
						<div class="auth-password-wrap">
							<input type="password" id="resetPassword2" name="password2" class="form-control" placeholder="Şifrenizi tekrar girin" required autocomplete="new-password">
							<button type="button" class="auth-password-toggle" data-target="#resetPassword2" aria-label="Şifreyi göster">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
							</button>
						</div>
					</div>

					<button type="submit" class="btn btn-primary w-100 auth-submit">Şifreyi Güncelle</button>
				</form>
				{else}
				<div class="alert alert-danger auth-notice">{$authError|escape}</div>
				<p class="auth-switch text-center mb-0 mt-3">
					<a href="{$domain}sifremi-unuttum">Yeni sıfırlama bağlantısı iste</a>
				</p>
				{/if}
			</div>
		</div>
	</div>
</div>
