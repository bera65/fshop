<link rel="stylesheet" href="{$domain}templates/admin/css/product-editor.css?v={$smarty.now}">
<link rel="stylesheet" href="{$domain}templates/admin/css/media-library.css?v={$smarty.now}">

<div class="product-editor">
	{if $flash}
		<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
			<div class="toast frisay-toast {$flashType|default:'success'} show" role="alert">
				<div class="toast-icon">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
				</div>
				<div class="toast-body p-0">
					<div class="toast-message">
						{$flash|escape}
					</div>
				</div>
				<button class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
			</div>
		</div>
	{/if}

	<form method="post" id="productForm">
		<input type="hidden" name="saveProduct" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="pe-topbar">
			<div class="pe-topbar-title">
				<h1>{if $isNew}{'New Product'|adminT}{else}{'Edit Product'|adminT}{/if}</h1>
				<span>
					{if $isNew}
						{'Save first; then you can add images via drag and drop.'|adminT}
					{else}
						#{$idProduct} · {'Saved per language tab'|adminT} ({$shopLanguages|@count} {'Language'|adminT})
					{/if}
				</span>
			</div>
			<div class="save">
				<a href="{$adminUrl}languages" class="btn btn-sm btn-outline-secondary">{'Languages'|adminT}</a>
				{if !$isNew && $pLink}
				<a href="{$pLink}" class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener">{'View product'|adminT}</a>
				{/if}
				<button type="submit" class="btn btn-success btn-sm">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
					{'Save'|adminT}
				</button>
			</div>
		</div>

		<div class="pe-tabs-layout">
			<nav class="pe-tabs-nav" role="tablist" aria-label="{'Product editor'|adminT}">
				<button type="button" class="pe-tab-btn pe-tab-btn--active" role="tab" aria-selected="true" aria-controls="pePaneGeneral" data-tab="general">{'General'|adminT}</button>
				<button type="button" class="pe-tab-btn" role="tab" aria-selected="false" aria-controls="pePaneDetail" data-tab="detail">{'Product details'|adminT}</button>
				<button type="button" class="pe-tab-btn" role="tab" aria-selected="false" aria-controls="pePanePricing" data-tab="pricing">{'Price & stock'|adminT}</button>
				<button type="button" class="pe-tab-btn" role="tab" aria-selected="false" aria-controls="pePaneMedia" data-tab="media">{'Images'|adminT}</button>
				{if !$isNew}
				<button type="button" class="pe-tab-btn" role="tab" aria-selected="false" aria-controls="pePaneLog" data-tab="log">{'Log'|adminT}</button>
				{/if}
			</nav>

			<div class="pe-tabs-content">
				{* ── General ── *}
				<div class="pe-tab-pane pe-tab-pane--active" id="pePaneGeneral" role="tabpanel" data-tab="general">
					<div class="pe-card pe-lang-tabs">
						<div class="pe-card-head">
							<div>
								<h2>{'Product name &amp; URL'|adminT}</h2>
								<p>{'Name and slug are per language.'|adminT}</p>
							</div>
						</div>

						<ul class="nav nav-tabs mb-3" role="tablist">
							{foreach $productLangForms as $langCode => $langForm}
							<li class="nav-item" role="presentation">
								<button class="nav-link{if $langForm@first} active{/if}" data-bs-toggle="tab" data-bs-target="#product-general-pane-{$langCode|escape}" type="button" role="tab">{$langForm.label|escape}</button>
							</li>
							{/foreach}
						</ul>

						<div class="tab-content">
							{foreach $productLangForms as $langCode => $langForm}
							<div class="tab-pane fade{if $langForm@first} show active{/if}" id="product-general-pane-{$langCode|escape}" role="tabpanel">
								<div class="row g-3">
									<div class="col-md-8">
										<label class="form-label">{'Product name'|adminT} ({$langForm.label|escape}){if $langForm@first} *{/if}</label>
										<input type="text" name="langs[{$langCode|escape}][product_name]" class="form-control"{if $langForm@first} required{/if} value="{$langForm.product_name|escape}">
									</div>
									<div class="col-md-4">
										<label class="form-label">URL Slug</label>
										<input type="text" name="langs[{$langCode|escape}][product_link]" class="form-control" value="{$langForm.product_link|escape}" placeholder="{'Leave blank for automatic'|adminT}">
									</div>
								</div>
							</div>
							{/foreach}
						</div>
					</div>

					<div class="pe-card pe-lang-tabs">
						<div class="pe-card-head">
							<div>
								<h2>{'Product content'|adminT}</h2>
								<p>{'Short/long description and SEO fields are per language.'|adminT}</p>
							</div>
						</div>

						<ul class="nav nav-tabs mb-3" role="tablist">
							{foreach $productLangForms as $langCode => $langForm}
							<li class="nav-item" role="presentation">
								<button class="nav-link{if $langForm@first} active{/if}" data-bs-toggle="tab" data-bs-target="#product-general-content-{$langCode|escape}" type="button" role="tab">{$langForm.label|escape}</button>
							</li>
							{/foreach}
						</ul>

						<div class="tab-content">
							{foreach $productLangForms as $langCode => $langForm}
							<div class="tab-pane fade{if $langForm@first} show active{/if}" id="product-general-content-{$langCode|escape}" role="tabpanel">
								<div class="row g-3">
									<div class="col-12">
										<label class="form-label">{'Short description'|adminT}</label>
										<textarea name="langs[{$langCode|escape}][short_description]" class="form-control" rows="2" maxlength="512">{$langForm.short_description|default:''|escape}</textarea>
									</div>
									<div class="col-md-5">
										<label class="form-label">{'Meta title'|adminT}</label>
										<input type="text" name="langs[{$langCode|escape}][meta_title]" class="form-control" value="{$langForm.meta_title|default:''|escape}" maxlength="255">
									</div>
									<div class="col-md-7">
										<label class="form-label">{'Meta description'|adminT}</label>
										<textarea name="langs[{$langCode|escape}][meta_description]" class="form-control" rows="1" maxlength="512">{$langForm.meta_description|default:''|escape}</textarea>
									</div>
									<div class="col-12">
										<label class="form-label">{'Long description'|adminT}{if $langForm@first} *{/if}</label>
										<textarea name="langs[{$langCode|escape}][description]" class="form-control wysiwyg-editor" rows="12">{$langForm.description|escape}</textarea>
									</div>
								</div>
							</div>
							{/foreach}
						</div>
					</div>

					<div class="pe-card">
						<div class="pe-card-head">
							<div>
								<h2>{'Catalog'|adminT}</h2>
								<p>{'Category and brand assignment.'|adminT}</p>
							</div>
						</div>
						<div class="row g-3">
							<div class="col-md-6">
								<label class="form-label">{'Category'|adminT}</label>
								<div class="input-group">
									<select name="id_category" id="productCategorySelect" class="form-select" required>
										{foreach $categoryOptions as $cat}
										<option value="{$cat.id_category}"{if $product.id_category == $cat.id_category} selected{/if}>{$cat.category_name|escape}</option>
										{/foreach}
									</select>
									<button type="button" class="btn btn-outline-secondary" id="quickAddCategoryBtn" title="Kategori ekle" aria-label="Kategori ekle">+</button>
								</div>
							</div>
							<div class="col-md-6">
								<label class="form-label">{'Brand'|adminT}</label>
								<div class="input-group">
									<select name="id_brand" id="productBrandSelect" class="form-select" required>
										{foreach $brandOptions as $b}
										<option value="{$b.id_brand}"{if $product.id_brand == $b.id_brand} selected{/if}>{$b.brand_name|escape}</option>
										{/foreach}
									</select>
									<button type="button" class="btn btn-outline-secondary" id="quickAddBrandBtn" title="Marka ekle" aria-label="Marka ekle">+</button>
								</div>
							</div>
						</div>
					</div>
				</div>

				{* ── Detail ── *}
				<div class="pe-tab-pane" id="pePaneDetail" role="tabpanel" data-tab="detail">
					<div class="pe-card">
						<div class="pe-card-head">
							<div>
								<h2>{'Product details'|adminT}</h2>
								<p>{'Type, status, variations and delivery options.'|adminT}</p>
							</div>
						</div>
						<div class="row g-3">
							<div class="col-md-4">
								<label class="form-label">{'Product type'|adminT}</label>
								<select name="product_type" id="productType" class="form-select">
									<option value="physical"{if ($product.product_type|default:'physical') == 'physical'} selected{/if}>{'Physical product'|adminT}</option>
									<option value="virtual"{if ($product.product_type|default:'physical') == 'virtual'} selected{/if}>{'Virtual / digital product'|adminT}</option>
									<option value="pack"{if ($product.product_type|default:'physical') == 'pack'} selected{/if}>Set (paket)</option>
								</select>
							</div>
							<div class="col-md-4">
								<label class="form-label">{'Product status'|adminT}</label>
								<select name="active" class="form-select">
									<option value="1"{if $product.active == 1} selected{/if}>{'Active'|adminT}</option>
									<option value="0"{if $product.active == 0} selected{/if}>{'Inactive'|adminT}</option>
								</select>
							</div>
							<div class="col-md-4">
								<label class="form-label">{'Product label'|adminT}</label>
								<input type="text" name="label" class="form-control" value="{$product.label|default:''|escape}" maxlength="128" placeholder="{'e.g. Buy 3 Pay 2'|adminT}">
							</div>
							<div class="col-md-4">
								<label class="form-label">{'Lead time (days)'|adminT}</label>
								<input type="number" name="cargo_day" class="form-control" value="{$product.cargo_day|default:0|escape}" min="0">
								<div class="form-text">{'0 uses the general shipping time.'|adminT}</div>
							</div>
							<div class="col-md-8">
								<div class="form-check form-switch mt-4">
									<input type="hidden" name="has_variations" value="0">
									<input class="form-check-input" type="checkbox" id="hasVariations" name="has_variations" value="1"{if $hasVariations} checked{/if}>
									<label class="form-check-label" for="hasVariations">{'Use variations'|adminT}</label>
									<div class="form-text">{'Enable to manage combinations (e.g. color + size) below.'|adminT}</div>
								</div>
							</div>
						</div>

						<div class="pe-card mt-3 mb-0" id="variationsWrap"{if !$hasVariations} style="display:none"{/if}>
							<div class="pe-soft-box">
								<div class="mb-3">
									<h2 class="h6 mb-1">{'Product variations'|adminT}</h2>
									<p class="text-muted small mb-0">{'Each row is one combination (e.g. Red + M). Use the same option name in every row.'|adminT}</p>
								</div>

								<div id="variationsPanel"{if !$hasVariations} style="display:none"{/if}>
									<div class="table-responsive">
										<table class="table table-sm table-bordered align-middle mb-2 variation-table bg-white">
											<thead class="table-light">
												<tr>
													<th>{'Option 1'|adminT}</th>
													<th>{'Value'|adminT}</th>
													<th>{'Option 2'|adminT}</th>
													<th>{'Value'|adminT}</th>
													<th>{'SKU'|adminT}</th>
													<th>{'Barcode'|adminT}</th>
													<th>{'Price'|adminT}</th>
													<th>{'Stock'|adminT}</th>
													<th>{'Active'|adminT}</th>
													<th class="text-end" style="width:48px;"></th>
												</tr>
											</thead>
											<tbody id="variationsBody">
												{foreach $variationRows as $idx => $var}
												<tr class="variation-row">
													<td><input type="text" name="variations[{$idx}][option1_name]" class="form-control form-control-sm" value="{$var.option1_name|escape}" placeholder="Renk"></td>
													<td><input type="text" name="variations[{$idx}][option1_value]" class="form-control form-control-sm" value="{$var.option1_value|escape}" placeholder="{'Red'|adminT}"></td>
													<td><input type="text" name="variations[{$idx}][option2_name]" class="form-control form-control-sm" value="{$var.option2_name|escape}" placeholder="Beden"></td>
													<td><input type="text" name="variations[{$idx}][option2_value]" class="form-control form-control-sm" value="{$var.option2_value|escape}" placeholder="M"></td>
													<td><input type="text" name="variations[{$idx}][sku]" class="form-control form-control-sm" value="{$var.sku|escape}"></td>
													<td><input type="text" name="variations[{$idx}][barcode]" class="form-control form-control-sm" value="{$var.barcode|escape}"></td>
													<td><input type="text" name="variations[{$idx}][price]" class="form-control form-control-sm" value="{$var.price|escape}" placeholder="{'Empty = base price'|adminT}"></td>
													<td><input type="number" name="variations[{$idx}][stock]" class="form-control form-control-sm variation-stock-input" value="{$var.stock|escape}" min="0"></td>
													<td class="text-center">
														<input type="hidden" name="variations[{$idx}][id_variation]" value="{$var.id_variation|escape}">
														<input type="checkbox" name="variations[{$idx}][active]" value="1" class="form-check-input"{if $var.active} checked{/if}>
													</td>
													<td class="text-end">
														<button type="button" class="btn btn-sm btn-outline-danger variation-remove" title="{'Remove row'|adminT}">&times;</button>
													</td>
												</tr>
												{/foreach}
											</tbody>
										</table>
									</div>
									<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
										<button type="button" class="btn btn-sm btn-outline-dark" id="addVariationRow">{'+ Add variation'|adminT}</button>
										<span class="small text-muted">{'Total stock:'|adminT} <strong id="variationStockTotal">0</strong></span>
									</div>
								</div>
							</div>
						</div>

						<div class="pe-card mt-3 mb-0" id="virtualKindWrap"{if ($product.product_type|default:'physical') != 'virtual'} style="display:none"{/if}>
							<div class="pe-card-head border-0 pb-0 mb-2">
								<div>
									<h2 class="h6 mb-1">{'Virtual / digital delivery'|adminT}</h2>
									<p class="text-muted small mb-0">{'Delivery type, text content and license keys.'|adminT}</p>
								</div>
							</div>
							<div class="row g-3">
								<div class="col-md-6">
									<label class="form-label">{'Delivery type'|adminT}</label>
									<select name="virtual_kind" id="virtualKind" class="form-select">
										<option value="download"{if $product.virtual_kind|default:'' == 'download'} selected{/if}>{'Downloadable file'|adminT}</option>
										<option value="license"{if $product.virtual_kind|default:'' == 'license'} selected{/if}>{'License key'|adminT}</option>
										<option value="text"{if $product.virtual_kind|default:'' == 'text'} selected{/if}>{'Text delivery'|adminT}</option>
									</select>
								</div>
							</div>
						</div>

						<div class="pe-card mt-3 mb-0" id="virtualTextWrap" style="display:none;">
							<label class="form-label">{'Delivery text'|adminT}</label>
							<textarea name="virtual_text" class="form-control" rows="4" placeholder="{'License info, download instructions or access details shown after order'|adminT}">{$product.virtual_text|default:''|escape}</textarea>
							<div class="form-text">{'For text delivery, this field is sent directly to the customer.'|adminT}</div>
						</div>

						<div class="pe-card mt-3 mb-0" id="virtualLicenseWrap" style="display:none;">
							<label class="form-label">{'License keys'|adminT}</label>
							{if !$isNew && !empty($allLicenses)}
							<div class="mb-3">
								<div class="d-flex justify-content-between align-items-center mb-2">
									<span class="small fw-semibold text-muted">{'Available keys'|adminT} ({$allLicenses|@count})</span>
									{if $licenseStats.used > 0}<span class="small text-muted">{'Used:'|adminT} {$licenseStats.used}</span>{/if}
								</div>
								<div class="border rounded p-2 bg-light small" style="max-height:220px;overflow:auto;">
									{foreach $allLicenses as $lic}
									<div class="py-1 border-bottom border-light-subtle d-flex justify-content-between align-items-center" id="licenseRow_{$lic.id_license}">
										<div class="d-flex align-items-center gap-2">
											<span class="font-monospace license-text">{$lic.license_key|escape}</span>
											<input type="text" class="form-control form-control-sm d-none license-input" value="{$lic.license_key|escape}" style="width: 250px;">
										</div>
										<div class="d-flex gap-1">
											<button type="button" class="btn btn-sm btn-link text-primary p-0 btn-edit-license" data-id="{$lic.id_license}">Düzenle</button>
											<button type="button" class="btn btn-sm btn-link text-success p-0 d-none btn-save-license" data-id="{$lic.id_license}">Kaydet</button>
											<button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2 btn-delete-license" data-id="{$lic.id_license}">Sil</button>
										</div>
									</div>
									{/foreach}
								</div>
							</div>
							{/if}
							<label class="form-label small">{'Add new keys'|adminT}</label>
							<textarea name="license_keys" class="form-control font-monospace" rows="5" placeholder="{'One license key per line'|adminT}"></textarea>
							<div class="form-text">
								{'New keys are added on save; used keys are not removed.'|adminT}
								{if !$isNew && $product.product_type|default:'physical' == 'virtual' && $product.virtual_kind|default:'' == 'license' && !$allLicenses|@count}
								<br>{'No available keys yet.'|adminT} {'Used:'|adminT} <strong>{$licenseStats.used}</strong>
								{/if}
							</div>
						</div>

						<div class="pe-card mt-3 mb-0" id="productOptionsWrap">
							<input type="hidden" name="option_groups_present" value="1">
							<div class="pe-soft-box">
								<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
									<div>
										<h2 class="h6 mb-1">{'Product options'|adminT}</h2>
										<p class="text-muted small mb-0">{'Does not affect stock; customer selects on the product page (e.g. Size, Drink).'|adminT}</p>
									</div>
									<button type="button" class="btn btn-sm btn-outline-dark" id="addOptionGroup">{'+ Add option group'|adminT}</button>
								</div>
								<div id="optionGroupsBody">
									{foreach $optionRows as $idx => $opt}
									<div class="option-group-row border rounded p-3 mb-2 bg-white">
										<div class="row g-2 align-items-start">
											<div class="col-md-4">
												<label class="form-label small mb-1">{'Group name'|adminT}</label>
												<input type="text" name="option_groups[{$idx}][name]" class="form-control form-control-sm" value="{$opt.name|escape}" placeholder="Boyut">
											</div>
											<div class="col-md-2">
												<label class="form-label small mb-1">{'Required'|adminT}</label>
												<div class="form-check mt-1">
													<input type="hidden" name="option_groups[{$idx}][required]" value="0">
													<input type="checkbox" name="option_groups[{$idx}][required]" value="1" class="form-check-input"{if $opt.required} checked{/if}>
												</div>
											</div>
											<div class="col-md-5">
												<label class="form-label small mb-1">{'Values (one per line)'|adminT}</label>
												<textarea name="option_groups[{$idx}][values_text]" class="form-control form-control-sm" rows="3" placeholder="1&#10;1.5&#10;2">{$opt.values_text|escape}</textarea>
											</div>
											<div class="col-md-1 text-end">
												<label class="form-label small mb-1 d-block">&nbsp;</label>
												<button type="button" class="btn btn-sm btn-outline-danger option-group-remove" title="{'Remove group'|adminT}">&times;</button>
											</div>
										</div>
									</div>
									{/foreach}
								</div>
							</div>
						</div>
					</div>

					<div class="pe-card">
						<div class="pe-card-head">
							<div>
								<h2>{'Identifiers'|adminT}</h2>
								<p>{'SKU, barcode and shipping weight (desi).'|adminT}</p>
							</div>
						</div>
						<div class="row g-3">
							<div class="col-md-4">
								<label class="form-label">{'SKU'|adminT} *</label>
								<input required type="text" name="stock_code" class="form-control" value="{$product.stock_code|escape}">
							</div>
							<div class="col-md-4">
								<label class="form-label">{'Barcode'|adminT}</label>
								<input type="text" name="barcode" class="form-control" value="{$product.barcode|escape}">
							</div>
							<div class="col-md-4">
								<label class="form-label">{'Desi'|adminT}</label>
								<input type="number" name="desi" class="form-control" value="{$product.desi|escape}" min="1">
							</div>
						</div>
					</div>
				</div>

				{* ── Pricing ── *}
				<div class="pe-tab-pane" id="pePanePricing" role="tabpanel" data-tab="pricing">
					<div class="pe-card pe-price-grid">
						<div class="pe-card-head">
							<div>
								<h2>{'Price'|adminT} ({$shopCurrencyLabel|escape})</h2>
								<p>{'Sale price, cost, tax and stock fields.'|adminT}</p>
							</div>
						</div>
						<div class="row g-3">
							<div class="col-md-3">
								<label class="form-label" for="costPrice">{'Cost price'|adminT}</label>
								<input type="text" id="costPrice" name="cost" class="form-control" value="{$product.cost|default:'0.00'|escape}">
							</div>
							<div class="col-md-3">
								<label class="form-label" for="productPrice" id="productPriceLabel">{'Sale price'|adminT}</label>
								<input type="text" id="productPrice" name="price" class="form-control" value="{$product.price|escape}">
							</div>
							<div class="col-md-3">
								<label class="form-label" for="productOldPrice">{'Old price'|adminT}</label>
								<input type="text" id="productOldPrice" name="old_price" class="form-control" value="{$product.old_price|escape}">
							</div>
							<div class="col-md-3">
								<label class="form-label">{'Tax (VAT)'|adminT}</label>
								<select name="vat" class="form-select">
									{foreach $taxOptions as $tax}
									<option value="{$tax.rate|escape}"{if $product.vat == $tax.rate} selected{/if}>{$tax.name|escape}{if $tax.legacy|default:false} — {'legacy rate'|adminT}{/if}</option>
									{/foreach}
								</select>
							</div>
							<div class="col-md-3" id="saleUnitWrap">
								<label class="form-label">{'Sale unit'|adminT}</label>
								<select name="sale_unit" id="saleUnit" class="form-select">
									<option value="piece"{if ($product.sale_unit|default:'piece') == 'piece'} selected{/if}>{'Piece'|adminT}</option>
									<option value="m2"{if ($product.sale_unit|default:'piece') == 'm2'} selected{/if}>{'Square meter (m²)'|adminT}</option>
								</select>
								<div class="form-text">{'m²: price and stock are per square meter.'|adminT}</div>
							</div>
							<div class="col-md-3" id="mainStockWrap">
								<label class="form-label" id="productStockLabel">{'Stock'|adminT}</label>
								<input type="number" name="stock" id="productStock" class="form-control" value="{$product.stock|escape}" min="0" step="any">
								<div class="form-text" id="virtualStockHint" style="display:none;">{'For license products, stock is the number of available keys. 0 = unlimited (download/text).'|adminT}</div>
								<div class="form-text" id="variationStockHint" style="display:none;">{'For products with variations, total stock is calculated automatically.'|adminT}</div>
								<div class="form-text" id="m2StockHint" style="display:none;">{'Enter remaining stock in m² (e.g. 125.5).'|adminT}</div>
							</div>
							<div class="col-md-3" id="saleQtyMinWrap" style="display:none;">
								<label class="form-label">{'Min. quantity (m²)'|adminT}</label>
								<input type="number" name="sale_qty_min" id="saleQtyMin" class="form-control" value="{$product.sale_qty_min|default:'0.01'|escape}" min="0.001" step="any">
							</div>
							<div class="col-md-3" id="saleQtyStepWrap" style="display:none;">
								<label class="form-label">{'Quantity step (m²)'|adminT}</label>
								<input type="number" name="sale_qty_step" id="saleQtyStep" class="form-control" value="{$product.sale_qty_step|default:'0.01'|escape}" min="0.001" step="any">
							</div>
						</div>
					</div>
				</div>

				{* ── Media ── *}
				<div class="pe-tab-pane" id="pePaneMedia" role="tabpanel" data-tab="media">
					<div class="pe-card pe-media" id="productImageUploader"
						data-enabled="{if !$isNew}1{else}0{/if}"
						data-token="{$adminToken|escape}"
						data-product-id="{$idProduct}"
						data-upload-url="{$adminUrl}product?id={$idProduct}"
						data-media-api="{$domain}api/admin-media.php">
						<div class="pe-card-head">
							<div>
								<h2>{'Images'|adminT}</h2>
								<p>{'Pick from media library or upload new files.'|adminT}</p>
							</div>
						</div>

						{if $isNew}
						<div class="pe-dropzone is-disabled" data-dropzone>
							<div class="pe-dropzone-icon">
								<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
							</div>
							<strong>{'Save the product first'|adminT}</strong>
							<span>{'You can use the media library after saving'|adminT}</span>
						</div>
						{else}
						<button type="button" class="pe-dropzone w-100 border-0" data-dropzone data-open-media>
							<div class="pe-dropzone-icon">
								<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
							</div>
							<strong>{'Open media library'|adminT}</strong>
							<span class="pe-dropzone-hint">{'Select existing images or upload new files'|adminT}</span>
						</button>
						<p class="pe-media-status" data-status></p>
						<div class="pe-media-gallery" data-gallery>
							{if $product.images|@count}
								{foreach $product.images as $img}
								<div class="pe-media-card{if $img.cover} is-cover{/if}" data-image-id="{$img.id_image}">
									<div class="pe-media-thumb"><img src="{$img.url}" alt=""></div>
									{if $img.cover}<span class="pe-media-badge">{'Cover'|adminT}</span>{/if}
									<div class="pe-media-actions">
										{if !$img.cover}
										<button type="button" class="btn btn-sm btn-outline-dark" data-action="cover">{'Cover'|adminT}</button>
										{/if}
										<button type="button" class="btn btn-sm btn-outline-danger" data-action="delete">{'Delete'|adminT}</button>
									</div>
								</div>
								{/foreach}
							{else}
							<p class="pe-media-empty">{'No images yet. Select from the media library.'|adminT}</p>
							{/if}
						</div>
						{/if}
					</div>

					<div class="pe-card">
						<div class="pe-card-head">
							<div>
								<h2>{'Product video'|adminT}</h2>
								<p>{'YouTube link — shown as a tab on the product page.'|adminT}</p>
							</div>
						</div>
						<input type="url" id="productVideo" name="product_video" class="form-control" value="{$product.product_video|default:''|escape}" placeholder="https://www.youtube.com/watch?v=...">
					</div>

					{if $adminHooks.admin_product_button}
					<div class="pe-card">{$adminHooks.admin_product_button nofilter}</div>
					{/if}
				</div>

				{* ── Log ── *}
				{if !$isNew}
				<div class="pe-tab-pane" id="pePaneLog" role="tabpanel" data-tab="log">
					<div class="pe-card">
						<div class="pe-card-head">
							<div>
								<h2>{'Log'|adminT}</h2>
								<p>{'Product activity history.'|adminT}</p>
							</div>
						</div>
						{if $productLogs|@count}
						<ul class="list-unstyled mb-0 pe-log-list">
							{foreach $productLogs as $log}
							{assign var=logBadge value='secondary'}
							{assign var=logLabel value=$log.event_type}
							{if $log.event_type == 'sold'}
								{assign var=logBadge value='success'}
								{assign var=logLabel value={'Sold'|adminT}}
							{elseif $log.event_type == 'price_change'}
								{assign var=logBadge value='warning'}
								{assign var=logLabel value={'Price'|adminT}}
							{elseif $log.event_type == 'stock_change' || $log.event_type == 'stock_restored'}
								{assign var=logBadge value='info'}
								{assign var=logLabel value={'Stock'|adminT}}
							{elseif $log.event_type == 'created'}
								{assign var=logLabel value={'Created'|adminT}}
							{elseif $log.event_type == 'updated'}
								{assign var=logLabel value={'Updated'|adminT}}
							{/if}
							<li class="border-start border-3 ps-3 pb-3 mb-3{if $log@last} mb-0 pb-0{/if} border-{$logBadge}">
								<div class="small text-muted mb-1">{$log.date_formatted|escape}</div>
								<div>
									<span class="badge bg-{$logBadge} me-1">{$logLabel|escape}</span>
									{$log.message|escape}
								</div>
							</li>
							{/foreach}
						</ul>
						{else}
						<p class="text-muted mb-0">{'No log entries yet.'|adminT}</p>
						{/if}
					</div>
				</div>
				{/if}
			</div>
		</div>
	</form>

	{if !$isNew}
	<div class="pe-card" id="virtualFilePanel" style="display:none;">
		<div class="pe-card-head">
			<div>
				<h2>{'Digital file'|adminT}</h2>
				<p>{'Upload a file for downloadable products (ZIP, PDF, RAR… max 50 MB).'|adminT}</p>
			</div>
		</div>
		{if $product.virtual_file_name}
		<p class="mb-2"><strong>{'Uploaded file:'|adminT}</strong> {$product.virtual_file_name|escape}</p>
		<form method="post" action="{$adminUrl}product?id={$idProduct}" class="d-inline mb-3" onsubmit="return confirm('{'Digital file'|adminT} silinsin mi?');">
			<input type="hidden" name="deleteVirtualFile" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<button type="submit" class="btn btn-sm btn-outline-danger">{'Delete file'|adminT}</button>
		</form>
		{else}
		<p class="text-muted small mb-3">{'No digital file uploaded yet.'|adminT}</p>
		{/if}
		<form method="post" action="{$adminUrl}product?id={$idProduct}" enctype="multipart/form-data">
			<input type="hidden" name="uploadVirtualFile" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<input type="file" name="virtual_file" class="form-control form-control-sm mb-2"{if !$product.virtual_file_name} required{/if}>
			<button type="submit" class="btn btn-sm btn-dark">{'Upload digital file'|adminT}</button>
		</form>
	</div>
	{/if}
