<div class="prime-container prime-page">
	<h1 class="prime-page__title">{'Cancel request'|translate}</h1>

	{if $flash}
	<div class="alert alert-{$flashType|default:'danger'}">{$flash|escape}</div>
	{/if}

	{if !$eligible}
	<div class="prime-empty">
		<p>{'This order is not eligible for cancellation'|translate}</p>
		<a href="{$domain}my-account" class="prime-btn prime-btn--outline prime-btn--sm">{'My Account'|translate}</a>
	</div>
	{else}
	<p class="text-muted mb-4">{'Cancel request intro'|translate}</p>
	<form method="post" class="prime-page-card">
		<input type="hidden" name="submitCancel" value="1">
		<input type="hidden" name="token" value="{$token}">
		<input type="hidden" name="id_order" value="{$selectedOrderId}">
		<div class="mb-4">
			<label class="form-label" for="message">{'Cancellation reason'|translate}</label>
			<textarea name="message" id="message" class="form-control" rows="4" maxlength="5000" placeholder="{'Cancel message placeholder'|translate}"></textarea>
			<div class="form-text">{'Optional'|translate}</div>
		</div>
		<div class="d-flex flex-wrap gap-2">
			<button type="submit" class="prime-btn prime-btn--primary prime-btn--sm">{'Submit cancel request'|translate}</button>
			<a href="{$domain}my-account?order={$selectedOrderId}" class="prime-btn prime-btn--outline prime-btn--sm">{'Cancel'|translate}</a>
		</div>
	</form>
	{/if}
</div>
