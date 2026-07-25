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
				<h2 class="h6 mb-0">{'Return request #'|adminT}{$returnItem.id_return}</h2>
				<span class="badge {$returnItem.status_badge}">{$returnItem.status_label|escape}</span>
			</div>
			<div class="row g-2 small">
				<div class="col-md-6"><strong>{'Order:'|adminT}</strong> <a href="{$adminUrl}order?id={$returnItem.id_order}">#{$returnItem.reference|escape}</a></div>
				<div class="col-md-6"><strong>{'Request date:'|adminT}</strong> {$returnItem.date_formatted}</div>
				<div class="col-md-6"><strong>{'Customer:'|adminT}</strong> {$returnItem.user_name|escape}</div>
				<div class="col-md-6"><strong>{'Phone:'|adminT}</strong> {$returnItem.user_phone|escape}</div>
				{if $returnItem.user_email}<div class="col-md-6"><strong>E-posta:</strong> {$returnItem.user_email|escape}</div>{/if}
				<div class="col-md-6"><strong>{'Order status:'|adminT}</strong> {$returnItem.order_status_label|escape}</div>
				<div class="col-md-6"><strong>{'Order total:'|adminT}</strong> {$returnItem.total_formatted}</div>
			</div>
		</div>

		<div class="admin-panel mb-4">
			<h2 class="h6 mb-3">{'Customer message'|adminT}</h2>
			<p class="mb-0">{$returnItem.customer_message|escape|nl2br}</p>
		</div>

		{if $returnItem.images|@count}
		<div class="admin-panel mb-4">
			<h2 class="h6 mb-3">{'Images'|adminT}</h2>
			<div class="d-flex flex-wrap gap-2">
				{foreach $returnItem.images as $img}
				<a href="{$img.url|escape}" target="_blank" rel="noopener">
					<img src="{$img.url|escape}" alt="" class="rounded border" style="width:120px;height:120px;object-fit:cover;">
				</a>
				{/foreach}
			</div>
		</div>
		{/if}

		{if $returnItem.admin_message && $returnItem.status != $statusPending}
		<div class="admin-panel{if $returnItem.admin_receipt_url} mb-4{else}{/if}">
			<h2 class="h6 mb-3">{'Store reply'|adminT}</h2>
			<p class="mb-0">{$returnItem.admin_message|escape|nl2br}</p>
			{if $returnItem.resolved_formatted}
			<p class="small text-muted mt-2 mb-0">{$returnItem.resolved_formatted}</p>
			{/if}
		</div>
		{/if}

		{if $returnItem.admin_receipt_url}
		<div class="admin-panel">
			<h2 class="h6 mb-3">{'Return receipt'|adminT}</h2>
			<a href="{$returnItem.admin_receipt_url|escape}" target="_blank" rel="noopener">
				<img src="{$returnItem.admin_receipt_url|escape}" alt="{'Return receipt'|adminT}" class="rounded border" style="max-width:280px;max-height:280px;object-fit:contain;">
			</a>
		</div>
		{/if}
	</div>

	<div class="col-lg-4">
		{if $returnItem.status == $statusPending}
		<div class="admin-panel mb-4">
			<h2 class="h6 mb-3">{'Process request'|adminT}</h2>
			<p class="small text-muted">{'When approved, your message is sent to the customer and the order status becomes <strong>Returned</strong>.'|adminT}</p>
			<form method="post" class="mb-3">
				<input type="hidden" name="token" value="{$adminToken}">
				<div class="mb-3">
					<label class="form-label">{'Message to customer'|adminT}</label>
					<textarea name="admin_message" class="form-control" rows="4" required maxlength="5000" placeholder="{'Write return approval and instructions'|adminT}"></textarea>
				</div>
				<div class="d-grid gap-2">
					<button type="submit" name="approveReturn" value="1" class="btn btn-primary">{'Approve and start process'|adminT}</button>
					<button type="submit" name="rejectReturn" value="1" class="btn btn-outline-danger" onclick="return confirm('{'Reject this return request?'|adminT}');">{'Reject'|adminT}</button>
				</div>
			</form>
		</div>
		{elseif $returnItem.status == $statusApproved}
		<div class="admin-panel mb-4">
			<h2 class="h6 mb-3">{'Complete return'|adminT}</h2>
			<p class="small text-muted">{'When the return is done, you can upload a receipt and add a message for the customer.'|adminT}</p>
			<form method="post" enctype="multipart/form-data">
				<input type="hidden" name="token" value="{$adminToken}">
				<div class="mb-3">
					<label class="form-label">{'Additional message (optional)'|adminT}</label>
					<textarea name="admin_message" class="form-control" rows="3" maxlength="5000" placeholder="{'Return completed notice'|adminT}"></textarea>
				</div>
				<div class="mb-3">
					<label class="form-label">{'Return receipt (optional)'|adminT}</label>
					<input type="file" name="admin_receipt" class="form-control" accept="image/jpeg,image/png,image/webp">
					<div class="form-text">{'The customer can see this image on the return details. JPG, PNG or WEBP — max 5 MB.'|adminT}</div>
				</div>
				<button type="submit" name="completeReturn" value="1" class="btn btn-success w-100">{'Mark return as completed'|adminT}</button>
			</form>
		</div>
		{/if}

		<div class="admin-panel">
			<a href="{$adminUrl}returns" class="btn btn-outline-secondary btn-sm w-100">{'← Back to returns'|adminT}</a>
		</div>
	</div>
</div>