</div>

{if !$isNew}
<div class="modal fade ml-modal" id="adminMediaLibraryModal" tabindex="-1" aria-labelledby="adminMediaLibraryTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="adminMediaLibraryTitle">{'File manager'|adminT}</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Close'|adminT}"></button>
			</div>

			<div class="ml-toolbar">
				<div class="ml-toolbar-group">
					<span class="ml-toolbar-label">{'Actions'|adminT}</span>
					<button type="button" class="btn btn-sm btn-outline-dark" data-ml-upload-btn title="{'Add new file'|adminT}">
						{'+ Add file'|adminT}
					</button>
					<button type="button" class="btn btn-sm btn-outline-secondary" data-ml-mkdir title="{'New folder'|adminT}">
						{'+ Folder'|adminT}
					</button>
					<button type="button" class="btn btn-sm btn-outline-secondary" data-ml-home-media title="{'Media folder'|adminT}">
						Medya
					</button>
					<input type="file" data-ml-upload accept="image/jpeg,image/png,image/webp,image/gif" multiple hidden>
				</div>
				<div class="ml-toolbar-group ms-auto">
					<span class="ml-toolbar-label">{'Filters'|adminT}</span>
					<input type="search" class="form-control form-control-sm" style="width:180px" placeholder="{'Filter...'|adminT}" data-ml-filter>
				</div>
			</div>

			<div class="ml-nav">
				<div class="ml-breadcrumbs" data-ml-crumbs></div>
				<button type="button" class="btn btn-sm btn-outline-secondary" data-ml-refresh>{'Refresh'|adminT}</button>
			</div>

			<div class="ml-body">
				<div class="ml-grid" data-ml-grid>
					<div class="ml-loading">{'Loading…'|adminT}</div>
				</div>
			</div>

			<div class="ml-footer">
				<div>
					<div class="ml-footer-meta" data-ml-meta>{'Select an image or upload new'|adminT}</div>
					<p class="small text-muted mb-0 mt-1" data-ml-status></p>
				</div>
				<div class="ml-footer-actions">
					<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{'Cancel'|adminT}</button>
					<button type="button" class="btn btn-dark btn-sm" data-ml-attach disabled>{'Add selected to product'|adminT}</button>
				</div>
			</div>
		</div>
	</div>
