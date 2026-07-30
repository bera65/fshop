<div class="admin-panel p-3" style="max-width: 720px;">
	{if $flash}
	<div class="alert alert-success py-2">{$flash|escape}</div>
	{/if}

	<h2 class="h6 mb-3">PayPal</h2>
	<p class="text-muted small">
		<a href="https://developer.paypal.com/dashboard/" target="_blank" rel="noopener">PayPal Developer Dashboard</a>
		üzerinden bir uygulama oluşturup Client ID / Secret alın.
		Sandbox ile test edin; canlıya geçerken “Live” anahtarlarını kullanın.
	</p>

	{if $paypalConfigured}
	<div class="alert alert-success py-2 small">Yapılandırma tamam — checkout’ta PayPal seçeneği görünür.</div>
	{else}
	<div class="alert alert-warning py-2 small">Client ID ve Client Secret zorunludur.</div>
	{/if}

	<form method="post" class="vstack gap-3">
		<input type="hidden" name="savePaypal" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="form-check">
			<input class="form-check-input" type="checkbox" name="sandbox" value="1" id="paypalSandbox"{if $paypalSandbox} checked{/if}>
			<label class="form-check-label" for="paypalSandbox">Sandbox (test) ortamı</label>
		</div>

		<div>
			<label class="form-label">Client ID</label>
			<input type="text" name="client_id" class="form-control font-monospace" value="{$paypalClientId|escape}" required autocomplete="off">
		</div>
		<div>
			<label class="form-label">Client Secret</label>
			<input type="password" name="client_secret" class="form-control font-monospace" value="{$paypalClientSecret|escape}" required autocomplete="new-password">
		</div>
		<div>
			<label class="form-label">Para birimi</label>
			<input type="text" name="currency" class="form-control" value="{$paypalCurrency|escape}" maxlength="3" placeholder="USD" style="max-width:120px">
			<div class="form-text">
				PayPal hesabınızın desteklediği kod (ör. <code>USD</code>, <code>EUR</code>, <code>GBP</code>).
				TRY bazı hesaplarda desteklenmez — Dashboard’dan kontrol edin.
			</div>
		</div>

		<div class="border rounded p-3 bg-light small">
			<div class="mb-1"><span class="text-muted">Return URL:</span> <code class="user-select-all">{$paypalReturnUrl|escape}</code></div>
			<div><span class="text-muted">Cancel URL:</span> <code class="user-select-all">{$paypalCancelUrl|escape}</code></div>
			<div class="text-muted mt-2 mb-0">Bu adresler API isteğinde otomatik gönderilir; PayPal uygulamasında ekstra tanımlama gerekmez.</div>
		</div>

		<button type="submit" class="btn btn-dark align-self-start">Kaydet</button>
	</form>
</div>
