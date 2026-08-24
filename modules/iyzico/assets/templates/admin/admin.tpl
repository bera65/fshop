{if $flash}
<div class="alert alert-info py-2">{$flash|escape}</div>
{/if}

<div class="admin-panel p-3" style="max-width: 640px;">
	<h2 class="h6 mb-3">iyzico API Ayarları</h2>
	<p class="text-muted small mb-3">
		Anahtarları <a href="https://merchant.iyzipay.com" target="_blank" rel="noopener">iyzico üye işyeri paneli</a> → Ayarlar → API bilgileri bölümünden alın.
		Test için Sandbox paneli kullanın.
	</p>

	<form method="post">
		<input type="hidden" name="saveIyzico" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="mb-3">
			<label class="form-label">API ortamı</label>
			<select name="api_type" class="form-select">
				<option value="live"{if $iyzicoApiType == 'live'} selected{/if}>Canlı (api.iyzipay.com)</option>
				<option value="sandbox"{if $iyzicoApiType == 'sandbox'} selected{/if}>Sandbox / Test</option>
			</select>
		</div>
		<div class="mb-3">
			<label class="form-label">API Key</label>
			<input type="text" name="api_key" class="form-control" value="{$iyzicoApiKey|escape}" autocomplete="off" required>
		</div>
		<div class="mb-3">
			<label class="form-label">Secret Key</label>
			<input type="text" name="secret_key" class="form-control" value="{$iyzicoSecretKey|escape}" autocomplete="off" required>
		</div>

		<div class="row g-3 mb-3">
			<div class="col-md-6">
				<label class="form-label">Form dili</label>
				<select name="language" class="form-select">
					<option value="auto"{if $iyzicoLanguage == 'auto'} selected{/if}>Mağaza dili</option>
					<option value="tr"{if $iyzicoLanguage == 'tr'} selected{/if}>Türkçe</option>
					<option value="en"{if $iyzicoLanguage == 'en'} selected{/if}>English</option>
				</select>
			</div>
			<div class="col-md-6">
				<label class="form-label">Form görünümü</label>
				<select name="form_class" class="form-select">
					<option value="responsive"{if $iyzicoFormClass == 'responsive'} selected{/if}>Responsive</option>
					<option value="popup"{if $iyzicoFormClass == 'popup'} selected{/if}>Popup</option>
				</select>
			</div>
		</div>

		<div class="form-check mb-3">
			<input class="form-check-input" type="checkbox" name="no_installment" id="iyzicoNoInstallment" value="1"{if $iyzicoNoInstallment} checked{/if}>
			<label class="form-check-label" for="iyzicoNoInstallment">Taksitleri kapat (tek çekim)</label>
		</div>

		<button type="submit" class="btn btn-dark">Kaydet</button>
	</form>
</div>

<div class="admin-panel p-3 mt-3" style="max-width: 640px;">
	<h3 class="h6 mb-2">Callback URL</h3>
	<p class="text-muted small mb-2">iyzico ödeme sonrası müşteri tarayıcısı bu adrese döner (otomatik kullanılır):</p>
	<code class="d-block p-2 bg-light border rounded text-break">{$iyzicoCallbackUrl|escape}</code>
</div>

<div class="admin-panel p-3 mt-3" style="max-width: 640px;">
	<h3 class="h6 mb-2">Webhook URL</h3>
	<p class="text-muted small mb-2">
		iyzico üye işyeri paneline bu adresi girin. Tarayıcı kapanırsa sipariş yine oluşur.
	</p>
	<code class="d-block p-2 bg-light border rounded text-break">{$iyzicoWebhookUrl|escape}</code>
</div>