</div>
{/if}

<div class="modal fade" id="quickAddCategoryModal" tabindex="-1" aria-labelledby="quickAddCategoryModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="quickAddCategoryModalLabel">Kategori ekle</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Close'|adminT}"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label class="form-label" for="quickCategoryName">{'Category name'|adminT}</label>
					<input type="text" class="form-control" id="quickCategoryName" maxlength="64" required>
				</div>
				<div class="mb-0">
					<label class="form-label" for="quickCategoryParent">{'Parent category'|adminT}</label>
					<select class="form-select" id="quickCategoryParent">
						<option value="0">{'None (root)'|adminT}</option>
						{foreach $categoryOptions as $cat}
						<option value="{$cat.id_category}">{$cat.category_name|escape}</option>
						{/foreach}
					</select>
				</div>
				<div class="alert alert-danger py-2 mt-3 mb-0 d-none" id="quickCategoryError"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{'Cancel'|adminT}</button>
				<button type="button" class="btn btn-dark btn-sm" id="quickCategorySaveBtn">{'Save'|adminT}</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="quickAddBrandModal" tabindex="-1" aria-labelledby="quickAddBrandModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="quickAddBrandModalLabel">Marka ekle</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Close'|adminT}"></button>
			</div>
			<div class="modal-body">
				<div class="mb-0">
					<label class="form-label" for="quickBrandName">Marka adı</label>
					<input type="text" class="form-control" id="quickBrandName" maxlength="48" required>
				</div>
				<div class="alert alert-danger py-2 mt-3 mb-0 d-none" id="quickBrandError"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{'Cancel'|adminT}</button>
				<button type="button" class="btn btn-dark btn-sm" id="quickBrandSaveBtn">{'Save'|adminT}</button>
			</div>
		</div>
	</div>
