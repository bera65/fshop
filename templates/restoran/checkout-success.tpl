<div class="prime-container prime-page">
	<div class="checkout-success text-center py-2">
		<div class="success-icon mb-3">✓</div>
		<h1 class="prime-page__title mb-2">{'Order received'|translate}</h1>
		<p class="text-muted mb-4">{'Your order number'|translate} <strong>{$order.reference}</strong></p>

		<div class="prime-page-card text-start mx-auto mb-4" style="max-width: 640px;">
			<div class="row g-3 small">
				<div class="col-md-6">
					<p class="mb-1 text-muted">{'Status'|translate}</p>
					<p class="fw-semibold mb-0">{$order.status_label}</p>
				</div>
				<div class="col-md-6">
					<p class="mb-1 text-muted">{'Payment'|translate}</p>
					<p class="fw-semibold mb-0">{$order.payment_label}</p>
				</div>
				<div class="col-md-6">
					<p class="mb-1 text-muted">{'Total'|translate}</p>
					<p class="fw-semibold mb-0">{$order.total_formatted}</p>
				</div>
				<div class="col-md-6">
					<p class="mb-1 text-muted">{'Date'|translate}</p>
					<p class="fw-semibold mb-0">{$order.date_formatted}</p>
				</div>
			</div>

			{if $hooks.order_confirmation}{$hooks.order_confirmation nofilter}{/if}

			{assign var="hasVirtual" value=false}
			{foreach $order.items as $item}
				{if $item.is_virtual}{assign var="hasVirtual" value=true}{/if}
			{/foreach}
			{if $hasVirtual}
			<div class="alert alert-info small mt-3 mb-0">
				{'Virtual product checkout notice'|translate}
				<a href="{$domain}order?id={$order.id_order}">{'Order Detail'|translate}</a>.
			</div>
			{/if}
		</div>

		<div class="d-flex flex-wrap gap-2 justify-content-center">
			<a href="{$domain}order?id={$order.id_order}" class="prime-btn prime-btn--primary">{'Order Detail'|translate}</a>
			<a href="{$domain}orders" class="prime-btn prime-btn--outline">{'My Orders'|translate}</a>
			<a href="{$domain}" class="prime-btn prime-btn--outline">{'Continue Shopping'|translate}</a>
		</div>
	</div>
</div>
