<div class="container py-4 iyzico-payment-page">
	<div class="row g-4">
		<div class="col-lg-7">
			<div class="page-card p-4">
				<h1 class="fs-5 mb-1">Kart ile Ödeme</h1>
				<p class="text-muted small mb-3">Referans: <strong>{$order.reference|escape}</strong></p>

				{if $paymentError}
				<div class="alert alert-danger">{$paymentError|escape}</div>
				{/if}

				{if $checkoutFormContent}
				<div class="iyzico-form-wrap">
					<div id="iyzipay-checkout-form" class="{$formClass|escape}">
						{$checkoutFormContent nofilter}
					</div>
				</div>
				{elseif $paymentPageUrl}
				<p class="text-muted">iyzico ödeme sayfasına yönlendiriliyorsunuz…</p>
				<script>window.location.href = "{$paymentPageUrl|escape:'javascript'}";</script>
				<a href="{$paymentPageUrl|escape}" class="btn btn-dark">Ödemeye git</a>
				{elseif !$paymentError}
				<div class="alert alert-warning mb-0">Ödeme ekranı yüklenemedi. Lütfen birkaç dakika sonra tekrar deneyin.</div>
				{/if}

				<a href="{$domain}checkout" class="btn btn-link mt-3 px-0">← Ödeme yöntemini değiştir</a>
			</div>
		</div>

		<div class="col-lg-5">
			<div class="checkout-summary page-card bg-light">
				<h2 class="fs-6 mb-3">Sipariş Özeti</h2>

				{foreach $order.items as $item}
				<div class="d-flex justify-content-between gap-2 mb-2 small">
					<span>{$item.product_name|escape} x {$item.qty}</span>
					<span>{$item.total_formatted}</span>
				</div>
				{/foreach}

				<hr>
				<div class="d-flex justify-content-between small mb-1">
					<span>Ara Toplam</span>
					<span>{$order.subtotal_formatted}</span>
				</div>
				<div class="d-flex justify-content-between small mb-1">
					<span>Kargo</span>
					<span>{if $order.shipping > 0}{$order.shipping_formatted}{else}Ücretsiz{/if}</span>
				</div>
				<div class="d-flex justify-content-between fw-semibold mt-2">
					<span>Toplam</span>
					<span>{$order.total_formatted}</span>
				</div>
			</div>
		</div>
	</div>
</div>
