<div class="container py-4">
	<div class="row g-4">
		<div class="col-lg-7">
			<div class="page-card p-4">
				<h1 class="fs-5 mb-1">PayPal ile Ödeme</h1>
				<p class="text-muted small mb-4">
					Ödeme PayPal sayfasında tamamlanır. Para birimi: <strong>{$paypalCurrency|escape}</strong>
				</p>

				{if $paymentError}
				<div class="alert alert-danger">{$paymentError|escape}</div>
				{/if}

				{if $approveUrl}
				<a href="{$approveUrl|escape}" class="btn btn-primary btn-lg w-100">PayPal’da Ödemeye Devam Et</a>
				{else}
				<a href="{$domain}paypal-payment" class="btn btn-outline-primary w-100">Tekrar Dene</a>
				{/if}

				<a href="{$domain}checkout" class="btn btn-link w-100 mt-2">← Ödeme yöntemini değiştir</a>
			</div>
		</div>

		<div class="col-lg-5">
			<div class="checkout-summary page-card bg-light">
				<h2 class="fs-6 mb-3">Sipariş Özeti</h2>

				{foreach $cart.items as $item}
				<div class="d-flex justify-content-between gap-2 mb-2 small">
					<span>{$item.product_name|escape} x {$item.qty}</span>
					<span>{$item.line_total_formatted}</span>
				</div>
				{/foreach}

				<hr>
				<div class="d-flex justify-content-between small mb-1">
					<span>Ara Toplam</span>
					<span>{$checkoutTotals.subtotal_formatted}</span>
				</div>
				{if $checkoutTotals.has_coupon}
				<div class="d-flex justify-content-between small mb-1 text-success">
					<span>İndirim ({$checkoutTotals.coupon_code|escape})</span>
					<span>-{$checkoutTotals.discount_formatted}</span>
				</div>
				{/if}
				<div class="d-flex justify-content-between small mb-1">
					<span>Kargo</span>
					<span>{if $checkoutTotals.shipping > 0}{$checkoutTotals.shipping_formatted}{else}Ücretsiz{/if}</span>
				</div>
				<div class="d-flex justify-content-between fw-semibold mt-2">
					<span>Toplam</span>
					<span>{$checkoutTotals.total_formatted}</span>
				</div>
			</div>
		</div>
	</div>
</div>
