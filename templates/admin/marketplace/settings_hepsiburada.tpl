<div class="admin-panel p-3" style="max-width: 720px;">
	<h2 class="h6 mb-3">Hepsiburada API</h2>
	{if !$hbConfigured}
	<div class="alert alert-warning py-2">Merchant ID, API Key (integrator) ve API Pass zorunludur.</div>
	{/if}
	<form method="post">
		<input type="hidden" name="saveHepsiburada" value="1">
		<input type="hidden" name="token" value="{$adminToken}">
		<div class="mb-3">
			<label class="form-label">Merchant ID</label>
			<input type="text" name="merchant_id" class="form-control" value="{$hbMerchantId|escape}" autocomplete="off" required>
		</div>
		<div class="mb-3">
			<label class="form-label">API Key (User-Agent / integrator adı)</label>
			<input type="password" name="api_key" class="form-control" value="{$hbApiKey|escape}" autocomplete="off" required>
		</div>
		<div class="mb-3">
			<label class="form-label">API Pass</label>
			<input type="password" name="api_pass" class="form-control" value="{$hbApiPass|escape}" autocomplete="off" required>
		</div>
		<button type="submit" class="btn btn-dark mt-1">Kaydet</button>
	</form>
	<p class="text-muted small mt-3 mb-0">Ürün eşleştirme ve fiyat/stok güncellemesi ürün panelinden yapılır.</p>
</div>
