<div class="admin-panel p-3" style="max-width: 720px;">
	{if $flash}
	<div class="alert alert-success py-2">{$flash|escape}</div>
	{/if}

	<h2 class="h6 mb-3">Tami Sanal POS</h2>
	<p class="text-muted small">
		3D Secure ödeme: <code>/payment/auth</code> → banka doğrulama → <code>/payment/complete-3ds</code>.
		Sandbox portal: <a href="https://sandbox-portal.tami.com.tr" target="_blank" rel="noopener">sandbox-portal.tami.com.tr</a>
	</p>

	{if $tamiConfigured}
	<div class="alert alert-success py-2 small">Yapılandırma tamam — checkout’ta Tami seçeneği görünür.</div>
	{else}
	<div class="alert alert-warning py-2 small">Merchant, Terminal, Secret Key ve <strong>K Değeri</strong> zorunludur.</div>
	{/if}

	<form method="post" class="vstack gap-3">
		<input type="hidden" name="saveTami" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="form-check">
			<input class="form-check-input" type="checkbox" name="test_mode" value="1" id="tamiTest"{if $tamiTestMode} checked{/if}>
			<label class="form-check-label" for="tamiTest">Test ortamı (sandbox-paymentapi.tami.com.tr)</label>
		</div>

		<div>
			<label class="form-label">İşyeri Numarası (Merchant)</label>
			<input type="text" name="merchant_number" class="form-control font-monospace" value="{$tamiMerchantNumber|escape}" placeholder="77006950" required>
		</div>
		<div>
			<label class="form-label">Terminal Numarası</label>
			<input type="text" name="terminal_number" class="form-control font-monospace" value="{$tamiTerminalNumber|escape}" placeholder="84006953" required>
		</div>
		<div>
			<label class="form-label">Secret Key (Güvenlik Anahtarı)</label>
			<input type="text" name="secret_key" class="form-control font-monospace" value="{$tamiSecretKey|escape}" placeholder="uuid…" required autocomplete="off">
			<div class="form-text">PG-Auth-Token ve callback <code>hashedData</code> için kullanılır.</div>
		</div>
		<div>
			<label class="form-label">Kid Değeri</label>
			<input type="text" name="kid" class="form-control font-monospace" value="{$tamiKid|escape}" placeholder="Portal → POS Yetkileri">
			<div class="form-text">securityHash JWT header <code>kid</code>. Boş bırakılırsa boşluk karakteri gönderilir.</div>
		</div>
		<div>
			<label class="form-label">K Değeri (JWK)</label>
			<input type="text" name="k_value" class="form-control font-monospace" value="{$tamiKValue|escape}" placeholder="base64url HMAC anahtarı" required autocomplete="off">
			<div class="form-text">İstek gövdesi <code>securityHash</code> (HS512 JWS) için zorunlu. Portal → POS Yetkileri.</div>
		</div>
		<div>
			<label class="form-label">Maks. taksit</label>
			<input type="number" name="max_installment" class="form-control" min="1" max="12" value="{$tamiMaxInstallment}">
			<div class="form-text">1 = yalnızca peşin.</div>
		</div>

		<div class="border rounded p-3 bg-light">
			<div class="small text-muted mb-1">3D callback URL (Tami’ye gönderilir)</div>
			<code class="user-select-all">{$tamiCallbackUrl|escape}</code>
		</div>

		<button type="submit" class="btn btn-dark align-self-start">Kaydet</button>
	</form>

	<hr class="my-4">
	<h3 class="h6">Sandbox test örneği</h3>
	<ul class="small text-muted mb-0">
		<li>Merchant <code>77006950</code> · Terminal <code>84006953</code> · Secret <code>0edad05a-7ea7-40f1-a80c-d600121ca51b</code></li>
		<li>Test kart: <code>4824910501747014</code> · SKT 04/2026 (CVV boş olabilir)</li>
		<li>Kid / K değerlerini sandbox portal POS Yetkileri ekranından alın</li>
	</ul>
</div>
