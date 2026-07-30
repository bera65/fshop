<div class="payment-option mb-2">
	<label class="d-flex gap-2 align-items-start border rounded p-3 w-100">
		<input type="radio" name="payment_method" value="paypal"{if $formData.payment_method == 'paypal'} checked{/if}>
		<span>
			<strong>PayPal</strong>
			<small class="d-block text-muted">PayPal hesabınız veya kartınızla güvenli ödeme.</small>
		</span>
	</label>
</div>
