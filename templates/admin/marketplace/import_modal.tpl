<div class="modal fade" id="trendyolProductImportModal" tabindex="-1" aria-labelledby="trendyolProductImportModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="trendyolProductImportModalLabel">Barkod ile ürün içe aktar</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
			</div>
			<div class="modal-body">
				<div class="alert alert-danger d-none" id="trendyolImportError"></div>
				<div class="alert alert-success d-none" id="trendyolImportSuccess"></div>

				<div class="mb-3">
					<label class="form-label" for="trendyolImportSource">Kaynak site</label>
					<select class="form-select" id="trendyolImportSource">
						<option value="trendyol"{if $tyConfigured} selected{/if}>Trendyol</option>
						<option value="fiyattrend"{if !$tyConfigured} selected{/if}>FiyatTrend</option>
					</select>
				</div>

				<div class="mb-3">
					<label class="form-label" for="trendyolImportBarcode">Barkod</label>
					<input type="text" class="form-control" id="trendyolImportBarcode" placeholder="8699708091650" autocomplete="off">
				</div>

				<div class="row g-3">
					<div class="col-md-6">
						<label class="form-label" for="trendyolImportCategory">Kategori</label>
						<select class="form-select" id="trendyolImportCategory">
							<option value="create" selected>Kategoriyi ekle (kaynak adından)</option>
							{foreach $categoryOptions as $cat}
							<option value="{$cat.id_category}">{$cat.category_name|escape}</option>
							{/foreach}
						</select>
					</div>
					<div class="col-md-6">
						<label class="form-label" for="trendyolImportBrand">Marka</label>
						<select class="form-select" id="trendyolImportBrand">
							<option value="create" selected>Markayı ekle (kaynak adından)</option>
							{foreach $brandOptions as $brand}
							<option value="{$brand.id_brand}">{$brand.brand_name|escape}</option>
							{/foreach}
						</select>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
				<button type="button" class="btn btn-primary" id="trendyolImportSubmit">Ürünü içe aktar</button>
			</div>
		</div>
	</div>
</div>

<script>
window.trendyolProductImportConfig = {
	importUrl: {$importUrl|@json_encode nofilter},
	token: {$adminToken|@json_encode nofilter}
};
</script>
