{if $flash}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast {$flashType|default:'success'} show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">{$flash|escape}</div>
			</div>
			<button class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
		</div>
	</div>
{/if}

<div class="admin-panel">
	<form method="post">
		<input type="hidden" name="saveSupplier" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="row g-3 mb-3">
			<div class="col-md-8">
				<label class="form-label">{'Supplier name'|adminT}</label>
				<input type="text" name="supplier_name" class="form-control" value="{$supplier.supplier_name|escape}" required maxlength="128">
			</div>
			<div class="col-md-4 d-flex align-items-end">
				<div class="form-check mb-2">
					<input class="form-check-input" type="checkbox" name="active" value="1" id="supplierActive"{if $supplier.active} checked{/if}>
					<label class="form-check-label" for="supplierActive">{'Active'|adminT}</label>
				</div>
			</div>
		</div>

		<div class="d-flex gap-2">
			<button type="submit" class="btn btn-dark">{'Save'|adminT}</button>
			<a href="{$adminUrl}suppliers" class="btn btn-outline-secondary">{'Cancel'|adminT}</a>
		</div>
	</form>
</div>
