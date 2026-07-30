{include file='admin/marketplace/_nav.tpl'}

<div class="admin-panel p-3">
	<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
		<div>
			<h2 class="h5 mb-1">{'Marketplace Products'|adminT}</h2>
			<p class="text-muted small mb-0">{'Manage product marketplace connections and stock levels'|adminT}</p>
		</div>
		<button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#trendyolProductImportModal">{'Import Product'|adminT}</button>
	</div>

	<form method="get" action="{$adminUrl}marketplace-products" class="row g-2 mb-3">
		<div class="col-md-4">
			<input type="text" name="q" class="form-control form-control-sm" placeholder="{'Product name or barcode'|adminT}" value="{$searchQuery|escape}">
		</div>
		<div class="col-md-3">
			<select name="filter" class="form-select form-select-sm">
				<option value="all"{if $linkFilter == 'all'} selected{/if}>{'All'|adminT}</option>
				<option value="linked"{if $linkFilter == 'linked'} selected{/if}>{'Marketplace Products'|adminT}</option>
				<option value="unlinked"{if $linkFilter == 'unlinked'} selected{/if}>{'Store Products'|adminT}</option>
			</select>
		</div>
		<div class="col-md-auto d-flex gap-2">
			<button type="submit" class="btn btn-sm btn-dark">{'Filter'|adminT}</button>
			<a href="{$productsUrl|escape}" class="btn btn-sm btn-outline-secondary">{'Clean'|adminT}</a>
		</div>
	</form>

	{if !$catalogProducts|@count}
	<p class="text-muted mb-0">{'Not found'|adminT}</p>
	{else}
	<div class="vstack gap-2 marketplace-catalog">
		{foreach $catalogProducts as $item}
		{assign var=row value=$item.row}
		<div class="border rounded overflow-hidden marketplace-item" id="mp-item-{$row.id_product}">
			<div class="marketplace-item-header d-flex align-items-center gap-2 p-2 bg-white">
				<div class="flex-grow-1 d-flex align-items-center gap-3 p-1 min-w-0">
					<img src="{$row.image_url|escape}" alt="" width="48" height="48" style="object-fit:contain" class="rounded flex-shrink-0">
					<div class="flex-grow-1 min-w-0">
						<div class="fw-semibold text-truncate">{$row.product_name|escape}</div>
						<div class="small text-muted">
							#{$row.id_product}
							{if $row.stock_code|default:'' != ''} · Stok Code: {$row.stock_code|escape}{/if}
							{if $row.barcode|default:'' != ''} · Barkod: {$row.barcode|escape}{/if}
							{if $row.category_name|default:'' != ''} · {$row.category_name|escape}{/if}
							 · {'Store'|adminT} {$row.price_formatted}
							<span class="cursor-pointer quick-stock-btn px-2 py-1 float-right" role="button" data-id="{$row.id_product}" data-name="{$row.product_name|escape}" data-stock="{$row.stock}" title="{'Add Stock'|adminT}" style="cursor:pointer;">
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil-line-icon lucide-pencil-line"><path d="M13 21h8"/><path d="m15 5 4 4"/><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
							{'Stock'|adminT} <strong class="stock-display-val">{$row.stock}</strong></span>
						</div>
					</div>
				</div>
			</div>
			<div class="marketplace-connections" aria-label="Pazaryeri bağlantıları">
				{foreach $marketplacePlatforms as $platform}
				{if $platform.key == 'trendyol' && $platform.active}
				<button type="button" class="marketplace-connection marketplace-connection--trendyol{if $row.ty_linked} is-linked{else} is-unlinked{/if}"
					data-product-id="{$row.id_product}" data-bs-toggle="modal" data-bs-target="#mp-modal-{$row.id_product}">
					<img class="marketplace-platform-icon" src="{$domain}templates/admin/img/trendyol.png" alt="Trendyol">
					<span class="marketplace-connection-body">
						<span class="marketplace-platform-name">Trendyol</span>
						{if $row.ty_linked}
						<span class="marketplace-connection-status">{'Connected'|adminT}{if $row.ty_sale_price > 0} · {$row.ty_sale_price|escape} TL{/if}</span>
						{else}
						<span class="marketplace-connection-status">{'Add New'|adminT}</span>
						{/if}
					</span>
				</button>
				{elseif $platform.key == 'hepsiburada' && $platform.active}
				<button type="button" class="marketplace-connection marketplace-connection--hepsiburada{if $row.hb_linked} is-linked{else} is-unlinked{/if}"
					data-product-id="{$row.id_product}" data-bs-toggle="modal" data-bs-target="#mp-modal-hb-{$row.id_product}">
					<img class="marketplace-platform-icon" src="{$domain}templates/admin/img/hepsiburada.png" alt="Hepsiburada" onerror="this.style.display='none'">
					<span class="marketplace-connection-body">
						<span class="marketplace-platform-name">Hepsiburada</span>
						{if $row.hb_linked}
						<span class="marketplace-connection-status">{'Connected'|adminT}{if $row.hb_sale_price > 0} · {$row.hb_sale_price|escape} TL{/if}</span>
						{else}
						<span class="marketplace-connection-status">{'Add New'|adminT}</span>
						{/if}
					</span>
				</button>
				{elseif $platform.key == 'n11' && $platform.active}
				<button type="button" class="marketplace-connection marketplace-connection--n11{if $row.n11_linked} is-linked{else} is-unlinked{/if}"
					data-product-id="{$row.id_product}" data-bs-toggle="modal" data-bs-target="#mp-modal-n11-{$row.id_product}">
					<img class="marketplace-platform-icon" src="{$domain}templates/admin/img/n11.png" alt="N11" onerror="this.style.display='none'">
					<span class="marketplace-connection-body">
						<span class="marketplace-platform-name">N11</span>
						{if $row.n11_linked}
						<span class="marketplace-connection-status">{'Connected'|adminT}{if $row.n11_sale_price > 0} · {$row.n11_sale_price|escape} TL{/if}</span>
						{else}
						<span class="marketplace-connection-status">{'Add New'|adminT}</span>
						{/if}
					</span>
				</button>
				{elseif !$platform.active}
				<div class="marketplace-connection marketplace-connection--planned marketplace-connection--{$platform.key|escape}">
					<img class="marketplace-platform-icon" src="{$domain}templates/admin/img/{$platform.key|escape}.png" alt="{$platform.label|escape}" onerror="this.style.display='none'">
					<span class="marketplace-connection-body">
						<span class="marketplace-platform-name">{$platform.label|escape}</span>
						<span class="marketplace-connection-status">{'Soon'|adminT}</span>
					</span>
				</div>
				{/if}
				{/foreach}
			</div>
		</div>
		<div class="modal fade" id="mp-modal-{$row.id_product}" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header"><div><h5 class="modal-title">{'Trendyol Connect'|adminT}</h5><div class="small text-muted">{$row.product_name|escape}</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button></div>
					<div class="modal-body">{$item.panel_html nofilter}</div>
				</div>
			</div>
		</div>
		<div class="modal fade" id="mp-modal-hb-{$row.id_product}" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header"><div><h5 class="modal-title">Hepsiburada Bağlantı</h5><div class="small text-muted">{$row.product_name|escape}</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button></div>
					<div class="modal-body">{$item.panel_html_hb nofilter}</div>
				</div>
			</div>
		</div>
		<div class="modal fade" id="mp-modal-n11-{$row.id_product}" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header"><div><h5 class="modal-title">N11 Bağlantı</h5><div class="small text-muted">{$row.product_name|escape}</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button></div>
					<div class="modal-body">{$item.panel_html_n11 nofilter}</div>
				</div>
			</div>
		</div>
		{/foreach}
	</div>
	{/if}
