{if !$configured}
<div class="n11-product-panel">
	<div class="alert alert-warning mb-0">
		<strong>N11 API yapılandırılmamış.</strong>
		<a href="{$settingsUrl|escape}" class="alert-link">Ayarlara git</a>
	</div>
</div>
{else}
<div class="n11-product-panel" data-id="{$id_product}">
	{if $n11_linked}
	<div class="mb-2 small text-muted">
		Stok kodu: <code>{$n11_stock_code|escape}</code>
	</div>
	<div class="row g-2 mb-3">
		<div class="col-md-6">
			<label class="form-label small mb-0">N11 satış fiyatı</label>
			<input type="number" step="0.01" min="0" class="form-control n11-sale-price-input" value="{if $n11_sale_price > 0}{$n11_sale_price|escape}{/if}" placeholder="Satış fiyatı">
		</div>
		<div class="col-md-6">
			<label class="form-label small mb-0">N11 liste fiyatı</label>
			<input type="number" step="0.01" min="0" class="form-control n11-list-price-input" value="{if $n11_list_price > 0}{$n11_list_price|escape}{/if}" placeholder="Liste fiyatı">
		</div>
	</div>
	<div class="d-flex flex-wrap gap-2">
		<button type="button" class="btn btn-dark n11-price-btn" data-url="{$priceUrl|escape}" data-id="{$id_product}" data-platform="n11">Fiyat / Stok Güncelle</button>
		<button type="button" class="btn btn-outline-danger n11-unlink-btn" data-url="{$unlinkUrl|escape}" data-id="{$id_product}" data-platform="n11">Bağlantıyı Sil</button>
	</div>
	{else}
	<div class="alert alert-light border small">N11'de mevcut ürünün stok kodunu yazın. Ürün doğrulanır ve yalnızca bağlantı oluşturulur.</div>
	<label class="form-label">Stok kodu</label>
	<div class="input-group">
		<input type="text" class="form-control n11-stock-code-input" value="{$product_stock_code|escape}" placeholder="N11 stok kodu">
		<button type="button" class="btn btn-primary n11-link-existing-btn" data-url="{$linkExistingUrl|escape}" data-id="{$id_product}" data-platform="n11">Ürünle eşleştir</button>
	</div>
	{/if}
	<span class="small d-block mt-3 n11-action-msg text-muted"></span>
</div>
{/if}
