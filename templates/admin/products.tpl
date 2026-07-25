{if $sonuc}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast dark show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">
					{$sonuc|escape}
				</div>
			</div>
			<button class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
		</div>
	</div>
{/if}
<form method="get" class="admin-toolbar row g-2 mb-3">
	<div class="col-md-3">
		<input type="text" name="q" class="form-control form-control-sm" placeholder="{'Search...'|adminT}" value="{$searchQuery|escape}">
	</div>
	<div class="col-md-2">
		<select name="category" class="form-select form-select-sm">
			<option value="0">{'All categories'|adminT}</option>
			{foreach $categoryOptions as $cat}
			<option value="{$cat.id_category}"{if $categoryFilter == $cat.id_category} selected{/if}>{$cat.category_name|escape}</option>
			{/foreach}
		</select>
	</div>
	<div class="col-md-2">
		<select name="brand" class="form-select form-select-sm">
			<option value="0">{'All brands'|adminT}</option>
			{foreach $brandOptions as $b}
			<option value="{$b.id_brand}"{if $brandFilter == $b.id_brand} selected{/if}>{$b.brand_name|escape}</option>
			{/foreach}
		</select>
	</div>
	<div class="col-md-2">
		<select name="active" class="form-select form-select-sm">
			<option value=""{if $activeFilter == -1} selected{/if}>{'All statuses'|adminT}</option>
			<option value="1"{if $activeFilter == 1} selected{/if}>{'Active'|adminT}</option>
			<option value="0"{if $activeFilter == 0} selected{/if}>{'Inactive'|adminT}</option>
		</select>
	</div>
	<div class="col-md-3 d-flex gap-2">
		<button type="submit" class="btn btn-sm btn-dark">{'Filter'|adminT}</button>
		<a href="{$adminUrl}products" class="btn btn-sm btn-outline-secondary">{'Clear'|adminT}</a>
	</div>
</form>
<div class="admin-panel mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
	<div>
		<a href="{$adminUrl}product" class="btn btn-sm btn-primary">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-plus-icon lucide-circle-plus"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/></svg>
			{'New Product'|adminT}
		</a>
	</div>
	<form method="post" id="productsBulkForm" class="d-flex align-items-center flex-wrap gap-2" hidden>
		<input type="hidden" name="bulkProductToken" value="{$adminToken}">
		<span class="small text-muted" id="productsBulkCount">0 {'selected'|adminT}</span>
		<button type="submit" name="bulkProductAction" value="activate" class="btn btn-sm btn-outline-success js-bulk-product-submit">{'Active'|adminT}</button>
		<button type="submit" name="bulkProductAction" value="deactivate" class="btn btn-sm btn-outline-secondary js-bulk-product-submit">{'Deactive'|adminT}</button>
		<button type="submit" name="bulkProductAction" value="delete" class="btn btn-sm btn-outline-danger js-bulk-product-submit js-admin-confirm" data-confirm-title="Toplu sil" data-confirm-message="{'Selected items will be permanently deleted. Do you want to continue?'|adminT}">{'Delete'|adminT}</button>
	</form>
</div>
<div class="admin-panel p-0">
	<div class="table-responsive">
		<table class="table table-sm align-middle mb-0">
			<thead>
				<tr>
					<th style="width:36px">
						<input type="checkbox" class="form-check-input" id="productsSelectAll" aria-label="Tümünü seç">
					</th>
					<th></th>
					<th class="text-muted" style="width:56px">{'ID'|adminT}</th>
					<th>{'Product'|adminT}</th>
					<th>{'Category'|adminT}</th>
					<th>{'Brand'|adminT}</th>
					<th>{'Price'|adminT}</th>
					<th>{'Stock'|adminT}</th>
					<th>{'Status'|adminT}</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{if $products|@count}
				{foreach $products as $row}
				<tr>
					<td>
						<input type="checkbox" form="productsBulkForm" name="productIds[]" value="{$row.id_product}" class="form-check-input js-product-select" aria-label="Ürün #{$row.id_product} seç">
					</td>
					<td style="width:48px"><img src="{$row.image_url}" alt="" width="40" height="40" style="object-fit:contain"></td>
					<td class="text-muted small">{$row.id_product}</td>
					<td>{$row.product_name|escape|truncate:40}{if $row.product_type|default:'physical' == 'virtual'} <span class="badge bg-info">{'Virtual'|adminT}</span>{/if}</td>
					<td>{$row.category_name|escape}</td>
					<td>{$row.brand_name|escape}</td>
					<td>{$row.price_formatted}</td>
					<td>{$row.stock}</td>
					<td>{$row.active_label|escape}</td>
					<td class="text-end">
					<form action="" method="POST">
						<a href="{$adminUrl}product?id={$row.id_product}" class="btn btn-sm btn-outline-dark">
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil-icon lucide-pencil"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
						</a>
						<input type="hidden" name="idProduct" value="{$row.id_product}" />
						<button type="submit" name="deleteProduct" value="{$adminToken}" class="btn btn-danger btn-sm js-admin-confirm" data-confirm-title="{'Delete product'|adminT}" data-confirm-message="{'Are you sure you want to delete this product? The product and related images will be permanently removed.'|adminT}">
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-icon lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
						</button>
					</form>
					</td>
				</tr>
				{/foreach}
				{else}
				<tr><td colspan="10" class="text-muted">{'No records found.'|adminT}</td></tr>
				{/if}
			</tbody>
		</table>
	</div>
</div>

{include file='admin/plugin/pagination.tpl'}

<script>
(function () {
	'use strict';

	var selectAll = document.getElementById('productsSelectAll');
	var bulkForm = document.getElementById('productsBulkForm');
	var countEl = document.getElementById('productsBulkCount');

	function getBoxes() {
		return document.querySelectorAll('.js-product-select');
	}

	function updateBulkBar() {
		var checked = document.querySelectorAll('.js-product-select:checked');
		var total = getBoxes().length;
		var selected = checked.length;

		if (bulkForm) {
			bulkForm.hidden = selected === 0;
		}

		if (countEl) {
			countEl.textContent = selected + ' ürün seçildi';
		}

		if (!selectAll) {
			return;
		}

		selectAll.checked = total > 0 && selected === total;
		selectAll.indeterminate = selected > 0 && selected < total;
	}

	if (selectAll) {
		selectAll.addEventListener('change', function () {
			getBoxes().forEach(function (box) {
				box.checked = selectAll.checked;
			});
			updateBulkBar();
		});
	}

	document.addEventListener('change', function (event) {
		if (event.target && event.target.classList.contains('js-product-select')) {
			updateBulkBar();
		}
	});

	document.querySelectorAll('.js-bulk-product-submit').forEach(function (btn) {
		btn.addEventListener('click', function (event) {
			if (document.querySelectorAll('.js-product-select:checked').length === 0) {
				event.preventDefault();
				event.stopPropagation();
				window.alert('Lütfen en az bir ürün seçin.');
			}
		});
	});
})();
</script>
