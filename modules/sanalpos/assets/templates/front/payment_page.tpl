<div class="container py-4">
	<div class="row g-4">
		<div class="col-lg-7">
			<div class="page-card p-4">
				<h1 class="fs-5 mb-1">Kart ile Ödeme</h1>
				<p class="text-muted small mb-4">Kart bilgileriniz güvenli bağlantı üzerinden bankaya iletilir, sitemizde saklanmaz.</p>

				{if $paymentError}
				<div class="alert alert-danger">{$paymentError|escape}</div>
				{/if}

				<form method="post" action="{$domain}odeme-karti" autocomplete="off">
					<input type="hidden" name="payCard" value="1">
					<input type="hidden" name="token" value="{$token}">

					<div class="mb-3">
						<label class="form-label">Kart Üzerindeki İsim</label>
						<input type="text" name="card_holder" class="form-control" value="{$cardForm.holder|escape}" placeholder="AD SOYAD" required>
					</div>

					<div class="mb-3">
						<label class="form-label">Kart Numarası</label>
						<input type="text" name="card_number" class="form-control" value="{$cardForm.number|escape}"
							placeholder="0000 0000 0000 0000" inputmode="numeric" maxlength="23" required>
					</div>

					<div class="row g-3">
						<div class="col-4">
							<label class="form-label">Ay</label>
							<select name="exp_month" class="form-select" required>
								<option value="">Ay</option>
								{for $m=1 to 12}
								<option value="{$m}"{if $cardForm.exp_month == $m} selected{/if}>{if $m < 10}0{/if}{$m}</option>
								{/for}
							</select>
						</div>
						<div class="col-4">
							<label class="form-label">Yıl</label>
							<select name="exp_year" class="form-select" required>
								<option value="">Yıl</option>
								{assign var=thisYear value=$smarty.now|date_format:'%Y'}
								{for $y=$thisYear to $thisYear+10}
								<option value="{$y}"{if $cardForm.exp_year == $y} selected{/if}>{$y}</option>
								{/for}
							</select>
						</div>
						<div class="col-4">
							<label class="form-label">CVV</label>
							<input type="password" name="cvv" class="form-control" placeholder="123" inputmode="numeric" maxlength="4" required>
						</div>
					</div>

					<button type="submit" class="btn btn-primary w-100 mt-4">{$checkoutTotals.total_formatted} Ödemeyi Onayla</button>
				</form>

				<a href="{$domain}checkout" class="btn btn-link w-100 mt-2">← Ödeme yöntemini değiştir</a>

				<div class="alert alert-light border small mt-3 mb-0">
					<strong>Test modu:</strong> Geçerli herhangi bir kart numarası (ör. 4111 1111 1111 1111) onaylanır.
					<code>4000 0000 0000 0002</code> banka reddi simüle eder.
				</div>
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
