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
	<div class="col-lg-8">
		<div class="admin-panel mb-4">
			<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
				<h2 class="h6 mb-0">{'Cancel request #'|adminT}{$cancelItem.id_cancel}</h2>
				<span class="badge {$cancelItem.status_badge}">{$cancelItem.status_label|escape}</span>
			</div>
			<div class="row g-2 small">
				<div class="col-md-6"><strong>{'Order:'|adminT}</strong> <a href="{$adminUrl}order?id={$cancelItem.id_order}">#{$cancelItem.reference|escape}</a></div>
				<div class="col-md-6"><strong>{'Request date:'|adminT}</strong> {$cancelItem.date_formatted}</div>
				<div class="col-md-6"><strong>{'Customer:'|adminT}</strong> {$cancelItem.user_name|escape}</div>
				<div class="col-md-6"><strong>{'Phone:'|adminT}</strong> {$cancelItem.user_phone|escape}</div>
				<div class="col-md-6"><strong>{'Order status:'|adminT}</strong> {$cancelItem.order_status_label|escape}</div>
				<div class="col-md-6"><strong>{'Order total:'|adminT}</strong> {$cancelItem.total_formatted}</div>
			</div>
		</div>

		<div class="admin-panel mb-4">
			<h2 class="h6 mb-3">{'Customer message'|adminT}</h2>
			<p class="mb-0">{if $cancelItem.customer_message}{$cancelItem.customer_message|escape|nl2br}{else}<span class="text-muted">{'No message provided'|adminT}</span>{/if}</p>
		</div>

		{if $cancelItem.admin_message && $cancelItem.status != $statusPending}
		<div class="admin-panel{if $cancelItem.admin_receipt_url} mb-4{else}{/if}">
			<h2 class="h6 mb-3">{'Store reply'|adminT}</h2>
			<p class="mb-0">{$cancelItem.admin_message|escape|nl2br}</p>
		</div>
		{/if}

		{if $cancelItem.admin_receipt_url}
		<div class="admin-panel">
			<h2 class="h6 mb-3">{'Cancel receipt'|adminT}</h2>
			<a href="{$cancelItem.admin_receipt_url|escape}" target="_blank" rel="noopener">
				<img src="{$cancelItem.admin_receipt_url|escape}" alt="" class="rounded border" style="max-width:280px;max-height:280px;object-fit:contain;">
			</a>
		</div>
		{/if}
	</div>

	<div class="col-lg-4">
		{if $cancelItem.status == $statusPending}
		<div class="admin-panel mb-4">
			<h2 class="h6 mb-3">{'Process request'|adminT}</h2>
			<p class="small text-muted">{'When approved, the order is cancelled. You may upload a receipt optionally.'|adminT}</p>
			<form method="post" enctype="multipart/form-data">
				<input type="hidden" name="token" value="{$adminToken}">
				<div class="mb-3">
					<label class="form-label">{'Message to customer'|adminT}</label>
					<textarea name="admin_message" class="form-control" rows="4" required maxlength="5000"></textarea>
				</div>
				<div class="mb-3">
					<label class="form-label">{'Cancel receipt (optional)'|adminT}</label>
					<input type="file" name="admin_receipt" class="form-control" accept="image/jpeg,image/png,image/webp">
				</div>
				<div class="d-grid gap-2">
					<button type="submit" name="approveCancel" value="1" class="btn btn-primary">{'Approve and cancel'|adminT}</button>
					<button type="submit" name="rejectCancel" value="1" class="btn btn-outline-danger js-admin-confirm" data-confirm-title="{'Reject'|adminT}" data-confirm-message="{'Reject this request?'|adminT}">{'Reject'|adminT}</button>
				</div>
			</form>
		</div>
		{/if}

		<div class="admin-panel">
			<a href="{$adminUrl}cancellations" class="btn btn-outline-secondary btn-sm w-100">{'← Back to cancellations'|adminT}</a>
		</div>
	</div>
</div>
