{if $flash}
<div class="alert alert-info py-2">{$flash|escape}</div>
{/if}

<ul class="nav nav-tabs mb-3">
	<li class="nav-item">
		<a class="nav-link{if $tab == 'settings' || $tab == ''} active{/if}" href="{$adminUrl}module-shopier?tab=settings">Ayarlar</a>
	</li>
	<li class="nav-item">
		<a class="nav-link{if $tab == 'categories'} active{/if}" href="{$adminUrl}module-shopier?tab=categories">Kategori Eşleme</a>
	</li>
	<li class="nav-item">
		<a class="nav-link{if $tab == 'syncs'} active{/if}" href="{$adminUrl}module-shopier?tab=syncs">Senkron Kayıtları</a>
	</li>
</ul>

{if $tab == 'settings' || $tab == ''}
<div class="admin-panel p-3" style="max-width: 720px;">
	<h2 class="h6 mb-3">Shopier API Ayarları</h2>
	<form method="post">
		<input type="hidden" name="saveShopier" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="mb-3">
			<label class="form-label">Kişisel Erişim Anahtarı (Bearer Token)</label>
			<input type="password" name="api_token" class="form-control" value="{$shopierApiToken|escape}" autocomplete="off">
			<div class="form-text">Shopier Geliştirici Portalı → Kişisel erişim anahtarı</div>
		</div>

		<div class="mb-3">
			<label class="form-label">Varsayılan Shopier Kategori ID</label>
			<div class="input-group">
				<input type="text" name="default_category_id" id="shopierDefaultCategoryId" class="form-control" value="{$shopierDefaultCategoryId|escape}">
				<button type="button" class="btn btn-outline-secondary shopier-load-categories" data-target="#shopierDefaultCategoryId">Kategorileri Getir</button>
			</div>
			<div class="form-text">Kategori eşlemesi olmayan ürünler için kullanılır</div>
			<div class="shopier-category-list small mt-2" data-target="#shopierDefaultCategoryId"></div>
		</div>

		<div class="row g-3">
			<div class="col-md-6">
				<label class="form-label">Ürün tipi</label>
				<select name="product_type" class="form-select">
					<option value="physical"{if $shopierProductType == 'physical'} selected{/if}>Fiziksel</option>
					<option value="digital"{if $shopierProductType == 'digital'} selected{/if}>Dijital</option>
				</select>
			</div>
			<div class="col-md-6">
				<label class="form-label">Kargo ödemesi</label>
				<select name="shipping_payer" class="form-select">
					<option value="buyerPays"{if $shopierShippingPayer == 'buyerPays'} selected{/if}>Alıcı öder</option>
					<option value="sellerPays"{if $shopierShippingPayer == 'sellerPays'} selected{/if}>Satıcı öder</option>
				</select>
			</div>
			<div class="col-md-4">
				<label class="form-label">Kargo ücreti (opsiyonel)</label>
				<input type="text" name="shipping_price" class="form-control" value="{$shopierShippingPrice|escape}" placeholder="ör. 49.90">
			</div>
			<div class="col-md-4">
				<label class="form-label">Sevkiyat süresi (gün)</label>
				<input type="number" name="dispatch_duration" class="form-control" min="1" max="3" value="{$shopierDispatchDuration}">
			</div>
			<div class="col-md-4">
				<label class="form-label">Sıralama puanı</label>
				<input type="number" name="placement_score" class="form-control" min="0" value="{$shopierPlacementScore}">
			</div>
		</div>

		<div class="form-check mt-3 mb-3">
			<input class="form-check-input" type="checkbox" name="auto_sync" id="shopierAutoSync" value="1"{if $shopierAutoSync} checked{/if}>
			<label class="form-check-label" for="shopierAutoSync">Shopier'e daha önce gönderilmiş ürünleri kayıt sonrası otomatik güncelle</label>
		</div>

		<button type="submit" class="btn btn-dark">Kaydet</button>
	</form>

	<div class="alert alert-light border small mt-3 mb-0">
		API: <code>https://api.shopier.com/v1/products</code><br>
		Görsellerin herkese açık HTTPS URL olması gerekir (localhost çalışmaz).
	</div>
</div>
{/if}

{if $tab == 'categories'}
<div class="admin-panel p-3">
	<h2 class="h6 mb-3">FShop → Shopier Kategori Eşlemesi</h2>
	<p class="small text-muted">Her FShop kategorisi için Shopier kategori ID tanımlayabilirsiniz. Boş bırakılanlar varsayılan kategoriyi kullanır.</p>

	<form method="post">
		<input type="hidden" name="saveShopierCategories" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="mb-2">
			<button type="button" class="btn btn-sm btn-outline-secondary shopier-load-categories" data-list="1">Shopier Kategorilerini Yükle</button>
		</div>
		<div class="shopier-category-list small mb-3" data-list="1"></div>

		<div class="table-responsive">
			<table class="table table-sm align-middle">
				<thead>
					<tr>
						<th>FShop Kategorisi</th>
						<th style="width:280px">Shopier Kategori ID</th>
					</tr>
				</thead>
				<tbody>
					{foreach $categoryOptions as $cat}
					<tr>
						<td>{$cat.category_name|escape}</td>
						<td>
							<input type="text" class="form-control form-control-sm" name="shopier_category[{$cat.id_category}]" value="{$categoryMaps[$cat.id_category]|default:''|escape}">
						</td>
					</tr>
					{/foreach}
				</tbody>
			</table>
		</div>

		<button type="submit" class="btn btn-dark">Eşlemeleri Kaydet</button>
	</form>
</div>
{/if}

{if $tab == 'syncs'}
<div class="admin-panel p-3">
	<h2 class="h6 mb-3">Son Senkronizasyonlar</h2>
	{if !$recentSyncs}
	<p class="text-muted small mb-0">Henüz kayıt yok.</p>
	{else}
	<div class="table-responsive">
		<table class="table table-sm table-hover align-middle mb-0">
			<thead>
				<tr>
					<th>Ürün</th>
					<th>Shopier ID</th>
					<th>Durum</th>
					<th>Son işlem</th>
				</tr>
			</thead>
			<tbody>
				{foreach $recentSyncs as $row}
				<tr>
					<td>
						<a href="{$adminUrl}product?id={$row.id_product}">{$row.product_name|escape}</a>
					</td>
					<td class="small">
						{if $row.shopier_url}
						<a href="{$row.shopier_url|escape}" target="_blank" rel="noopener">{$row.shopier_id|escape}</a>
						{else}
						{$row.shopier_id|escape}
						{/if}
					</td>
					<td>
						{if $row.last_status == 'synced'}
						<span class="badge text-bg-success">Senkron</span>
						{elseif $row.last_status == 'failed'}
						<span class="badge text-bg-danger" title="{$row.last_error|escape}">Hata</span>
						{else}
						<span class="badge text-bg-secondary">{$row.last_status|escape}</span>
						{/if}
					</td>
					<td class="small text-muted">{$row.last_sync_at|escape}</td>
				</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
	{/if}
</div>
{/if}

<script>
window.shopierCategoriesApiUrl = {$categoriesApiUrl|@json_encode nofilter};
window.shopierAdminToken = {$adminToken|@json_encode nofilter};
</script>
