<div class="prime-container prime-page">

	<h1 class="prime-page__title">{'Order'|translate} #{$order.reference|escape}{if $order.has_gift_wrap|default:false} <span class="text-danger" title="{'Gift wrap'|translate}" aria-label="{'Gift wrap'|translate}"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/></svg></span>{/if}</h1>



	<div class="row g-4">

		<div class="col-lg-8">

			<div class="prime-page-card mb-4">

				<h2 class="prime-page__subtitle">{'Products'|translate}</h2>

				{foreach $order.items as $item}

				<div class="prime-list-row">

					<div>

						<div class="fw-semibold">{$item.product_name|escape}</div>

						<div class="small text-muted">{if $item.measure_label|default:''}{$item.measure_label|escape}{elseif $item.qty_label|default:''}{$item.qty_label|escape}{else}{$item.qty} {'pcs'|translate}{/if} x {$item.price_formatted}</div>

					</div>

					<div class="fw-semibold">{$item.total_formatted}</div>

				</div>

				{/foreach}

			</div>



			<div class="prime-page-card">

				<h2 class="prime-page__subtitle">{'Delivery Address'|translate}</h2>

				<p class="mb-1 fw-semibold">{$order.customer_name|escape}</p>

				<p class="mb-1">{$order.customer_phone|escape}</p>

				<p class="mb-0 text-muted">{$order.address_district|escape} / {$order.address_city|escape}<br>{$order.address_text|escape}</p>

				{if $order.note}

				<p class="mt-3 mb-0 small"><strong>{'Note'|translate}:</strong> {$order.note|escape}</p>

				{/if}

			</div>

		</div>



		<div class="col-lg-4">

			<div class="prime-page-card prime-page-card--soft">

				<p class="mb-2 d-flex justify-content-between"><span>{'Status'|translate}</span><strong>{$order.status_label}</strong></p>

				<p class="mb-2 d-flex justify-content-between"><span>{'Payment'|translate}</span><strong>{$order.payment_label}</strong></p>

				<p class="mb-2 d-flex justify-content-between"><span>{'Subtotal'|translate}</span><span>{$order.subtotal_formatted}</span></p>

				<p class="mb-2 d-flex justify-content-between"><span>{'Cargo'|translate}</span><span>{$order.shipping_formatted}</span></p>

				{if $order.has_gift_wrap|default:false}
				<p class="mb-2 d-flex justify-content-between"><span>{'Gift wrap'|translate}</span><span>{if $order.gift_wrap_fee > 0}{$order.gift_wrap_fee_formatted}{else}{'Free'|translate}{/if}</span></p>
				{/if}

				<hr>

				<p class="mb-0 d-flex justify-content-between fs-5 fw-bold"><span>{'Total'|translate}</span><span>{$order.total_formatted}</span></p>

				{if $order.has_invoice|default:false && $order.invoice_view_url|default:''}
				<hr>
				<a href="{$order.invoice_view_url|escape}" target="_blank" rel="noopener" class="prime-btn prime-btn--outline prime-btn--sm w-100">{$order.invoice_label|default:'Invoice'|translate|escape}</a>
				{/if}

			</div>

		</div>

	</div>



	<div class="mt-4 d-flex flex-wrap gap-2">

		<a href="{$domain}orders" class="prime-btn prime-btn--outline prime-btn--sm">? {'Back to orders'|translate}</a>

		{if $canReturnRequest}
		<a href="{$domain}return-request?id_order={$order.id_order}" class="prime-btn prime-btn--primary prime-btn--sm">{'Request return'|translate}</a>
		{elseif $returnRequest}
		<a href="{$domain}returns?id={$returnRequest.id_return}" class="prime-btn prime-btn--outline prime-btn--sm">{'View return request'|translate}</a>
		{/if}

	</div>

</div>

