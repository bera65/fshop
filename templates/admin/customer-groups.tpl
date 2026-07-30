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

<div class="row g-4">
	<div class="col-lg-8">
		<div class="admin-panel">
			<h2 class="h6 mb-3">{'Customer groups'|adminT}</h2>
			<p class="text-muted small">{'Group discount applies as a percentage off the catalog price for all products when the customer is logged in.'|adminT}</p>
			<div class="table-responsive">
				<table class="table table-sm align-middle mb-0">
					<thead>
						<tr>
							<th>{'Group'|adminT}</th>
							<th>{'Discount %'|adminT}</th>
							<th>{'Customers'|adminT}</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						{foreach $groups as $group}
						<tr>
							<td>
								<form method="post" class="d-flex flex-wrap gap-2 align-items-center">
									<input type="hidden" name="groupAction" value="1">
									<input type="hidden" name="action" value="update">
									<input type="hidden" name="id_group" value="{$group.id_group}">
									<input type="hidden" name="token" value="{$adminToken}">
									<input type="text" name="name" class="form-control form-control-sm" style="min-width:140px" value="{$group.name|escape}" maxlength="64" required>
									<div class="input-group input-group-sm" style="width:110px">
										<span class="input-group-text">%</span>
										<input type="text" name="discount_percent" class="form-control" value="{$group.discount_percent|escape}"{if $group.is_default} readonly{/if} required>
									</div>
									<div class="form-check form-switch mb-0">
										<input class="form-check-input" type="checkbox" name="active" value="1" id="groupActive{$group.id_group}" {if $group.active}checked{/if}{if $group.is_default} disabled{/if}>
										<label class="form-check-label small" for="groupActive{$group.id_group}">{'Active'|adminT}</label>
									</div>
									{if $group.is_default}
									<input type="hidden" name="active" value="1">
									{/if}
									<button type="submit" class="btn btn-sm btn-outline-dark">{'Save'|adminT}</button>
								</form>
							</td>
							<td>
								{$group.discount_percent|escape}%
								{if $group.is_default}<span class="badge bg-primary ms-1">{'Default'|adminT}</span>{/if}
							</td>
							<td>{$group.member_count}</td>
							<td class="text-end text-nowrap">
								{if !$group.is_default}
								<form method="post" class="d-inline">
									<input type="hidden" name="groupAction" value="1">
									<input type="hidden" name="action" value="default">
									<input type="hidden" name="id_group" value="{$group.id_group}">
									<input type="hidden" name="token" value="{$adminToken}">
									<button type="submit" class="btn btn-sm btn-outline-primary">{'Set as default'|adminT}</button>
								</form>
								<form method="post" class="d-inline" onsubmit="return confirm('{'Delete this group? Customers will move to the default group.'|adminT}');">
									<input type="hidden" name="groupAction" value="1">
									<input type="hidden" name="action" value="delete">
									<input type="hidden" name="id_group" value="{$group.id_group}">
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

	<div class="col-lg-4">
		<div class="admin-panel">
			<h2 class="h6 mb-3">{'Add group'|adminT}</h2>
			<form method="post">
				<input type="hidden" name="groupAction" value="1">
				<input type="hidden" name="action" value="add">
				<input type="hidden" name="token" value="{$adminToken}">
				<div class="mb-3">
					<label class="form-label">{'Name'|adminT}</label>
					<input type="text" name="name" class="form-control" placeholder="Bayi" maxlength="64" required>
				</div>
				<div class="mb-3">
					<label class="form-label">{'Discount %'|adminT}</label>
					<input type="text" name="discount_percent" class="form-control" placeholder="15" required>
				</div>
				<div class="form-check form-switch mb-3">
					<input class="form-check-input" type="checkbox" name="active" id="groupAddActive" value="1" checked>
					<label class="form-check-label" for="groupAddActive">{'Active'|adminT}</label>
				</div>
				<button type="submit" class="btn btn-dark">{'Add group'|adminT}</button>
			</form>
		</div>
	</div>
</div>

<p class="mt-3"><a href="{$adminUrl}customers">{'← Back to customers'|adminT}</a></p>
