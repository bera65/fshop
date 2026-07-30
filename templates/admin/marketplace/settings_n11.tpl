<div class="admin-panel p-3" style="max-width: 720px;">
	<h2 class="h6 mb-3">N11 API</h2>
	{if !$n11Configured}
	<div class="alert alert-warning py-2">API Key ve API Secret zorunludur.</div>
	{/if}
	<form method="post">
		<input type="hidden" name="saveN11" value="1">
		<input type="hidden" name="token" value="{$adminToken}">
		<div class="mb-3">
			<label class="form-label">API Key</label>
			<input type="password" name="api_key" class="form-control" value="{$n11ApiKey|escape}" autocomplete="off" required>
		</div>
		<div class="mb-3">
			<label class="form-label">API Secret</label>
			<input type="password" name="api_secret" class="form-control" value="{$n11ApiSecret|escape}" autocomplete="off" required>
		</div>
		<button type="submit" class="btn btn-dark mt-1">Kaydet</button>
	</form>
	<p class="text-muted small mt-3 mb-0">Ürün eşleştirme stok kodu ile yapılır; fiyat/stok ürün panelinden güncellenir.</p>
</div>
