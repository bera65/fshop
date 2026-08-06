{if $sonuc}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast dark show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">{$sonuc|escape}</div>
			</div>
			<button class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
		</div>
	</div>
{/if}
<div class="admin-toolbar d-flex flex-wrap gap-2 mb-3">
	<a href="{$adminUrl}suppliers" class="btn btn-sm {if $activeFilter == -1}btn-dark{else}btn-outline-dark{/if}">{'All'|adminT}</a>
	<a href="{$adminUrl}suppliers?active=1" class="btn btn-sm {if $activeFilter == 1}btn-dark{else}btn-outline-dark{/if}">{'Active'|adminT}</a>
	<a href="{$adminUrl}suppliers?active=0" class="btn btn-sm {if $activeFilter == 0}btn-dark{else}btn-outline-dark{/if}">{'Inactive'|adminT}</a>
	<a href="{$adminUrl}supplier" class="btn btn-sm btn-primary ms-auto">{'+ New supplier'|adminT}</a>
</div>

<div class="admin-panel p-0">
	<div class="table-responsive">
		<table class="table table-sm align-middle mb-0">
			<thead>
				<tr>
					<th>ID</th>
					<th>{'Supplier'|adminT}</th>
					<th>{'Status'|adminT}</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{if $suppliers|@count}
				{foreach $suppliers as $row}
				<tr>
					<td>{$row.id_supplier}</td>
					<td>{$row.supplier_name|escape}</td>
					<td>{if $row.active}{'Active'|adminT}{else}<span class="text-danger">{'Inactive'|adminT}</span>{/if}</td>
					<td class="text-end">
						<form action="" method="POST">
							<input type="hidden" name="token" value="{$adminToken}">
							<a href="{$adminUrl}supplier?id={$row.id_supplier}" class="btn btn-sm btn-outline-dark">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
							</a>
							<input type="hidden" name="idSupplier" value="{$row.id_supplier}">
							<button type="submit" name="deleteSupplier" value="{$adminToken}" class="btn btn-danger btn-sm js-admin-confirm" data-confirm-title="{'Delete supplier'|adminT}" data-confirm-message="{'Are you sure you want to delete this supplier?'|adminT}">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
							</button>
						</form>
					</td>
				</tr>
				{/foreach}
				{else}
				<tr><td colspan="4" class="text-muted">{'No records found.'|adminT}</td></tr>
				{/if}
			</tbody>
		</table>
	</div>
</div>
