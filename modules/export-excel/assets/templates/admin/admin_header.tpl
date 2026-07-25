{if $mode == 'products'}
<div class="export-excel-bar d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
	<div class="d-flex align-items-center gap-2">
		<strong class="small mb-0">Excel</strong>
		<span class="text-muted small d-none d-md-inline">{'Import / export products (current filters apply)'|adminT}</span>
	</div>
	<div class="d-flex flex-wrap gap-2">
		<form method="post" action="{$exportUrl|escape}" class="d-inline">
			<input type="hidden" name="token" value="{$adminToken|escape}">
			<input type="hidden" name="q" value="{$filterQ|escape}">
			<input type="hidden" name="category" value="{$filterCategory|escape}">
			<input type="hidden" name="brand" value="{$filterBrand|escape}">
			{if $filterActive >= 0}
			<input type="hidden" name="active" value="{$filterActive|escape}">
			{/if}
			<button type="submit" class="btn btn-sm btn-dark">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/></svg>
				{'Export'|adminT}
			</button>
		</form>
		<button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#exportExcelImportModal">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m17 8-5-5-5 5"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
			{'Import'|adminT}
		</button>
	</div>
</div>

<div class="modal fade" id="exportExcelImportModal" tabindex="-1" aria-labelledby="exportExcelImportModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exportExcelImportModalLabel">{'Import products from Excel'|adminT}</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Close'|adminT}"></button>
			</div>
			<form method="post" action="{$importAction|escape}" enctype="multipart/form-data">
				<div class="modal-body">
					<input type="hidden" name="exportExcelImportProducts" value="1">
					<input type="hidden" name="token" value="{$adminToken|escape}">
					<input type="hidden" name="return_url" value="{$returnUrl|escape}">
					<div class="alert alert-info mb-3">
						<strong>{'Information'|adminT}</strong>
						<ul class="mb-0 mt-2 small">
							<li>{'Only <strong>.xlsx</strong> files are allowed.'|adminT nofilter}</li>
							<li>{'Download the current product list with <strong>Export</strong> first to get a sample template.'|adminT nofilter}</li>
							<li>{'If <strong>SKU</strong> matches an existing product, the record is updated; otherwise a new product is created.'|adminT nofilter}</li>
							<li>{'If <strong>category</strong> or <strong>brand</strong> in Excel does not exist, it is created automatically.'|adminT nofilter}</li>
							<li>{'In the <strong>Images</strong> column, enter image URLs separated by <strong>;</strong>. On import, existing product images are replaced with the URLs in Excel.'|adminT nofilter}</li>
						</ul>
					</div>
					<div class="mb-3">
						<p class="fw-semibold mb-2">{'Expected columns'|adminT}</p>
						<div class="small text-muted">
							Product Name, Barcode, Stock Code, {'Desi'|adminT}, Price, Old Price, Vat, Stock,
							short Description, Description, Meta Title, Meta Description, Slug,
							Category Name, Brand Name, Images, Active
						</div>
					</div>
					<div class="mb-0">
						<label for="exportExcelFile" class="form-label">{'Excel file'|adminT}</label>
						<input type="file" id="exportExcelFile" name="excelFile" class="form-control" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{'Cancel'|adminT}</button>
					<button type="submit" class="btn btn-success">{'Upload and import'|adminT}</button>
				</div>
			</form>
		</div>
	</div>
</div>

{else}
<div class="export-excel-bar d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
	<div class="d-flex align-items-center gap-2">
		<strong class="small mb-0">Excel</strong>
		<span class="text-muted small d-none d-md-inline">{'Export filtered orders'|adminT}</span>
	</div>
	<form method="post" action="{$exportUrl|escape}" class="d-inline">
		<input type="hidden" name="token" value="{$adminToken|escape}">
		<input type="hidden" name="status" value="{$filterStatus|escape}">
		<input type="hidden" name="reference" value="{$filterReference|escape}">
		<input type="hidden" name="customer" value="{$filterCustomer|escape}">
		<input type="hidden" name="date_from" value="{$filterDateFrom|escape}">
		<input type="hidden" name="date_to" value="{$filterDateTo|escape}">
		<button type="submit" class="btn btn-sm btn-dark">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/></svg>
			{'Export'|adminT}
		</button>
	</form>
</div>
{/if}

<link rel="stylesheet" href="{$domain}modules/export-excel/assets/css/admin.css?v={$smarty.now}">
