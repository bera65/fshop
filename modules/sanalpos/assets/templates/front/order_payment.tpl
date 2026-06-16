<div class="payment-option mb-2">
	<label class="d-flex gap-2 align-items-start border rounded p-3 w-100">
		<input type="radio" name="payment_method" value="credit_card"{if $formData.payment_method == 'credit_card'} checked{/if}>
		<span>
			<strong>Kredi Kartı</strong>
			<small class="d-block text-muted">Bir sonraki adımda kart bilgilerinizi girersiniz.</small>
		</span>
	</label>
</div>
