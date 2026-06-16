<div class="auth-page">
	<div class="row g-4 justify-content-center">
		<div class="col-lg-5 col-md-8 col-12">
			<div class="auth-card page-card">
				<h1 class="page-heading mb-1">Şifremi Unuttum</h1>
				<p class="auth-card__subtitle">Kayıtlı e-posta adresinize şifre sıfırlama bağlantısı gönderilir</p>

				{if $authSuccess}
				<div class="alert alert-success auth-notice">{$authSuccess|escape}</div>
				<p class="auth-switch text-center mb-0 mt-3">
					<a href="{$domain}login">Giriş sayfasına dön</a>
				</p>
				{else}
				{if $authError}
				<div class="alert alert-danger auth-notice">{$authError|escape}</div>
				{/if}

				<form method="post" action="{$domain}sifremi-unuttum" class="auth-form">
					<input type="hidden" name="token" value="{$token}">
					<input type="hidden" name="forgotPassword" value="1">

					<div class="mb-4">
						<label class="form-label" for="forgotEmail">E-posta Adresi</label>
						<input type="email" id="forgotEmail" name="email" class="form-control" placeholder="ornek@email.com" value="{$formData.email|escape}" required autocomplete="email">
					</div>

					<button type="submit" class="btn btn-primary w-100 auth-submit">Sıfırlama Bağlantısı Gönder</button>
				</form>

				<p class="auth-switch text-center mb-0 mt-4">
					<a href="{$domain}login">Giriş sayfasına dön</a>
				</p>
				{/if}
			</div>
		</div>
	</div>
</div>
