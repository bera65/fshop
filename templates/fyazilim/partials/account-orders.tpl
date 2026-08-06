<div class="account-orders-head d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
	<h2 class="dress-account-section-title mb-0">{'My Orders'|translate}</h2>
</div>

{if !$selectedOrder}
<div class="account-order-filters mb-4">
	<a href="{$domain}my-account?filter=all" class="account-order-filter{if $orderFilter == 'all'} is-active{/if}">{'All'|translate}</a>
	<a href="{$domain}my-account?filter=ongoing" class="account-order-filter{if $orderFilter == 'ongoing'} is-active{/if}">{'Ongoing orders'|translate}</a>
	<a href="{$domain}my-account?filter=cancelled" class="account-order-filter{if $orderFilter == 'cancelled'} is-active{/if}">{'Cancellations'|translate}</a>
	<a href="{$domain}my-account?filter=returns" class="account-order-filter{if $orderFilter == 'returns'} is-active{/if}">{'Returns'|translate}</a>
</div>

{if !$orders|@count}
<div class="dress-account-empty">
	<p>{'No orders yet'|translate}</p>
	<a href="{$domain}" class="prime-btn prime-btn--primary prime-btn--sm">{'Start shopping'|translate}</a>
</div>
{else}
<div class="account-order-list">
	{foreach $orders as $o}
	<div class="account-order-card">
		<div class="account-order-card__meta">
			<div class="row g-2 small">
				<div class="col-sm-4"><span class="text-muted">{'Order Date'|translate}</span><br><strong>{$o.date_formatted}</strong></div>
				<div class="col-sm-4"><span class="text-muted">{'Order summary'|translate}</span><br><strong>1 {'Delivery'|translate}, {$o.item_count} {'Products'|translate|lower}</strong></div>
				<div class="col-sm-4"><span class="text-muted">{'Total'|translate}</span><br><strong class="account-order-card__total">{$o.total_formatted}</strong></div>
			</div>
			<div class="text-end mt-2">
				<a href="{$domain}my-account?order={$o.id_order}" class="btn btn-sm account-order-detail-btn">{'Order Detail'|translate}</a>
			</div>
		</div>
		<div class="account-order-card__body">
			<div class="account-order-card__status">
				{if $o.is_delivered}
				<span class="account-order-status account-order-status--success">✓ {$o.status_label|escape}</span>
				{elseif $o.is_returned}
				<span class="account-order-status account-order-status--return">↩ {$o.status_label|escape}</span>
				{elseif $o.is_cancelled}
				<span class="account-order-status account-order-status--cancel">✕ {$o.status_label|escape}</span>
				{else}
				<span class="account-order-status">{$o.status_label|escape}</span>
				{/if}
			</div>
			<div class="account-order-card__thumb">
				<img src="{$o.thumb_url|escape}" alt="{$o.thumb_product|escape}">
			</div>
			<div class="account-order-card__actions">
				{if $o.can_review}
				<a href="{$domain}product?id={$o.first_product_id}#product-reviews" class="btn btn-sm account-review-btn">{'Write a review'|translate}</a>
				{/if}
			</div>
		</div>
	</div>
	{/foreach}
</div>
{/if}

{else}
<div class="mb-3">
	<a href="{$domain}my-account{if $orderFilter != 'all'}?filter={$orderFilter|escape}{/if}" class="small">← {'Back to orders'|translate}</a>
</div>

