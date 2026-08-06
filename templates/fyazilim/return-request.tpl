<div class="prime-container prime-page">

	<h1 class="prime-page__title">{'New Return Request'|translate}</h1>

	{if $flash}
	<div class="alert alert-{$flashType|default:'danger'}">{$flash|escape}</div>
	{/if}

	{if !$eligibleOrders|@count}
	<div class="prime-empty">
		<i class="fa-solid fa-rotate-left"></i>
		<p>{'No eligible orders for return'|translate}</p>
		<a href="{$domain}my-account" class="prime-btn prime-btn--outline prime-btn--sm">{'My Orders'|translate}</a>
	</div>
	{else}

	<p class="text-muted mb-4">{'Return request form intro'|translate|replace:'%days%':$returnDays}</p>

	<form method="post" enctype="multipart/form-data" class="prime-page-card">
		<input type="hidden" name="submitReturn" value="1">
		<input type="hidden" name="token" value="{$token}">

		<div class="mb-3">
			<label class="form-label" for="id_order">{'Select order'|translate}</label>
			<select name="id_order" id="id_order" class="form-select" required>
				<option value="">{'Choose an order'|translate}</option>
				{foreach $eligibleOrders as $o}
				<option value="{$o.id_order}" {if $selectedOrderId == $o.id_order}selected{/if}>
					#{$o.reference|escape} — {$o.date_formatted} — {$o.status_label|escape} — {$o.total_formatted}
				</option>
				{/foreach}
			</select>
		</div>

		<div class="mb-3">
			<label class="form-label" for="message">{'Return reason and details'|translate}</label>
			<textarea name="message" id="message" class="form-control" rows="5" required maxlength="5000" placeholder="{'Return message placeholder'|translate}"></textarea>
		</div>

		<div class="mb-4">
			<label class="form-label" for="images">{'Upload images'|translate}</label>
			<input type="file" name="images[]" id="images" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
			<div class="form-text">{'Return images help optional'|translate|replace:'%max%':$maxImages}</div>
		</div>

		<div class="d-flex flex-wrap gap-2">
			<button type="submit" class="prime-btn prime-btn--primary prime-btn--sm">{'Submit return request'|translate}</button>
			<a href="{$domain}returns" class="prime-btn prime-btn--outline prime-btn--sm">{'Cancel'|translate}</a>
		</div>
	</form>

	{/if}

</div>
