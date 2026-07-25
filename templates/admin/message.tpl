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
<div class="admin-panel mb-3">
	<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
		<div>
			<h2 class="h5 mb-2">
				{if $thread.is_order_thread}
				{'Order #'|adminT}{$thread.order_reference|escape}
				{else}
				{$thread.subject|escape}
				{/if}
			</h2>
			<p class="mb-1"><strong>{'Customer:'|adminT}</strong> {$thread.full_name|escape}</p>
			<p class="mb-1"><strong>E-posta:</strong> <a href="mailto:{$thread.email|escape}">{$thread.email|escape}</a></p>
			{if $thread.phone}<p class="mb-1"><strong>{'Phone:'|adminT}</strong> {$thread.phone|escape}</p>{/if}
			{if $thread.is_order_thread}
			<p class="mb-0">
				<strong>{'Order:'|adminT}</strong>
				<a href="{$adminUrl}order?id={$thread.id_order}">#{$thread.order_reference|escape}</a>
				· {$thread.message_count}{' customer messages · '|adminT}{$thread.reply_count}{' replies'|adminT}
			</p>
			{/if}
		</div>
		{if $thread.is_order_thread}
		<a href="{$adminUrl}order?id={$thread.id_order}" class="btn btn-outline-dark btn-sm">{'Open order'|adminT}</a>
		{/if}
	</div>

	<div class="contact-admin-thread">
		{foreach $thread.timeline as $item}
		<div class="contact-admin-thread__item contact-admin-thread__item--{$item.type|escape} mb-3">
			<div class="d-flex justify-content-between gap-2 small text-muted mb-1">
				<span>
					{if $item.type == 'customer'}
					<strong>{'Customer'|adminT}</strong>{if $thread.is_order_thread && $thread.message_count > 1} · {'Message #'|adminT}{$item.id_message}{/if}
					{else}
					<strong>{$item.author|escape}</strong>
					{/if}
				</span>
				<span>{$item.date_formatted}</span>
			</div>
			<div class="contact-admin-thread__body" style="white-space:pre-wrap;">{$item.message|escape}</div>
			{if $item.attachment_url}
			<div class="mt-2">
				<a href="{$item.attachment_url|escape}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark">{'Open attachment'|adminT}</a>
			</div>
			{/if}
		</div>
		{/foreach}
	</div>
</div>

<form method="post" class="admin-panel">
	<h3 class="fs-6 mb-3">{'Write reply to customer'|adminT}</h3>
	<input type="hidden" name="replyMessage" value="1">
	<input type="hidden" name="token" value="{$adminToken}">
	<input type="hidden" name="reply_to_message_id" value="{$thread.reply_to_message_id}">
	<div class="mb-3">
		<label class="form-label">{'Reply'|adminT}</label>
		<textarea name="reply" class="form-control" rows="5" required minlength="5" placeholder="{'Read previous messages and write your reply...'|adminT}"></textarea>
	</div>
	<button type="submit" class="btn btn-dark">{'Send reply'|adminT}</button>
	<p class="small text-muted mt-2 mb-0">{'The reply is linked to the latest customer message and sent by email and notification.'|adminT}</p>
</form>

<p class="mt-3"><a href="{$adminUrl}messages">{'← Back to messages'|adminT}</a></p>