</div>

<script src="{$domain}templates/admin/js/product-variations.js?v={$smarty.now}"></script>
<script src="{$domain}templates/admin/js/product-options.js?v={$smarty.now}"></script>
<script src="{$domain}templates/admin/js/product-images.js?v={$smarty.now}"></script>
<script>
window.productCatalogQuickConfig = {
	adminUrl: '{$adminUrl|escape:'javascript'}product{if !$isNew}?id={$idProduct}{/if}',
	token: '{$adminToken|escape:'javascript'}',
	parentRootLabel: "{'None (root)'|adminT|escape:'javascript'}"
};
</script>
<script src="{$domain}templates/admin/js/product-catalog-quick.js?v={$smarty.now}"></script>
<script>
(function () {
	var typeEl = document.getElementById('productType');
	var kindEl = document.getElementById('virtualKind');
	var kindWrap = document.getElementById('virtualKindWrap');
	var textWrap = document.getElementById('virtualTextWrap');
	var licenseWrap = document.getElementById('virtualLicenseWrap');
	var filePanel = document.getElementById('virtualFilePanel');
	var stockHint = document.getElementById('virtualStockHint');
	var stockInput = document.getElementById('productStock');
	var saleUnitEl = document.getElementById('saleUnit');
	var saleUnitWrap = document.getElementById('saleUnitWrap');
	var saleQtyMinWrap = document.getElementById('saleQtyMinWrap');
	var saleQtyStepWrap = document.getElementById('saleQtyStepWrap');
	var m2StockHint = document.getElementById('m2StockHint');
	var priceLabel = document.getElementById('productPriceLabel');
	var stockLabel = document.getElementById('productStockLabel');
	var hasVariationsEl = document.getElementById('hasVariations');
	var variationsWrap = document.getElementById('variationsWrap');
	var variationsPanel = document.getElementById('variationsPanel');
	var tabButtons = document.querySelectorAll('.pe-tab-btn');
	var tabPanes = document.querySelectorAll('.pe-tab-pane');
	var activeTab = 'general';

	function switchTab(tabName) {
		activeTab = tabName;

		tabButtons.forEach(function (btn) {
			var isActive = btn.getAttribute('data-tab') === tabName;
			btn.classList.toggle('pe-tab-btn--active', isActive);
			btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
		});

		tabPanes.forEach(function (pane) {
			var isActive = pane.getAttribute('data-tab') === tabName;
			pane.classList.toggle('pe-tab-pane--active', isActive);
		});
	}

	tabButtons.forEach(function (btn) {
		btn.addEventListener('click', function () {
			var tabName = btn.getAttribute('data-tab');
			if (tabName && btn.style.display !== 'none') {
				switchTab(tabName);
			}
		});
	});

	function refreshCombinationsTab() {
		var show = !!(hasVariationsEl && hasVariationsEl.checked && typeEl
			&& typeEl.value !== 'virtual' && typeEl.value !== 'pack');
		if (variationsWrap) {
			variationsWrap.style.display = show ? '' : 'none';
		}
		if (variationsPanel) {
			variationsPanel.style.display = show ? '' : 'none';
		}
	}

	function refreshSaleUnitFields() {
		var isPhysical = typeEl && typeEl.value === 'physical';
		var isM2 = isPhysical && saleUnitEl && saleUnitEl.value === 'm2';

		if (saleUnitWrap) saleUnitWrap.style.display = isPhysical ? '' : 'none';
		if (saleQtyMinWrap) saleQtyMinWrap.style.display = isM2 ? '' : 'none';
		if (saleQtyStepWrap) saleQtyStepWrap.style.display = isM2 ? '' : 'none';
		if (m2StockHint) m2StockHint.style.display = isM2 ? '' : 'none';
		if (priceLabel) {
			priceLabel.textContent = isM2
				? "{'Sale price (per m²)'|adminT|escape:'javascript'}"
				: "{'Sale price'|adminT|escape:'javascript'}";
		}
		if (stockLabel) {
			stockLabel.textContent = isM2
				? "{'Stock (m²)'|adminT|escape:'javascript'}"
				: "{'Stock'|adminT|escape:'javascript'}";
		}
	}

	function refreshVirtualFields() {
		var isVirtual = typeEl && typeEl.value === 'virtual';
		var isPack = typeEl && typeEl.value === 'pack';
		var kind = kindEl ? kindEl.value : '';

		if (kindWrap) kindWrap.style.display = isVirtual ? '' : 'none';
		if (textWrap) textWrap.style.display = isVirtual && kind === 'text' ? '' : 'none';
		if (licenseWrap) licenseWrap.style.display = isVirtual && kind === 'license' ? '' : 'none';
		if (filePanel) filePanel.style.display = isVirtual && kind === 'download' ? '' : 'none';
		if (stockHint) stockHint.style.display = isVirtual ? '' : 'none';
		if (stockInput) {
			stockInput.readOnly = (isVirtual && kind === 'license') || isPack;
			stockInput.title = isPack ? 'Set stoğu bileşenlerden hesaplanır' : '';
		}

		if (window.ProductVariations) {
			window.ProductVariations.refreshForProductType(isVirtual || isPack);
		}

		refreshCombinationsTab();
		refreshSaleUnitFields();
	}

	window.refreshVirtualFields = refreshVirtualFields;

	if (typeEl) typeEl.addEventListener('change', refreshVirtualFields);
	if (kindEl) kindEl.addEventListener('change', refreshVirtualFields);
	if (saleUnitEl) saleUnitEl.addEventListener('change', refreshSaleUnitFields);
	if (hasVariationsEl) {
		hasVariationsEl.addEventListener('change', refreshCombinationsTab);
	}
	refreshVirtualFields();
})();

