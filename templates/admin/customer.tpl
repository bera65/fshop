{if $flash}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast {$flashType|default:'success'} show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">
					{$flash|escape}
				</div>
			</div>
			<button class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
		</div>
	</div>
{/if}
<div class="row g-4">
	<div class="col-lg-4">
		<form method="post" class="admin-panel p-3 mb-4">
			<input type="hidden" name="saveCustomer" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<h2 class="h6 mb-3">{'Customer information'|adminT}</h2>

			<div class="mb-3">
				<label class="form-label">{'Full name'|adminT}</label>
				<input type="text" name="user_full_name" class="form-control" required
					value="{$customer.user_full_name|escape}">
			</div>
			<div class="mb-3">
				<label class="form-label">{'Phone'|adminT}</label>
				<input type="text" name="phone" class="form-control" required
					value="{$customer.phone|escape}" placeholder="05xxxxxxxxx">
			</div>
			<div class="mb-3">
				<label class="form-label">{'Email'|adminT}</label>
				<input type="email" name="email" class="form-control"
					value="{$customer.email|escape}" placeholder="ornek@mail.com">
			</div>

			<p class="mb-1 text-muted small">{'Registration date'|adminT}</p>
			<p class="mb-3">{$customer.date_formatted}</p>
			<p class="mb-3">
				{if $customer.active}
				<span class="badge bg-success">{'Active'|adminT}</span>
				{else}
				<span class="badge bg-danger">{'Inactive'|adminT}</span>
				{/if}
			</p>

			<button type="submit" class="btn btn-dark btn-sm">{'Save information'|adminT}</button>
			<button type="button" class="btn btn-outline-primary btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#customerContactModal">
				{'Contact customer'|adminT}
			</button>
		</form>

		<form method="post" class="admin-panel p-3 mb-4">
			<input type="hidden" name="saveCustomerPassword" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<h2 class="h6 mb-3">{'Change password'|adminT}</h2>
			<p class="text-muted small mb-3">{'The new password is saved directly to the customer account. At least 8 characters (e.g. <code>12345678</code>). The customer signs in with their registered phone number.'|adminT}</p>
			<div class="mb-3">
				<label class="form-label">{'New password'|adminT}</label>
				<input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
			</div>
			<div class="mb-3">
				<label class="form-label">{'New password (confirm)'|adminT}</label>
				<input type="password" name="password2" class="form-control" required minlength="8" autocomplete="new-password">
			</div>
			<button type="submit" class="btn btn-outline-dark btn-sm">{'Update password'|adminT}</button>
		</form>

		<form method="post" class="admin-panel p-3">
			<input type="hidden" name="toggleActive" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<h2 class="h6 mb-3">{'Account status'|adminT}</h2>
			<button type="submit" class="btn btn-sm {if $customer.active}btn-outline-danger{else}btn-outline-success{/if}">
				{if $customer.active}{'Deactivate account'|adminT}{else}{'Activate account'|adminT}{/if}
			</button>
		</form>
	</div>

	<div class="col-lg-8">
		<div class="admin-panel p-3">
			<h2 class="h6 mb-3">{'Order history'|adminT}</h2>
			{if $customer.orders|@count}
			<div class="table-responsive">
				<table class="table table-sm align-middle mb-0">
					<thead>
						<tr>
							<th>{'Reference'|adminT}</th>
							<th>{'Status'|adminT}</th>
							<th>{'Total'|adminT}</th>
							<th>{'Date'|adminT}</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						{foreach $customer.orders as $order}
						<tr>
							<td>{$order.reference|escape}</td>
							<td>{$order.status_label|escape}</td>
							<td>{$order.total_formatted}</td>
							<td>{$order.date_formatted}</td>
							<td class="text-end"><a href="{$adminUrl}order?id={$order.id_order}" class="btn btn-sm btn-outline-dark">{'View'|adminT}</a></td>
						</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
			{else}
			<p class="text-muted mb-0">{'No orders yet.'|adminT}</p>
			{/if}
		</div>
	</div>
</div>

<div class="modal fade" id="customerContactModal" tabindex="-1" aria-labelledby="customerContactModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<form method="post">
				<input type="hidden" name="sendCustomerContact" value="1">
				<input type="hidden" name="token" value="{$adminToken}">
				<div class="modal-header">
					<h5 class="modal-title" id="customerContactModalLabel">{'Contact customer'|adminT}</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Close'|adminT}"></button>
				</div>
				<div class="modal-body">
					<p class="small text-muted mb-3">
						<strong>{$customer.user_full_name|escape}</strong>
						{if $customerHasPhone}<br>{'Phone'|adminT}: {$customer.phone|escape}{/if}
						{if $customerHasEmail}<br>{'Email'|adminT}: {$customer.email|escape}{/if}
					</p>

					<div class="mb-3">
						<label class="form-label" for="customerContactMessage">{'Message'|adminT}</label>
						<textarea id="customerContactMessage" name="contact_message" class="form-control" rows="5" required placeholder="{'Write your message to the customer...'|adminT}"></textarea>
					</div>

					<div class="mb-0">
						<label class="form-label d-block">{'Send via'|adminT}</label>
						<div class="form-check">
							<input class="form-check-input" type="radio" name="contact_channel" id="contactChannelWhatsapp" value="whatsapp"{if $customerHasPhone} checked{else} disabled{/if}>
							<label class="form-check-label" for="contactChannelWhatsapp">
								WhatsApp
								{if $customerContactWapioReady}
								<span class="text-muted small">({'via Wapio API'|adminT})</span>
								{else}
								<span class="text-muted small">({'opens wa.me link'|adminT})</span>
								{/if}
							</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="radio" name="contact_channel" id="contactChannelEmail" value="email"{if !$customerHasPhone && $customerHasEmail} checked{elseif !$customerHasPhone} disabled{/if}>
							<label class="form-check-label" for="contactChannelEmail">{'Email'|adminT}</label>
						</div>
						{if !$customerHasPhone && !$customerHasEmail}
						<p class="small text-danger mb-0 mt-2">{'Customer has no phone or email on file.'|adminT}</p>
						{/if}
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{'Cancel'|adminT}</button>
					<button type="submit" class="btn btn-dark btn-sm"{if !$customerHasPhone && !$customerHasEmail} disabled{/if}>{'Send message'|adminT}</button>
				</div>
			</form>
		</div>
	</div>
</div>

{if $contactRedirectUrl}
<script>
(function () {
	var url = {$contactRedirectUrl|@json_encode nofilter};
	if (url) {
		window.open(url, '_blank', 'noopener');
	}
})();
</script>
{/if}
