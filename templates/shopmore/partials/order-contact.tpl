<div class="dress-account-card mt-4" id="order-contact">
	<h3 class="fs-6 mb-3">{'Ask about this order'|translate}</h3>

	{if $orderContactSuccess}
	<div class="alert alert-success">{$orderContactSuccess|escape}</div>
	{/if}

	{if $orderContactError}
	<div class="alert alert-danger">{$orderContactError|escape}</div>
	{/if}

	{if $orderContactThread|@count}
	<div class="order-contact-thread mb-4">
		{foreach $orderContactThread as $threadMsg}
		<div class="order-contact-item order-contact-item--customer mb-3">
			<div class="order-contact-item__meta small text-muted mb-1">
				<strong>{'You'|translate}</strong> · {$threadMsg.date_formatted}
			</div>
			<div class="order-contact-item__body">{$threadMsg.message|escape|nl2br}</div>
			{if $threadMsg.attachment_url}
			<div class="mt-2">
				<a href="{$threadMsg.attachment_url|escape}" target="_blank" rel="noopener" class="small">{'View attachment'|translate}</a>
			</div>
			{/if}
		</div>
		{foreach $threadMsg.replies as $reply}
		<div class="order-contact-item order-contact-item--store mb-3">
			<div class="order-contact-item__meta small text-muted mb-1">
				<strong>{'Store response'|translate}</strong> · {$reply.date_formatted}
			</div>
			<div class="order-contact-item__body">{$reply.message|escape|nl2br}</div>
		</div>
		{/foreach}
		{/foreach}
	</div>
	{/if}

	<form method="post" action="{$domain}my-account?order={$selectedOrder.id_order}" enctype="multipart/form-data">
		<input type="hidden" name="sendOrderContact" value="1">
		<input type="hidden" name="token" value="{$token}">
		<input type="hidden" name="id_order" value="{$selectedOrder.id_order}">
		<input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;" aria-hidden="true">

		<div class="mb-3">
			<label class="form-label">{'Your Message'|translate}</label>
			<textarea name="message" class="form-control" rows="4" required minlength="10" placeholder="{'Order contact placeholder'|translate}"></textarea>
		</div>
		<div class="mb-3">
			<label class="form-label">{'Attachment optional'|translate}</label>
			<input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf,image/*,application/pdf">
			<div class="form-text">{'Attachment hint'|translate}</div>
		</div>
		<button type="submit" class="prime-btn prime-btn--primary prime-btn--sm">{'Send Message'|translate}</button>
	</form>
</div>
