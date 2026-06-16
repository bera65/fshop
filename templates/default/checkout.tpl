<h1 class="page-heading">Siparişi Tamamla</h1>

{if $orderError}
<div class="alert alert-danger">{$orderError}</div>
{/if}

<div class="row g-4">
	<div class="col-lg-7">
		<form method="post" action="{$domain}checkout" class="checkout-form page-card" id="checkoutForm">
			<input type="hidden" name="placeOrder" value="1">
			<input type="hidden" name="token" value="{$token}">

			<h2 class="fs-6 mb-3">Teslimat Bilgileri</h2>

			{if $addresses|@count}
			<div class="saved-address-list mb-4">
				{foreach $addresses as $addr}
				<label class="address-option border rounded p-3 mb-2 d-block">
					<div class="d-flex gap-2">
						<input type="radio" name="id_address" value="{$addr.id_address}" class="checkout-address-radio mt-1"
							data-full-name="{$addr.full_name|escape}"
							data-phone="{$addr.phone|escape}"
							data-city="{$addr.city|escape}"
							data-district="{$addr.district|escape}"
							data-address-text="{$addr.address_text|escape}"
							{if $selectedAddressId == $addr.id_address} checked{/if}>
						<span>
							<strong>{if $addr.label}{$addr.label|escape}{else}Adres{/if}</strong>
							{if $addr.is_default}<span class="badge bg-dark ms-1">Varsayılan</span>{/if}
							<span class="d-block small text-muted mt-1">
								{$addr.full_name|escape} · {$addr.phone|escape}<br>
								{$addr.city|escape} / {$addr.district|escape} — {$addr.address_text|escape}
							</span>
						</span>
					</div>
				</label>
				{/foreach}
				<label class="address-option border rounded p-3 mb-2 d-block">
					<div class="d-flex gap-2">
						<input type="radio" name="id_address" value="0" class="checkout-address-radio mt-1"
							{if $selectedAddressId == 0} checked{/if}>
						<span><strong>Yeni adres kullan</strong></span>
					</div>
				</label>
			</div>
			{else}
			<input type="hidden" name="id_address" value="0">
			{/if}

			<div id="checkoutAddressFields">
				<div class="row g-3">
					<div class="col-md-6">
						<label class="form-label">Ad Soyad</label>
						<input type="text" name="customer_name" id="checkoutCustomerName" class="form-control checkout-field" value="{$formData.customer_name|escape}" required>
					</div>
					<div class="col-md-6">
						<label class="form-label">Telefon</label>
						<input type="tel" name="customer_phone" id="checkoutCustomerPhone" class="form-control phone-input checkout-field" value="{$formData.customer_phone|escape}" required>
					</div>
					<div class="col-md-6">
						<label class="form-label">İl</label>
						<input type="text" name="address_city" id="checkoutCity" class="form-control checkout-field" value="{$formData.address_city|escape}" placeholder="İstanbul" required>
					</div>
					<div class="col-md-6">
						<label class="form-label">İlçe</label>
						<input type="text" name="address_district" id="checkoutDistrict" class="form-control checkout-field" value="{$formData.address_district|escape}" placeholder="Kadıköy" required>
					</div>
					<div class="col-12">
						<label class="form-label">Açık Adres</label>
						<textarea name="address_text" id="checkoutAddressText" class="form-control checkout-field" rows="3" placeholder="Mahalle, sokak, bina no, daire" required>{$formData.address_text|escape}</textarea>
					</div>
					<div class="col-12" id="saveAddressBlock">
						<div class="form-check mb-2">
							<input class="form-check-input" type="checkbox" name="save_address" id="saveAddressCheck" value="1">
							<label class="form-check-label" for="saveAddressCheck">Bu adresi kaydet</label>
						</div>
						<div id="saveAddressExtra" class="d-none">
							<label class="form-label">Adres Başlığı</label>
							<input type="text" name="address_label" class="form-control mb-2" placeholder="Ev, İş..." value="{$formData.address_label|escape}">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="set_default_address" id="setDefaultAddressCheck" value="1">
								<label class="form-check-label" for="setDefaultAddressCheck">Varsayılan adres yap</label>
							</div>
						</div>
					</div>
					<div class="col-12">
						<label class="form-label">Sipariş Notu (Opsiyonel)</label>
						<textarea name="note" class="form-control" rows="2" placeholder="Kargo notu vb.">{$formData.note|escape}</textarea>
					</div>
				</div>
			</div>

			<h2 class="fs-6 mb-3 mt-4">Ödeme Yöntemi</h2>

			{if $hooks.order_payment}
			{$hooks.order_payment nofilter}
			{else}
			{* Hiç ödeme modülü kurulu değilse sabit seçenekler *}
			<div class="payment-option mb-2">
				<label class="d-flex gap-2 align-items-start border rounded p-3 w-100">
					<input type="radio" name="payment_method" value="bank_transfer"{if $formData.payment_method == 'bank_transfer'} checked{/if}>
					<span>
						<strong>Havale / EFT</strong>
						<small class="d-block text-muted">Sipariş sonrası banka bilgileri gösterilir.</small>
					</span>
				</label>
			</div>
			<div class="payment-option mb-3">
				<label class="d-flex gap-2 align-items-start border rounded p-3 w-100">
					<input type="radio" name="payment_method" value="cash_on_delivery"{if $formData.payment_method == 'cash_on_delivery'} checked{/if}>
					<span>
						<strong>Kapıda Ödeme</strong>
						<small class="d-block text-muted">Teslimat sırasında nakit veya kart.</small>
					</span>
				</label>
			</div>
			{/if}
			<div class="form-check mb-3">
				<input class="form-check-input" type="checkbox" name="accept_terms" id="acceptTerms" value="1" required>
				<label class="form-check-label small" for="acceptTerms">
					<a href="{$domain}mesafeli-satis" target="_blank" rel="noopener">Mesafeli Satış Sözleşmesi</a> ve
					<a href="{$domain}gizlilik" target="_blank" rel="noopener">Gizlilik Politikası</a>'nı okudum, kabul ediyorum.
				</label>
			</div>

			<button type="submit" class="btn btn-primary w-100">Siparişi Onayla</button>
		</form>
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

			<div class="coupon-box mb-3">
				<label class="form-label small mb-1">Kupon Kodu</label>
				<div class="input-group input-group-sm">
					<input type="text" id="couponCodeInput" class="form-control text-uppercase" placeholder="KOD" value="{$checkoutTotals.coupon_code|escape}">
					<button type="button" class="btn btn-dark" id="applyCouponBtn">Uygula</button>
				</div>
				{if $checkoutTotals.has_coupon}
				<div class="d-flex justify-content-between align-items-center mt-2 small">
					<span class="text-success">Kupon: {$checkoutTotals.coupon_code|escape}</span>
					<button type="button" class="btn btn-link btn-sm p-0 text-danger" id="removeCouponBtn">Kaldır</button>
				</div>
				{/if}
			</div>

			<hr>

			<div class="d-flex justify-content-between mb-2">
				<span>Ara Toplam</span>
				<span id="checkoutSubtotal">{$checkoutTotals.subtotal_formatted}</span>
			</div>
			{if $checkoutTotals.discount > 0}
			<div class="d-flex justify-content-between mb-2 text-success" id="checkoutDiscountRow">
				<span>İndirim</span>
				<span id="checkoutDiscount">-{$checkoutTotals.discount_formatted}</span>
			</div>
			{else}
			<div class="d-flex justify-content-between mb-2 text-success d-none" id="checkoutDiscountRow">
				<span>İndirim</span>
				<span id="checkoutDiscount"></span>
			</div>
			{/if}
			<div class="d-flex justify-content-between mb-2">
				<span>Kargo</span>
				<span id="checkoutShipping">{if $checkoutTotals.shipping > 0}{$checkoutTotals.shipping_formatted}{else}Ücretsiz{/if}</span>
			</div>
			{if $checkoutTotals.shipping > 0}
			<p class="small text-muted mb-2">{Tools::displayPrice($checkoutTotals.free_shipping_min)} ve üzeri siparişlerde kargo ücretsiz.</p>
			{/if}
			<div class="d-flex justify-content-between fw-bold fs-5 mt-3">
				<span>Toplam</span>
				<span class="text-danger" id="checkoutTotal">{$checkoutTotals.total_formatted}</span>
			</div>
		</div>
	</div>
</div>
