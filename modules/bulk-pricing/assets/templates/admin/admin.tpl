<div class="admin-panel bulk-pricing-admin">
	<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
		<div>
			<h1 class="h3 mb-1">Toplu Fiyat Güncelleme</h1>
			<p class="text-muted mb-0">Kategori, marka veya arama filtresine göre alış, satış ve eski fiyatlara yüzde veya sabit tutar zam/indirim uygulayın.</p>
		</div>
	</div>

	{if $flash}
	<div class="alert alert-{$flashType|default:'info'|escape}">{$flash|escape}</div>
	{/if}

	<form method="post" id="bulkPricingForm" class="row g-4">
		<input type="hidden" name="token" value="{$adminToken|escape}">

		<div class="col-lg-4">
			<div class="admin-panel p-4 h-100">
				<h2 class="h6 mb-3">Filtreler</h2>

				<div class="mb-3">
					<label class="form-label" for="bpQuery">Arama</label>
					<input type="text" class="form-control" id="bpQuery" name="q" value="{$filters.query|escape}" placeholder="Ürün adı, stok kodu, barkod">
				</div>

				<div class="mb-3">
					<label class="form-label" for="bpCategory">Kategori</label>
					<select class="form-select" id="bpCategory" name="id_category">
						<option value="0"{if $filters.id_category == 0} selected{/if}>Tüm kategoriler</option>
						{foreach $categoryOptions as $cat}
						<option value="{$cat.id_category|escape}"{if $filters.id_category == $cat.id_category} selected{/if}>{$cat.category_name|escape}</option>
						{/foreach}
					</select>
				</div>

				<div class="mb-3">
					<label class="form-label" for="bpBrand">Marka</label>
					<select class="form-select" id="bpBrand" name="id_brand">
						<option value="0"{if $filters.id_brand == 0} selected{/if}>Tüm markalar</option>
						{foreach $brandOptions as $brand}
						<option value="{$brand.id_brand|escape}"{if $filters.id_brand == $brand.id_brand} selected{/if}>{$brand.brand_name|escape}</option>
						{/foreach}
					</select>
				</div>

				<div class="mb-0">
					<label class="form-label" for="bpActive">Durum</label>
					<select class="form-select" id="bpActive" name="active_filter">
						<option value="-1"{if $filters.active == -1} selected{/if}>Tümü</option>
						<option value="1"{if $filters.active == 1} selected{/if}>Yalnızca aktif</option>
						<option value="0"{if $filters.active == 0} selected{/if}>Yalnızca pasif</option>
					</select>
				</div>
			</div>
		</div>

		<div class="col-lg-8">
			<div class="admin-panel p-4 mb-4">
				<h2 class="h6 mb-3">Fiyat değişikliği</h2>

				<div class="mb-3">
					<label class="form-label d-block">Güncellenecek alanlar</label>
					<div class="d-flex flex-wrap gap-3">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="field_price" id="fieldPrice" value="1"{if in_array('price', $adjustment.fields)} checked{/if}>
							<label class="form-check-label" for="fieldPrice">Satış fiyatı</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="field_cost" id="fieldCost" value="1"{if in_array('cost', $adjustment.fields)} checked{/if}>
							<label class="form-check-label" for="fieldCost">Alış fiyatı</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="field_old_price" id="fieldOldPrice" value="1"{if in_array('old_price', $adjustment.fields)} checked{/if}>
							<label class="form-check-label" for="fieldOldPrice">Eski fiyat</label>
						</div>
					</div>
				</div>

				<div class="row g-3">
					<div class="col-md-4">
						<label class="form-label" for="adjustMode">İşlem türü</label>
						<select class="form-select" id="adjustMode" name="adjust_mode">
							<option value="percent"{if $adjustment.mode == 'percent'} selected{/if}>Yüzde (%)</option>
							<option value="fixed"{if $adjustment.mode == 'fixed'} selected{/if}>Sabit tutar ({$shopCurrency|upper|escape})</option>
						</select>
					</div>
					<div class="col-md-4">
						<label class="form-label" for="adjustDirection">Yön</label>
						<select class="form-select" id="adjustDirection" name="adjust_direction">
							<option value="increase"{if $adjustment.direction == 'increase'} selected{/if}>Zam / artır</option>
							<option value="decrease"{if $adjustment.direction == 'decrease'} selected{/if}>İndirim / azalt</option>
						</select>
					</div>
					<div class="col-md-4">
						<label class="form-label" for="adjustValue">Değer</label>
						<input type="text" class="form-control" id="adjustValue" name="adjust_value" value="{$adjustment.value|string_format:'%.2f'|escape}" inputmode="decimal" placeholder="10">
						<div class="form-text" id="adjustValueHint">Örn. satış fiyatına %10 zam için 10 yazın.</div>
					</div>
				</div>

				<div class="d-flex flex-wrap gap-2 mt-4">
					<button type="submit" class="btn btn-outline-primary" name="bulkPricingPreview" value="1">Önizleme</button>
					<button type="submit" class="btn btn-primary js-bulk-pricing-apply js-admin-busy" name="bulkPricingApply" value="1">Uygula</button>
				</div>
			</div>

			{if $previewRows|@count > 0}
			<div class="admin-panel p-4">
				<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
					<h2 class="h6 mb-0">Önizleme</h2>
					{if $matchCount > 0}
					<span class="badge bg-secondary">{$matchCount|escape} ürün eşleşti</span>
					{/if}
				</div>

				{if $adjustmentLabel}
				<p class="small text-muted">Uygulanacak işlem: <strong>{$adjustmentLabel|escape}</strong></p>
				{/if}

				<div class="table-responsive">
					<table class="table table-sm align-middle mb-0">
						<thead>
							<tr>
								<th>Ürün</th>
								<th>Stok kodu</th>
								<th>Satış</th>
								<th>Alış</th>
								<th>Eski</th>
							</tr>
						</thead>
						<tbody>
							{foreach $previewRows as $row}
							<tr>
								<td>
									<div class="fw-semibold">{$row.product_name|escape}</div>
									<div class="small text-muted">{$row.category_name|escape} · {$row.brand_name|escape}</div>
								</td>
								<td>{$row.stock_code|escape}</td>
								<td>
									{if $row.changes.price}
									<span class="text-muted text-decoration-line-through">{$row.changes.price.before|string_format:'%.2f'|escape}</span>
									→ <strong>{$row.changes.price.after|string_format:'%.2f'|escape}</strong>
									{else}
									<span class="text-muted">{$row.price|string_format:'%.2f'|escape}</span>
									{/if}
								</td>
								<td>
									{if $row.changes.cost}
									<span class="text-muted text-decoration-line-through">{$row.changes.cost.before|string_format:'%.2f'|escape}</span>
									→ <strong>{$row.changes.cost.after|string_format:'%.2f'|escape}</strong>
									{else}
									<span class="text-muted">{$row.cost|string_format:'%.2f'|escape}</span>
									{/if}
								</td>
								<td>
									{if $row.changes.old_price}
									<span class="text-muted text-decoration-line-through">{$row.changes.old_price.before|string_format:'%.2f'|escape}</span>
									→ <strong>{$row.changes.old_price.after|string_format:'%.2f'|escape}</strong>
									{else}
									<span class="text-muted">{$row.old_price|string_format:'%.2f'|escape}</span>
									{/if}
								</td>
							</tr>
							{/foreach}
						</tbody>
					</table>
				</div>

				{if $matchCount > $previewRows|@count}
				<p class="small text-muted mt-3 mb-0">İlk {$previewRows|@count} ürün gösteriliyor. Uygula dediğinizde eşleşen tüm ürünler güncellenir.</p>
				{/if}
			</div>
			{/if}
		</div>
	</form>
</div>
