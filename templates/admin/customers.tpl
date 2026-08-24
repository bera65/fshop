<div class="admin-toolbar d-flex flex-wrap gap-2 mb-3">
	<form method="get" action="{$adminUrl}customers" class="d-flex gap-2 flex-grow-1">
		<input type="search" name="q" class="form-control form-control-sm" placeholder="{'Search name, phone or email...'|adminT}" value="{$searchQuery|escape}">
		<button type="submit" class="btn btn-sm btn-dark">{'Search'|adminT}</button>
	</form>
	<button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createCustomerModal">{'Add customer'|adminT}</button>
</div>

{if $customerFlash}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast {$customerFlashType|default:'dark'} show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">
					{$customerFlash|escape}
				</div>
			</div>
			<button class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
		</div>
	</div>
{/if}

<div class="admin-panel p-0">
	<div class="table-responsive">
		<table class="table table-sm align-middle mb-0">
			<thead>
				<tr>
					<th>ID</th>
					<th>{'Full name'|adminT}</th>
					<th>{'Phone'|adminT}</th>
					<th>{'Email'|adminT}</th>
					<th>{'Order'|adminT}</th>
					<th>{'Total'|adminT}</th>
					<th>{'Registered'|adminT}</th>
					<th>{'Status'|adminT}</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{if $customers|@count}
				{foreach $customers as $row}
				<tr>
					<td>{$row.id_user}</td>
					<td>{$row.user_full_name|escape}</td>
					<td>{$row.phone|escape}</td>
					<td>{if $row.email}{$row.email|escape}{else}<span class="text-muted">—</span>{/if}</td>
					<td>{$row.order_count}</td>
					<td>{$row.order_total_formatted}</td>
					<td>{$row.date_formatted}</td>
					<td>{if $row.active}{'Active'|adminT}{else}<span class="text-danger">{'Inactive'|adminT}</span>{/if}</td>
					<td class="text-end"><a href="{$adminUrl}customer?id={$row.id_user}" class="btn btn-sm btn-outline-dark">{'Detail'|adminT}</a></td>
				</tr>
				{/foreach}
				{else}
				<tr><td colspan="9" class="text-muted">{'No customers found.'|adminT}</td></tr>
				{/if}
			</tbody>
		</table>
	</div>
</div>

{if $pagination.total_pages > 1}
<nav class="mt-3">
	<ul class="pagination pagination-sm">
		{foreach $pagination.pages as $p}
		<li class="page-item{if $p.current} active{/if}">
			<a class="page-link" href="{$p.url}">{$p.number}</a>
		</li>
		{/foreach}
	</ul>
</nav>
{/if}

<div class="modal fade" id="createCustomerModal" tabindex="-1" aria-labelledby="createCustomerModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<form method="post" action="{$adminUrl}customers">
				<input type="hidden" name="token" value="{$adminToken|escape}">
				<input type="hidden" name="createCustomer" value="1">
				<div class="modal-header">
					<h5 class="modal-title" id="createCustomerModalLabel">{'Add customer'|adminT}</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Close'|adminT}"></button>
				</div>
				<div class="modal-body">
					<div class="mb-3">
						<label class="form-label" for="new_customer_name">{'Full name'|adminT}</label>
						<input type="text" class="form-control" id="new_customer_name" name="user_full_name" required maxlength="128" autocomplete="off">
					</div>
					<div class="mb-3">
						<label class="form-label" for="new_customer_phone">{'Phone'|adminT}</label>
						<input type="text" class="form-control" id="new_customer_phone" name="phone" required placeholder="{'e.g. +90 532… or +1 202…'|adminT}" autocomplete="off">
					</div>
					<div class="mb-0">
						<label class="form-label" for="new_customer_email">{'Email'|adminT}</label>
						<input type="email" class="form-control" id="new_customer_email" name="email" maxlength="128" autocomplete="off">
						<div class="form-text">{'Optional. A random password is generated automatically.'|adminT}</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{'Cancel'|adminT}</button>
					<button type="submit" class="btn btn-primary btn-sm">{'Create customer'|adminT}</button>
				</div>
			</form>
		</div>
	</div>
</div>
