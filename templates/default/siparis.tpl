<h1 class="page-heading">Sipariş #{$order.reference|escape}</h1>

<div class="row g-4">
	<div class="col-lg-8">
		<div class="border rounded p-4 bg-white mb-4">
			<h2 class="fs-6 mb-3">Ürünler</h2>
			{foreach $order.items as $item}
			<div class="d-flex justify-content-between gap-3 py-2 border-bottom">
				<div>
					<div class="fw-semibold">{$item.product_name|escape}</div>
					<div class="small text-muted">{$item.qty} adet x {$item.price_formatted}</div>
				</div>
				<div class="fw-semibold">{$item.total_formatted}</div>
			</div>
			{/foreach}
		</div>

		<div class="border rounded p-4 bg-white">
			<h2 class="fs-6 mb-3">Teslimat Adresi</h2>
			<p class="mb-1 fw-semibold">{$order.customer_name|escape}</p>
			<p class="mb-1">{$order.customer_phone|escape}</p>
			<p class="mb-0 text-muted">{$order.address_district|escape} / {$order.address_city|escape}<br>{$order.address_text|escape}</p>
			{if $order.note}
			<p class="mt-3 mb-0 small"><strong>Not:</strong> {$order.note|escape}</p>
			{/if}
		</div>
	</div>

	<div class="col-lg-4">
		<div class="border rounded p-4 bg-light">
			<p class="mb-2 d-flex justify-content-between"><span>Durum</span><strong>{$order.status_label}</strong></p>
			<p class="mb-2 d-flex justify-content-between"><span>Ödeme</span><strong>{$order.payment_label}</strong></p>
			<p class="mb-2 d-flex justify-content-between"><span>Ara Toplam</span><span>{$order.subtotal_formatted}</span></p>
			<p class="mb-2 d-flex justify-content-between"><span>Kargo</span><span>{$order.shipping_formatted}</span></p>
			<hr>
			<p class="mb-0 d-flex justify-content-between fs-5 fw-bold"><span>Toplam</span><span>{$order.total_formatted}</span></p>
		</div>
	</div>
</div>

<div class="mt-4">
	<a href="{$domain}siparislerim" class="btn btn-outline-secondary btn-sm">← Siparişlerim</a>
</div>
