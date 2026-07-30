<div class="payment-option mb-2">
	<label class="d-flex gap-2 align-items-start border rounded p-3 w-100">
		<input type="radio" name="payment_method" value="tami"{if $formData.payment_method == 'tami'} checked{/if}>
		<span>
			<strong>Kredi / Banka Kartı (Tami)</strong>
			<small class="d-block text-muted">3D Secure ile güvenli kart ödemesi.</small>
		</span>
	</label>
</div>
