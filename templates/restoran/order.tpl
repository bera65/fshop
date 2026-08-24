<div class="prime-container prime-page">

	<h1 class="prime-page__title">{'Order'|translate} #{$order.reference|escape}</h1>



	<div class="row g-4">

		<div class="col-lg-8">

			<div class="prime-page-card mb-4">

				<h2 class="prime-page__subtitle">{'Products'|translate}</h2>

				{foreach $order.items as $item}

				<div class="prime-list-row">

					<div>

						<div class="fw-semibold">{$item.product_name|escape}</div>

						<div class="small text-muted">{$item.qty} {'pcs'|translate} x {$item.price_formatted}</div>

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

				<hr>

				<p class="mb-0 d-flex justify-content-between fs-5 fw-bold"><span>{'Total'|translate}</span><span>{$order.total_formatted}</span></p>

			</div>

		</div>

	</div>



	<div class="mt-4">

		<a href="{$domain}orders" class="prime-btn prime-btn--outline prime-btn--sm">← {'Back to orders'|translate}</a>

	</div>

</div>