</div>

<!-- Hızlı Stok Güncelleme Modalı -->
<div class="modal fade" id="quickStockModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="quickStockForm">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">
                            {'Edit Stock Quantity'|adminT}
                        </h5>
                        <small class="text-muted quick-stock-product-name"></small>
                    </div>
                    <button class="btn-close"
                            data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden"
                           name="id_product"
                           id="qs_id_product">
                    <div class="row align-items-center mb-4">
                        <label class="col-4 col-form-label fw-semibold">
                            {'Stock Quantity'|adminT}
                            <span class="text-danger">*</span>
                        </label>
                        <div class="col-8">
                            <input
                                id="qs_stock"
                                name="stock"
                                type="number"
                                min="0"
                                class="form-control"
                                required>
                        </div>
                    </div>

                    <div class="form-check form-switch fs-6">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="qs_sync"
                            name="sync_marketplaces"
                            value="1"
                            checked>
                        <label class="form-check-label"
                               for="qs_sync">
                            {'Update marketplace stocks after saving'|adminT}
                        </label>
                    </div>
                    <div class="qs-msg mt-3 small"></div>
                </div>
                <div class="modal-footer">
                    <button
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                        type="button">
                        {'Cancel'|adminT}
                    </button>

                    <button
                        class="btn btn-primary id-qs-submit-btn"
                        type="submit">
                        <span class="spinner-border spinner-border-sm me-2 d-none qs-spinner"></span>
                        {'Save'|adminT}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{if $pagination.total_pages > 1}
<div class="mt-3">
	{include file='admin/plugin/pagination.tpl'}
</div>
{/if}

{include file='admin/marketplace/import_modal.tpl'}

<script>
window.trendyolBrandsApiUrl = {$brandsUrl|@json_encode nofilter};
window.trendyolCategoriesApiUrl = {$categoriesUrl|@json_encode nofilter};
window.trendyolAttributesApiUrl = {$attributesUrl|@json_encode nofilter};
window.trendyolUpdateStockApiUrl = {$updateStockUrl|@json_encode nofilter};
</script>
<script src="{$importJsUrl|escape}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
	if (window.TrendyolAdmin && typeof window.TrendyolAdmin.bindProductActions === 'function') {
		window.TrendyolAdmin.bindProductActions();
	}
});
</script>

{include file='admin/marketplace/_close.tpl'}