(function() {
	document.querySelectorAll('.btn-edit-license').forEach(function(btn) {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			var id = this.getAttribute('data-id');
			var row = document.getElementById('licenseRow_' + id);
			if (!row) return;
			
			row.querySelector('.license-text').classList.add('d-none');
			row.querySelector('.license-input').classList.remove('d-none');
			row.querySelector('.btn-edit-license').classList.add('d-none');
			row.querySelector('.btn-save-license').classList.remove('d-none');
		});
	});

	document.querySelectorAll('.btn-save-license').forEach(function(btn) {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			var id = this.getAttribute('data-id');
			var row = document.getElementById('licenseRow_' + id);
			if (!row) return;
			
			var newKey = row.querySelector('.license-input').value;
			if (!newKey.trim()) {
				alert('Lisans anahtarı boş olamaz');
				return;
			}
			
			var formData = new FormData();
			formData.append('updateLicense', '1');
			formData.append('id_license', id);
			formData.append('license_key', newKey);
			formData.append('token', document.querySelector('input[name="token"]').value);
			
			fetch('', {
				method: 'POST',
				body: formData
			}).then(res => res.json()).then(data => {
				if (data.success) {
					row.querySelector('.license-text').textContent = newKey;
					row.querySelector('.license-text').classList.remove('d-none');
					row.querySelector('.license-input').classList.add('d-none');
					row.querySelector('.btn-edit-license').classList.remove('d-none');
					row.querySelector('.btn-save-license').classList.add('d-none');
				} else {
					alert('Güncellenemedi. Anahtar kullanımda veya geçersiz olabilir.');
				}
			});
		});
	});

	document.querySelectorAll('.btn-delete-license').forEach(function(btn) {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			if (!confirm('Bu lisans anahtarını silmek istediğinize emin misiniz?')) return;
			
			var id = this.getAttribute('data-id');
			var row = document.getElementById('licenseRow_' + id);
			
			var formData = new FormData();
			formData.append('deleteLicense', '1');
			formData.append('id_license', id);
			formData.append('token', document.querySelector('input[name="token"]').value);
			
			fetch('', {
				method: 'POST',
				body: formData
			}).then(res => res.json()).then(data => {
				if (data.success) {
					row.remove();
				} else {
					alert('Silinemedi.');
				}
			});
		});
	});
})();
</script>