<div class="account-order-detail">
	<div class="account-order-card mb-4">
		<div class="account-order-card__meta">
			<div class="row g-2 small">
				<div class="col-md-3"><span class="text-muted">{'Order'|translate}</span><br><strong>#{$selectedOrder.reference|escape}</strong></div>
				<div class="col-md-3"><span class="text-muted">{'Order Date'|translate}</span><br><strong>{$selectedOrder.date_formatted}</strong></div>
				<div class="col-md-3"><span class="text-muted">{'Status'|translate}</span><br><strong>{$selectedOrder.status_label|escape}</strong></div>
				<div class="col-md-3"><span class="text-muted">{'Total'|translate}</span><br><strong class="account-order-card__total">{$selectedOrder.total_formatted}</strong></div>
			</div>
		</div>
	</div>

	<div class="dress-account-card mb-4">
		<h3 class="fs-6 mb-3">{'Products'|translate}</h3>
		{foreach $selectedOrder.items as $item}
		<div class="py-3 border-bottom">
			<div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
				<div>
					<div class="fw-semibold">{$item.product_name|escape}</div>
					<div class="small text-muted">{$item.qty} x {$item.price_formatted}</div>
				</div>
				<div class="d-flex align-items-center gap-2">
					<strong>{$item.total_formatted}</strong>
					{if $selectedOrder.is_delivered && $item.id_product > 0}
					<a href="{$domain}product?id={$item.id_product}#product-reviews" class="btn btn-sm account-review-btn">{'Write a review'|translate}</a>
					{/if}
				</div>
			</div>
			{include file='fyazilim/partials/virtual-order-item.tpl' item=$item}
		</div>
		{/foreach}
	</div>

	<div class="dress-account-card mb-4">
		<h3 class="fs-6 mb-3">{'Order Summary'|translate}</h3>
		<div class="vstack gap-2 small">
			<div class="d-flex justify-content-between"><span>{'Subtotal'|translate}</span><span>{$selectedOrder.subtotal_formatted}</span></div>
			{if $selectedOrder.promotion_discount > 0}
			<div class="d-flex justify-content-between text-success">
				<span>{if $selectedOrder.promotion_name}{$selectedOrder.promotion_name|escape}{else}{'Discount'|translate}{/if}</span>
				<span>-{$selectedOrder.promotion_discount_formatted}</span>
			</div>
			{/if}
			{if $selectedOrder.coupon_discount > 0}
			<div class="d-flex justify-content-between text-success">
				<span>{'Coupon'|translate}{if $selectedOrder.coupon_code}: {$selectedOrder.coupon_code|escape}{/if}</span>
				<span>-{$selectedOrder.coupon_discount_formatted}</span>
			</div>
			{/if}
			{if $selectedOrder.payment_discount > 0}
			<div class="d-flex justify-content-between text-success">
				<span>{if $selectedOrder.payment_discount_label}{$selectedOrder.payment_discount_label|escape}{else}{'Discount'|translate}{/if}</span>
				<span>-{$selectedOrder.payment_discount_formatted}</span>
			</div>
			{/if}
			<div class="d-flex justify-content-between"><span>{'Cargo'|translate}</span><span>{$selectedOrder.shipping_formatted}</span></div>
			<div class="d-flex justify-content-between"><span>{'Payment'|translate}</span><span>{$selectedOrder.payment_label|escape}</span></div>
			{if $selectedOrder.cargo_company}
			<div class="d-flex justify-content-between"><span>{'Cargo company'|translate}</span><span>{$selectedOrder.cargo_company|escape}{if $selectedOrder.tracking_number} — {$selectedOrder.tracking_number|escape}{/if}</span></div>
			{/if}
			<div class="d-flex justify-content-between fw-bold border-top pt-2 mt-1"><span>{'Total'|translate}</span><span>{$selectedOrder.total_formatted}</span></div>
		</div>
	</div>

	{if $hooks.order_confirmation}
	<div class="dress-account-card mb-4">
		{$hooks.order_confirmation nofilter}
	</div>
	{/if}

	<div class="dress-account-card mb-4">
		<h3 class="fs-6 mb-3">{'Delivery Address'|translate}</h3>
		<p class="mb-1 fw-semibold">{$selectedOrder.customer_name|escape}</p>
		<p class="mb-0 text-muted">{$selectedOrder.address_district|escape} / {$selectedOrder.address_city|escape}<br>{$selectedOrder.address_text|escape}</p>
	</div>

	<div class="d-flex flex-wrap gap-2">
		{if $selectedOrder.can_cancel}
		<a href="{$domain}cancel-request?id_order={$selectedOrder.id_order}" class="prime-btn prime-btn--outline prime-btn--sm">{'Request cancellation'|translate}</a>
		{elseif $selectedOrder.cancel_request}
		<span class="badge bg-secondary">{'Cancel request'|translate}: {$selectedOrder.cancel_request.status_label|escape}</span>
		{/if}

		{if $selectedOrder.can_return}
		<a href="{$domain}return-request?id_order={$selectedOrder.id_order}" class="prime-btn prime-btn--outline prime-btn--sm">{'Request return'|translate}</a>
		{elseif $selectedOrder.return_request}
		<a href="{$domain}returns?id={$selectedOrder.return_request.id_return}" class="prime-btn prime-btn--outline prime-btn--sm">{'View return request'|translate}</a>
		{/if}

		{if $selectedOrder.cancel_request && $selectedOrder.cancel_request.admin_receipt_url}
		<a href="{$selectedOrder.cancel_request.admin_receipt_url|escape}" target="_blank" class="prime-btn prime-btn--outline prime-btn--sm">{'Cancel receipt'|translate}</a>
		{/if}
	</div>

	{if $selectedOrder.cancel_request && $selectedOrder.cancel_request.admin_message}
	<div class="alert alert-info mt-3 mb-0 small">
		<strong>{'Store response'|translate}:</strong> {$selectedOrder.cancel_request.admin_message|escape|nl2br}
	</div>
	{/if}

	{include file='fyazilim/partials/order-contact.tpl'}
</div>
{/if}
