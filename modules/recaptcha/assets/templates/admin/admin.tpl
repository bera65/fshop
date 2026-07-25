{if $flash}
<div class="alert alert-{$flashType|default:'success'} py-2">{$flash|escape}</div>
{/if}

<div class="admin-panel">
	<h2 class="h6 mb-3">Google reCAPTCHA</h2>
	<p class="text-muted small mb-4">
		İletişim, müşteri giriş/kayıt ve admin giriş formlarını bot saldırılarına karşı korur.
		Anahtarları <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener">Google reCAPTCHA Admin</a> panelinden alın.
	</p>

	<form method="post">
		<input type="hidden" name="saveRecaptcha" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="form-check form-switch mb-4">
			<input class="form-check-input" type="checkbox" name="enabled" value="1" id="recaptchaEnabled"{if $settings.enabled} checked{/if}>
			<label class="form-check-label" for="recaptchaEnabled">reCAPTCHA etkin</label>
		</div>

		<div class="row g-3">
			<div class="col-md-6">
				<label class="form-label">Site Key (public)</label>
				<input type="text" name="site_key" class="form-control" value="{$settings.site_key|escape}" autocomplete="off">
			</div>
			<div class="col-md-6">
				<label class="form-label">Secret Key</label>
				<input type="password" name="secret_key" class="form-control" value="{$settings.secret_key|escape}" autocomplete="off">
			</div>
		</div>

		<div class="row g-3 mt-1">
			<div class="col-md-4">
				<label class="form-label">Sürüm</label>
				<select name="version" class="form-select" id="recaptchaVersion">
					<option value="v3"{if $settings.version == 'v3'} selected{/if}>v3 (görünmez, önerilen)</option>
					<option value="v2"{if $settings.version == 'v2'} selected{/if}>v2 (“Ben robot değilim” kutusu)</option>
				</select>
			</div>
			<div class="col-md-4" id="recaptchaScoreWrap">
				<label class="form-label">v3 skor eşiği</label>
				<input type="number" name="score_threshold" class="form-control" min="0.1" max="0.9" step="0.05" value="{$settings.score_threshold|escape}">
				<div class="form-text">0.5 varsayılan. Düşük = daha sıkı.</div>
			</div>
		</div>

		<hr class="my-4">

		<p class="fw-semibold mb-2">Hangi formlarda kullanılsın?</p>
		<div class="row g-2">
			<div class="col-md-6">
				<div class="form-check">
					<input class="form-check-input" type="checkbox" name="enable_contact" value="1" id="recaptchaContact"{if $settings.enable_contact} checked{/if}>
					<label class="form-check-label" for="recaptchaContact">İletişim formu</label>
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-check">
					<input class="form-check-input" type="checkbox" name="enable_login" value="1" id="recaptchaLogin"{if $settings.enable_login} checked{/if}>
					<label class="form-check-label" for="recaptchaLogin">Müşteri giriş (sayfa + modal)</label>
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-check">
					<input class="form-check-input" type="checkbox" name="enable_register" value="1" id="recaptchaRegister"{if $settings.enable_register} checked{/if}>
					<label class="form-check-label" for="recaptchaRegister">Yeni üyelik (sayfa + modal)</label>
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-check">
					<input class="form-check-input" type="checkbox" name="enable_admin" value="1" id="recaptchaAdmin"{if $settings.enable_admin} checked{/if}>
					<label class="form-check-label" for="recaptchaAdmin">Admin giriş</label>
				</div>
			</div>
		</div>

		<button type="submit" class="btn btn-dark mt-4">Kaydet</button>
	</form>

	{if $isConfigured && $settings.enabled}
	<p class="text-success small mt-3 mb-0">Modül aktif. Seçili formlarda doğrulama yapılır.</p>
	{elseif $isConfigured}
	<p class="text-warning small mt-3 mb-0">Anahtarlar kayıtlı; etkinleştirmek için anahtarı açın.</p>
	{else}
	<p class="text-warning small mt-3 mb-0">Site Key ve Secret Key girilene kadar CAPTCHA gösterilmez.</p>
	{/if}
</div>

<script>
(function () {
	var version = document.getElementById('recaptchaVersion');
	var scoreWrap = document.getElementById('recaptchaScoreWrap');
	if (!version || !scoreWrap) return;
	function sync() {
		scoreWrap.style.display = version.value === 'v3' ? '' : 'none';
	}
	version.addEventListener('change', sync);
	sync();
})();
</script>
