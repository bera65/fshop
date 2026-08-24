{if $flash}
<div class="alert alert-{$flashType|default:'success'} py-2">{$flash|escape}</div>
{/if}

<p class="mb-3"><a href="{$moduleDetailUrl|escape}">&larr; Modül detayına dön</a></p>

<div class="row g-4">
	<div class="col-lg-5">
		<div class="admin-panel p-3">
			<h2 class="h6 mb-3">{if $editTab}Sekme Düzenle{else}Yeni Sekme Ekle{/if}</h2>

			<form method="post" id="productTabForm">
				<input type="hidden" name="saveProductTab" value="1">
				<input type="hidden" name="token" value="{$adminToken}">
				<input type="hidden" name="id_tab" value="{$editTab.id_tab|default:0}">

				<div class="mb-3">
					<label class="form-label" for="pctTitle">Sekme başlığı</label>
					<input type="text" class="form-control" id="pctTitle" name="title" value="{$editTab.title|default:''|escape}" maxlength="128" required placeholder="Örn: Teslimat Bilgileri">
				</div>

				<div class="mb-3">
					<label class="form-label">Gösterim</label>
					<div class="form-check">
						<input class="form-check-input" type="radio" name="scope" id="pctScopeAll" value="all"{if !$editTab || $editTab.scope != 'selected'} checked{/if}>
						<label class="form-check-label" for="pctScopeAll">Tüm ürünlerde göster</label>
					</div>
					<div class="form-check">
						<input class="form-check-input" type="radio" name="scope" id="pctScopeSelected" value="selected"{if $editTab && $editTab.scope == 'selected'} checked{/if}>
						<label class="form-check-label" for="pctScopeSelected">Yalnızca seçili ürünlerde göster</label>
					</div>
				</div>

				<div class="mb-3 pct-product-picker{if !$editTab || $editTab.scope != 'selected'} d-none{/if}" id="pctProductPicker">
					<label class="form-label" for="pctProductSearch">Ürünler</label>
					<input type="search" class="form-control form-control-sm mb-2" id="pctProductSearch" placeholder="Ürün adı ile filtrele…">
					<div class="pct-product-list border rounded p-2">
						{foreach $productOptions as $product}
						<div class="form-check pct-product-item" data-name="{$product.product_name|lower|escape}">
							<input class="form-check-input" type="checkbox" name="product_ids[]" value="{$product.id_product}" id="pctProduct{$product.id_product}"{if $editTab && in_array($product.id_product, $editTab.product_ids)} checked{/if}>
							<label class="form-check-label" for="pctProduct{$product.id_product}">{$product.product_name|escape}</label>
						</div>
						{/foreach}
					</div>
					<div class="form-text">Birden fazla ürün seçebilirsiniz.</div>
				</div>

				<div class="mb-3">
					<label class="form-label" for="pctContent">Sekme içeriği</label>
					<textarea id="pctContent" name="content" class="form-control wysiwyg-editor" rows="12">{$editTab.content|default:''|escape}</textarea>
					<div class="form-text">Başlık, liste, tablo, görsel ve link ekleyebilirsiniz.</div>
				</div>

				<div class="row g-3 mb-3">
					<div class="col-6">
						<label class="form-label" for="pctPosition">Sıra</label>
						<input type="number" class="form-control" id="pctPosition" name="position" value="{$editTab.position|default:0}" min="0">
					</div>
					<div class="col-6 d-flex align-items-end">
						<div class="form-check mb-2">
							<input class="form-check-input" type="checkbox" name="active" value="1" id="pctActive"{if !$editTab || $editTab.active} checked{/if}>
							<label class="form-check-label" for="pctActive">Aktif</label>
						</div>
					</div>
				</div>

				<div class="d-flex flex-wrap gap-2">
					<button type="submit" class="btn btn-dark">Kaydet</button>
					{if $editTab}
					<a href="{$moduleConfigUrl|escape}" class="btn btn-outline-secondary">Yeni sekme</a>
					{/if}
				</div>
			</form>
		</div>
	</div>

	<div class="col-lg-7">
		<div class="admin-panel p-0">
			<div class="table-responsive">
				<table class="table table-sm mb-0 align-middle">
					<thead>
						<tr>
							<th>Başlık</th>
							<th>Gösterim</th>
							<th>Sıra</th>
							<th>Durum</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						{foreach $tabs as $tab}
						<tr>
							<td>
								<strong>{$tab.title|escape}</strong>
								{if $tab.scope == 'selected'}
								<div class="text-muted small">{$tab.product_count} ürün</div>
								{/if}
							</td>
							<td class="small">{$tab.scope_label|escape}</td>
							<td>{$tab.position|escape}</td>
							<td>
								{if $tab.active}
								<span class="badge text-bg-success">Aktif</span>
								{else}
								<span class="badge text-bg-secondary">Pasif</span>
								{/if}
							</td>
							<td class="text-end">
								<div class="d-inline-flex gap-1">
									<a href="{$moduleConfigUrl|escape}?edit={$tab.id_tab}" class="btn btn-sm btn-outline-dark">Düzenle</a>
									<form method="post" class="d-inline">
										<input type="hidden" name="toggleProductTab" value="1">
										<input type="hidden" name="token" value="{$adminToken}">
										<input type="hidden" name="id_tab" value="{$tab.id_tab}">
										<button type="submit" class="btn btn-sm btn-outline-secondary">{if $tab.active}Pasif{else}Aktif{/if}</button>
									</form>
									<form method="post" class="d-inline" data-confirm-message="Bu sekme silinsin mi?">
										<input type="hidden" name="deleteProductTab" value="1">
										<input type="hidden" name="token" value="{$adminToken}">
										<input type="hidden" name="id_tab" value="{$tab.id_tab}">
										<button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
									</form>
								</div>
							</td>
						</tr>
						{foreachelse}
						<tr>
							<td colspan="5" class="text-muted text-center py-4">Henüz sekme eklenmedi.</td>
						</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
