<div class="alert-stock-notify" id="alertStockNotify" data-api-url="{$api_url|escape}">
	<div class="alert-stock-notify__head">
		<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>
		<strong>{'Notify me when back in stock'|translate}</strong>
	</div>
	<p class="alert-stock-notify__desc">{'Enter your email and we will notify you when this product is available again.'|translate}</p>
	<form class="alert-stock-notify__form" data-alert-stock-form novalidate>
		<input type="hidden" name="id_product" value="{$id_product|escape}">
		<input type="hidden" name="id_variation" value="0" data-alert-variation-input>
		<input type="hidden" name="token" value="{$csrf_token|escape}">
		<label class="visually-hidden" for="alertStockEmail">{'Your Email'|translate}</label>
		<div class="alert-stock-notify__row">
			<input type="email" class="form-control form-control-sm" id="alertStockEmail" name="email" value="{$user_email|escape}" placeholder="email@example.com" autocomplete="email" required>
			<button type="submit" class="btn btn-sm btn-dark">{'Notify me'|translate}</button>
		</div>
	</form>
	<div class="alert-stock-notify__message" data-alert-stock-message hidden></div>
</div>
