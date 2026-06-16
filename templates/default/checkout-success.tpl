<div class="checkout-success text-center py-4">
	<div class="success-icon mb-3">✓</div>
	<h1 class="fs-3 mb-2">Siparişiniz Alındı!</h1>
	<p class="text-muted mb-4">Sipariş numaranız: <strong>{$order.reference}</strong></p>

	<div class="checkout-success-box text-start p-4 border rounded bg-white mx-auto mb-4">
		<div class="row g-3 small">
			<div class="col-md-6">
				<p class="mb-1 text-muted">Durum</p>
				<p class="fw-semibold mb-0">{$order.status_label}</p>
			</div>
			<div class="col-md-6">
				<p class="mb-1 text-muted">Ödeme</p>
				<p class="fw-semibold mb-0">{$order.payment_label}</p>
			</div>
			<div class="col-md-6">
				<p class="mb-1 text-muted">Toplam</p>
				<p class="fw-semibold mb-0">{$order.total_formatted}</p>
			</div>
			<div class="col-md-6">
				<p class="mb-1 text-muted">Tarih</p>
				<p class="fw-semibold mb-0">{$order.date_formatted}</p>
			</div>
		</div>

		{if $hooks.order_confirmation}{$hooks.order_confirmation nofilter}{/if}
	</div>

	<div class="d-flex flex-wrap gap-2 justify-content-center">
		<a href="{$domain}siparis?id={$order.id_order}" class="btn btn-dark">Sipariş Detayı</a>
		<a href="{$domain}siparislerim" class="btn btn-outline-secondary">Siparişlerim</a>
		<a href="{$domain}" class="btn btn-link">Alışverişe Devam Et</a>
	</div>
</div>
