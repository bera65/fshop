{if $flash}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast {$flashType|default:'success'} show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">{$flash|escape}</div>
			</div>
			<button class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
		</div>
	</div>
{/if}

<div class="row g-4">
	<div class="col-lg-7">
		<div class="admin-panel">
			<h2 class="h6 mb-3">{'Defined tax rates'|adminT}</h2>
			<p class="text-muted small">{'Product prices are entered including tax. Select the tax rate on each product. The default rate is used for new products.'|adminT}</p>
			<div class="table-responsive">
				<table class="table table-sm align-middle mb-0">
					<thead>
						<tr>
							<th>{'Tax'|adminT}</th>
							<th>{'Products'|adminT}</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						{foreach $taxes as $tax}
						<tr>
							<td>
								<form method="post" class="d-flex flex-wrap gap-2 align-items-center">
									<input type="hidden" name="taxAction" value="1">
									<input type="hidden" name="action" value="update">
									<input type="hidden" name="id_tax" value="{$tax.id_tax}">
									<input type="hidden" name="token" value="{$adminToken}">
									<input type="text" name="name" class="form-control form-control-sm" style="min-width:140px" value="{$tax.name|escape}" maxlength="64" required>
									<div class="input-group input-group-sm" style="width:110px">
										<span class="input-group-text">%</span>
										<input type="text" name="rate" class="form-control" value="{$tax.rate|escape}" required>
									</div>
									<div class="form-check form-switch mb-0">
										<input class="form-check-input" type="checkbox" name="active" value="1" id="taxActive{$tax.id_tax}" {if $tax.active}checked{/if}{if $tax.is_default} disabled{/if}>
										<label class="form-check-label small" for="taxActive{$tax.id_tax}">{'Active'|adminT}</label>
									</div>
									<button type="submit" class="btn btn-sm btn-outline-dark">{'Save'|adminT}</button>
								</form>
							</td>
							<td>
								{$tax.product_count}
								{if $tax.is_default}<span class="badge bg-primary ms-1">{'Default'|adminT}</span>{/if}
							</td>
							<td class="text-end text-nowrap">
								{if !$tax.is_default}
								<form method="post" class="d-inline">
									<input type="hidden" name="taxAction" value="1">
									<input type="hidden" name="action" value="default">
									<input type="hidden" name="id_tax" value="{$tax.id_tax}">
									<input type="hidden" name="token" value="{$adminToken}">
									<button type="submit" class="btn btn-sm btn-outline-primary">{'Set as default'|adminT}</button>
								</form>
								<form method="post" class="d-inline" onsubmit="return confirm('{'Delete this tax rate? Products using it will move to the default rate.'|adminT}');">
									<input type="hidden" name="taxAction" value="1">
									<input type="hidden" name="action" value="delete">
									<input type="hidden" name="id_tax" value="{$tax.id_tax}">
									<input type="hidden" name="token" value="{$adminToken}">
									<button type="submit" class="btn btn-sm btn-outline-danger">{'Delete'|adminT}</button>
								</form>
								{/if}
							</td>
						</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<div class="col-lg-5">
		<div class="admin-panel">
			<h2 class="h6 mb-3">{'Add tax rate'|adminT}</h2>
			<form method="post">
				<input type="hidden" name="taxAction" value="1">
				<input type="hidden" name="action" value="add">
				<input type="hidden" name="token" value="{$adminToken}">
				<div class="mb-3">
					<label class="form-label">{'Name'|adminT}</label>
					<input type="text" name="name" class="form-control" placeholder="KDV %8" maxlength="64" required>
				</div>
				<div class="mb-3">
					<label class="form-label">{'Rate (%)'|adminT}</label>
					<input type="text" name="rate" class="form-control" placeholder="8" required>
				</div>
				<div class="form-check form-switch mb-3">
					<input class="form-check-input" type="checkbox" name="active" id="taxAddActive" value="1" checked>
					<label class="form-check-label" for="taxAddActive">{'Active'|adminT}</label>
				</div>
				<button type="submit" class="btn btn-dark">{'Add tax rate'|adminT}</button>
			</form>
			<p class="text-muted small mt-3 mb-0">
				{'Current default rate:'|adminT} <strong>%{$defaultTaxRate|escape}</strong>.
				{'Changing a rate updates all products that use the old rate.'|adminT}
			</p>
		</div>
	</div>
</div>

<p class="mt-3"><a href="{$adminUrl}settings">{'← Back to settings'|adminT}</a></p>
