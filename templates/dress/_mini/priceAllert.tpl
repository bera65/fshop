<div class="modal fade" id="priceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title fw-semibold fs-5">
			{'Price Allert'|translate}
		</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
	  <form action="#" id="alertPrice" class="alert-price-form" data-api-url="{$domain}api/module.php?m=alert-price&action=subscribe" method="POST">
      <div class="modal-body">
		<div id="alertPriceMessage" class="alert d-none mb-3" role="alert"></div>
        <div id="userEmail" class="mb-3">
          <label class="form-label" for="emailInput">{'Your Email'|translate}</label>
          <input type="email" name="userEmail" class="form-control" id="emailInput" placeholder="email@mail.com" required autocomplete="email">
        </div>
		<div class="mb-1">
          <label class="form-label" for="alertTargetPrice">{'Target Price'|translate}</label>
          <input type="text" name="price" class="form-control" id="alertTargetPrice" placeholder="{'eg : 249.99'|translate}" required inputmode="decimal">
        </div>
        <input type="hidden" name="token" value="{$token|escape}">
        <input type="hidden" name="selectedProductId" id="selectedProductId" value="">
        <input type="hidden" name="selectedPrice" id="selectedPrice" value="">
		<small class="mt-2 d-block">
		  {'I consent to the use of my contact information for notification purposes.'|translate}
        </small>
      </div>

      <div class="modal-footer">
		<button type="button" class="btn btn-danger" data-bs-dismiss="modal">{'Close'|translate}</button>
        <button type="submit" class="btn btn-primary" id="saveBtn">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bell-icon lucide-bell"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>
			{'Create Notification'|translate}
		</button>
      </div>
	  </form>
    </div>
  </div>
</div>
