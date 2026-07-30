{if !$configured}
<div class="hb-product-panel">
	<div class="alert alert-warning mb-0">
		<strong>Hepsiburada API yapılandırılmamış.</strong>
		<a href="{$settingsUrl|escape}" class="alert-link">Ayarlara git</a>
	</div>
</div>
{else}
<div class="hb-product-panel" data-id="{$id_product}">
	{if $hb_linked}
	<div class="mb-2 small text-muted">
		Merchant SKU: <code>{$hb_merchant_sku|escape}</code>
		{if $hb_hepsiburada_sku != ''}
		· HB SKU: <code>{$hb_hepsiburada_sku|escape}</code>
		{/if}
	</div>
	<div class="row g-2 mb-3">
		<div class="col-md-6">
			<label class="form-label small mb-0">Hepsiburada satış fiyatı</label>
			<input type="number" step="0.01" min="0" class="form-control hb-sale-price-input" value="{if $hb_sale_price > 0}{$hb_sale_price|escape}{/if}" placeholder="Satış fiyatı">
		</div>
		<div class="col-md-6">
			<label class="form-label small mb-0">Hepsiburada liste fiyatı</label>
			<input type="number" step="0.01" min="0" class="form-control hb-list-price-input" value="{if $hb_list_price > 0}{$hb_list_price|escape}{/if}" placeholder="Liste fiyatı">
		</div>
	</div>
	<div class="d-flex flex-wrap gap-2">
		<button type="button" class="btn btn-dark hb-price-btn" data-url="{$priceUrl|escape}" data-id="{$id_product}" data-platform="hepsiburada">Fiyat / Stok Güncelle</button>
		<button type="button" class="btn btn-outline-danger hb-unlink-btn" data-url="{$unlinkUrl|escape}" data-id="{$id_product}" data-platform="hepsiburada">Bağlantıyı Sil</button>
	</div>
	{else}
	<div class="alert alert-light border small">Hepsiburada'da mevcut listenin merchant SKU'sunu yazın. Ürün doğrulanır ve yalnızca bağlantı oluşturulur.</div>
	<label class="form-label">Merchant SKU</label>
	<div class="input-group">
		<input type="text" class="form-control hb-merchant-sku-input" value="{$product_stock_code|escape}" placeholder="Merchant SKU">
		<button type="button" class="btn btn-primary hb-link-existing-btn" data-url="{$linkExistingUrl|escape}" data-id="{$id_product}" data-platform="hepsiburada">Ürünle eşleştir</button>
	</div>
	{/if}
	<span class="small d-block mt-3 hb-action-msg text-muted"></span>
</div>
{/if}
